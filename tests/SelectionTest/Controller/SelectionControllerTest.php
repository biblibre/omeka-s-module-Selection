<?php declare(strict_types=1);

namespace SelectionTest\Controller;

use OmekaTestHelper\Controller\OmekaControllerTestCase;

class SelectionControllerTest extends OmekaControllerTestCase
{
    /**
     * @var \Omeka\Api\Representation\ItemRepresentation
     */
    protected $item;

    /**
     * @var \Omeka\Api\Representation\SiteRepresentation
     */
    protected $site;

    /**
     * @var \Omeka\Api\Representation\UserRepresentation
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
        $this->site = $this->createSite('test', 'test');
        $this->item = $this->createUrlItem('First Item');
        $this->user = $this->createUser('test@test.fr', 'toto');
        $this->login('test@test.fr', 'toto');
    }

    public function tearDown(): void
    {
        $this->loginAsAdmin();
        $selectionResources = $this->api()->search('selection_resources')->getContent();
        foreach ($selectionResources as $selectionResource) {
            $this->api()->delete('selection_resources', $selectionResource->id());
        }
        $this->api()->delete('items', $this->item->id());
        $this->api()->delete('sites', $this->site->id());
        $this->api()->delete('users', $this->user->id());
    }

    public function testAdditemToSelectionShouldStoreSelectionForUser(): void
    {
        $this->dispatch('/s/test/guest/selection/add', null, ['id' => $this->item->id()], true);

        $selectionResources = $this->api()->search('selection_resources', [
            'owner_id' => $this->user->id(),
            'resource_id' => $this->item->id(),
        ])->getContent();

        $this->assertCount(1, $selectionResources);
    }

    public function testAddmediaToSelectionShouldStoreSelectionForUSer(): void
    {
        $media = $this->item->primaryMedia();
        $this->dispatch('/s/test/guest/selection/add', null, ['id' => $media->id()], true);

        $selectionResources = $this->api()->search('selection_resources', [
            'owner_id' => $this->user->id(),
            'resource_id' => $media->id(),
        ])->getContent();

        $this->assertCount(1, $selectionResources);
    }

    public function testAddExistingItemShouldNotUpdateSelection(): void
    {
        $this->addToSelection($this->item);
        $this->dispatch('/s/test/guest/selection/add', null, ['id' => $this->item->id()], true);

        $selectionResources = $this->api()->search('selection_resources', [
            'owner_id' => $this->user->id(),
        ])->getContent();

        $this->assertCount(1, $selectionResources);
    }

    public function testRemoveItemToSelectionShouldRemoveSelectionForUser(): void
    {
        $this->addToSelection($this->item);
        $this->dispatch('/s/test/guest/selection/delete', null, ['id' => $this->item->id()], true);
        $this->assertResponseStatusCode(200);

        $selectionResources = $this->api()->search('selection_resources', [
            'owner_id' => $this->user->id(),
        ])->getContent();

        $this->assertEmpty($selectionResources);
    }

    public function testDisplaySelectionShouldDisplayResources(): void
    {
        $this->addToSelection($this->item);
        $this->dispatch('/s/test/guest/selection');
        $this->assertXPathQueryContentContains('//h4', 'First Item');
    }

    public function testDisplaySelectionShouldDisplayMedia(): void
    {
        $media = $this->item->primaryMedia();
        $this->addToSelection($media);
        $this->dispatch('/s/test/guest/selection');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentRegex('.property .value', '/media1/');
    }

    protected function createUrlItem($title)
    {
        $media_url = 'http://farm8.staticflickr.com/7495/28077970085_4d976b3c96_z_d.jpg';
        $item = $this->api()->create('items', [
            'dcterms:identifier' => [
                [
                    'type' => 'literal',
                    'property_id' => '10',
                    '@value' => 'item1',
                ],
            ],
            'dcterms:title' => [
                [
                    'type' => 'literal',
                    'property_id' => '1',
                    '@value' => $title,
                ],
            ],
            'o:media' => [
                [
                    'o:ingester' => 'url',
                    'ingest_url' => $media_url,
                    'dcterms:identifier' => [
                        [
                            'type' => 'literal',
                            'property_id' => 10,
                            '@value' => 'media1',
                        ],
                    ],
                ],
            ],
        ])->getContent();

        return $item;
    }

    protected function createSite($slug, $title)
    {
        $site = $this->api()->create('sites', [
            'o:slug' => $slug,
            'o:theme' => 'default',
            'o:title' => $title,
            'o:is_public' => '1',
        ])->getContent();

        return $site;
    }

    protected function createUser($login, $password)
    {
        $user = $this->api()->create('users', [
            'o:email' => $login,
            'o:name' => $login,
            'o:role' => 'global_admin',
            'o:is_active' => true,
        ])->getContent();

        $em = $this->getEntityManager();
        $userEntity = $em->find(\Omeka\Entity\User::class, $user->id());
        $userEntity->setPassword($password);
        $em->flush();

        return $user;
    }

    protected function addToSelection($resource): void
    {
        $this->api()->create('selection_resources', [
            'o:owner' => ['o:id' => $this->user->id()],
            'o:resource' => ['o:id' => $resource->id()],
        ]);
    }
}

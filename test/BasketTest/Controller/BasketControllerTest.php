<?php

namespace SelectionTest\Controller;

use OmekaTestHelper\Controller\OmekaControllerTestCase;

class SelectionControllerTest extends OmekaControllerTestCase
{
    protected $user;
    protected $item;
    protected $site;

    public function setUp()
    {
        parent::setUp();

        $this->loginAsAdmin();
        $this->site = $this->createSite('test', 'test');
        $this->item = $this->createUrlItem('First Item');
        $this->user = $this->createUser('test@test.fr', 'toto');
        $this->login('test@test.fr', 'toto');
    }

    public function tearDown()
    {
        $this->loginAsAdmin();
        $selectionItems = $this->api()->search('selection_items')->getContent();
        foreach ($selectionItems as $selectionItem) {
            $this->api()->delete('selection_items', $selectionItem->id());
        }
        $this->api()->delete('items', $this->item->id());
        $this->api()->delete('sites', $this->site->id());
        $this->api()->delete('users', $this->user->id());
    }

    /** @test */
    public function additemToSelectionShouldStoreSelectionForUser()
    {
        $this->dispatch('/s/test/selection/add/' . $this->item->id());

        $selectionItems = $this->api()->search('selection_items', [
            'user_id' => $this->user->id(),
            'resource_id' => $this->item->id(),
        ])->getContent();

        $this->assertCount(1, $selectionItems);
    }

    /** @test */
    public function addmediaToSelectionShouldStoreSelectionForUSer()
    {
        $media = $this->item->primaryMedia();
        $this->dispatch('/s/test/selection/add/' . $media->id());

        $selectionItems = $this->api()->search('selection_items', [
            'user_id' => $this->user->id(),
            'resource_id' => $media->id(),
        ])->getContent();

        $this->assertCount(1, $selectionItems);
    }

    /** @test */
    public function addExistingItemShouldNotUpdateSelection()
    {
        $this->addToSelection($this->item);
        $this->dispatch('/s/test/selection/add/' . $this->item->id());

        $selectionItems = $this->api()->search('selection_items', [
            'user_id' => $this->user->id(),
        ])->getContent();

        $this->assertCount(1, $selectionItems);
    }

    /** @test */
    public function removeItemToSelectionShouldRemoveSelectionForUser()
    {
        $this->addToSelection($this->item);
        $this->dispatch('/s/test/selection/delete/' . $this->item->id());
        $this->assertResponseStatusCode(200);

        $selectionItems = $this->api()->search('selection_items', [
            'user_id' => $this->user->id(),
        ])->getContent();

        $this->assertEmpty($selectionItems);
    }

    /** @test */
    public function displaySelectionShouldDisplayItems()
    {
        $this->addToSelection($this->item);
        $this->dispatch('/s/test/selection');
        $this->assertXPathQueryContentContains('//h4', 'First Item');
    }

    /** @test */
    public function displaySelectionShouldDisplayMedia()
    {
        $media = $this->item->primaryMedia();
        $this->addToSelection($media);
        $this->dispatch('/s/test/selection');
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
        $userEntity = $em->find('Omeka\Entity\User', $user->id());
        $userEntity->setPassword($password);
        $em->flush();

        return $user;
    }

    protected function addToSelection($resource)
    {
        $this->api()->create('selection_items', [
            'o:user_id' => $this->user->id(),
            'o:resource_id' => $resource->id(),
        ]);
    }
}

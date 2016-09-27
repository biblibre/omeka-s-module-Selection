<?php

namespace BasketTest\Controller;

use OmekaTestHelper\Controller\OmekaControllerTestCase;
use Omeka\Entity\User;

class BasketControllerTest extends OmekaControllerTestCase
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
        $basketItems = $this->api()->search('basket_items')->getContent();
        foreach ($basketItems as $basketItem) {
            $this->api()->delete('basket_items', $basketItem->id());
        }
        $this->api()->delete('items', $this->item->id());
        $this->api()->delete('sites', $this->site->id());
        $this->api()->delete('users', $this->user->id());
    }

    /** @test */
    public function additemToBasketShouldStoreBasketForUser()
    {
        $this->dispatch('/s/test/basket/add/' . $this->item->id());

        $basketItems = $this->api()->search('basket_items', [
            'user_id' => $this->user->id(),
            'resource_id' => $this->item->id(),
        ])->getContent();

        $this->assertCount(1, $basketItems);
    }

    /** @test */
    public function addmediaToBasketShouldStoreBasketForUSer()
    {
        $media = $this->item->primaryMedia();
        $this->dispatch('/s/test/basket/add/' . $media->id());

        $basketItems = $this->api()->search('basket_items', [
            'user_id' => $this->user->id(),
            'resource_id' => $media->id(),
        ])->getContent();

        $this->assertCount(1, $basketItems);
    }

    /** @test */
    public function addExistingItemShouldNotUpdateBasket()
    {
        $this->addToBasket($this->item);
        $this->dispatch('/s/test/basket/add/' . $this->item->id());

        $basketItems = $this->api()->search('basket_items', [
            'user_id' => $this->user->id(),
        ])->getContent();

        $this->assertCount(1, $basketItems);
    }

    /** @test */
    public function removeItemToBasketShouldRemoveBasketForUser()
    {
        $this->addToBasket($this->item);
        $this->dispatch('/s/test/basket/delete/' . $this->item->id());
        $this->assertResponseStatusCode(200);

        $basketItems = $this->api()->search('basket_items', [
            'user_id' => $this->user->id(),
        ])->getContent();

        $this->assertEmpty($basketItems);
    }

    /** @test */
    public function displayBasketShouldDisplayItems()
    {
        $this->addToBasket($this->item);
        $this->dispatch('/s/test/basket');
        $this->assertXPathQueryContentContains('//h4', 'First Item');
    }

    /** @test */
    public function displayBasketShouldDisplayMedia()
    {
        $media = $this->item->primaryMedia();
        $this->addToBasket($media);
        $this->dispatch('/s/test/basket');
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

    protected function addToBasket($resource)
    {
        $this->api()->create('basket_items', [
            'o:user_id' => $this->user->id(),
            'o:resource_id' => $resource->id(),
        ]);
    }
}

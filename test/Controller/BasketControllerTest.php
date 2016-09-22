<?php

namespace BasketTest\Controller;
use OmekaTestHelper\Controller\OmekaControllerTestCase;
use Zend\Session\Container;
use Omeka\Entity\User;




class BasketControllerTest extends OmekaControllerTestCase {
    protected $user,$item;


    public function setUp() {
        parent::setUp();
        $this->loginAsAdmin();
        $this->createSite('test','test');
        $this->item = $this->createUrlItem('First Item');
        $this->createUser('test@test.fr', 'toto');
        $this->resetApplication();
        $this->login('test@test.fr','toto');

    }
    public function tearDown() {

    }

    protected function createUrlItem($title) {
        if ($this->item)
            return $this->item;
        $api = $this->getApplicationServiceLocator()->get('Omeka\ApiManager');
        $media_url = 'http://farm8.staticflickr.com/7495/28077970085_4d976b3c96_z_d.jpg';

        return $api->create('items', [
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
                                      ]]);

    }


    protected function createSite($slug, $title)
    {
        $em = $this->getEntityManager();

        if (!empty($em->getRepository('Omeka\Entity\Site')->findOneBy(['slug'=> $slug])))
            return true;
        $response = $this->api()->create('sites', [
            'o:slug' => $slug,
            'o:theme' => 'default',
            'o:title' => $title,
            'o:is_public' => '1',
        ]);
        if ($response->isError()) {
            error_log('Failed creating site');
            error_log(var_export($response->getErrors(), true));
        }
        return $response->getContent();
    }

    protected function createUser($login,$password) {
        $em = $this->getEntityManager();
        if (!empty($em->getRepository('Omeka\Entity\User')->findOneBy(['email'=> $login])))
            return true;
        $response = $this->api()->create('users', [
            'o:email' => $login,
            'o:name' => $login,
            'o:role' => 'global_admin',
            'o:is_active' => true,
        ]);
        $user = $response->getContent()->jsonSerialize();
        $userEntity = $em->find('Omeka\Entity\User', $user['o:id']);
        $userEntity->setPassword($password);
        $this->user=$userEntity;
        $em->flush();
    }


   /** @test */
    public function  additemToBasketShouldStoreBasketForUSer() {
        $this->dispatch('/s/test/basket/additem/'.$this->item->getContent()->id());
        echo $this->getResponse()->getBody();
        $em = $this->getEntityManager();
        $this->assertNotEmpty($em->getRepository('Basket\Entity\Basket')->findOneBy(['user' => $this->user]));
    }

   /** @test */
    public function  addmediaToBasketShouldStoreBasketForUSer() {
        $em = $this->getEntityManager();

        $this->dispatch('/s/test/basket/addmedia/'.$this->item->getContent()->jsonSerialize()['o:media'][0]->id());
        echo $this->getResponse()->getBody();

        $this->assertNotEmpty($em->getRepository('Basket\Entity\Basket')->findOneBy(['user' => $this->user]));
    }

    /** @test */
    public function addExistingItemShouldNotUpdateBasket() {
        $this->additemToBasketShouldStoreBasketForUSer();
        $this->dispatch('/s/test/basket/additem/'.$this->item->getContent()->id());
        $em = $this->getEntityManager();
        $this->assertEquals(1, count($em->getRepository('Basket\Entity\Basket')->findBy(['user' => $this->user])));
    }


   /** @test */
    public function removeItemToBasketShouldRemoveBasketForUser() {
        $this->additemToBasketShouldStoreBasketForUSer();
        $this->dispatch('/s/test/basket/removeitem/'.$this->item->getContent()->id());
        echo $this->getResponse()->getBody();
        $em = $this->getEntityManager();
        $this->assertEmpty($em->getRepository('Basket\Entity\Basket')->findOneBy(['user' => $this->user]));
    }


   /** @test */
    public function deleteShouldRemoveBasket() {
        $this->additemToBasketShouldStoreBasketForUSer();
        $this->dispatch('/s/test/basket/delete/1');
        echo $this->getResponse()->getBody();
        $em = $this->getEntityManager();
        $this->assertEmpty($em->getRepository('Basket\Entity\Basket')->findOneBy(['user' => $this->user]));
    }

    /** @test */
    public function displayBasketShoudDisplayItems() {
        $this->additemToBasketShouldStoreBasketForUSer();
        $this->dispatch('/s/test/basket/show');
        echo $this->getResponse()->getBody();
        $this->assertXPathQueryContentContains('//h4' ,'First Item');

    }
    /** @test */
    public function displayBasketShoudDisplayMedia() {
        $this->dispatch('/s/test/basket/addmedia/'.$this->item->getContent()->jsonSerialize()['o:media'][0]->id());
        $this->dispatch('/s/test/basket/show');
        echo $this->getResponse()->getBody();
        $this->assertXPathQueryContentContains('//div' ,'media1');

    }


}
?>
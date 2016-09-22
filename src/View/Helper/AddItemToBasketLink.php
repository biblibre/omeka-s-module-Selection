<?php
namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Omeka\Module\Manager as ModuleManager;
use Omeka\Api\Representation\ItemRepresentation;
class AddItemToBasketLink extends AbstractHelper
{
    protected $entityManager,
        $authenticationService;

    public function __invoke($item,$id_div=0,$site=null)
    {
        $view = $this->getView();
        $action = 'add';
        if (!($user = $this->authenticationService->getIdentity()))
            return '';
        if (!$site)
            $site= $view->site;
        $class = "add_basket";
        $is_item = $item instanceof ItemRepresentation;

        $action = $is_item ?
            'additem' : 'addmedia';
        $id = $item->id();
        $text = $view->translate('Add to basket');


        if ($basket = ($is_item ? $this->basketExistsFor($user,$item) :
                       $this->basketExistsForMedia($user,$item))) {
            $class = "remove_basket";
            $action = 'delete';
            $id = $basket->getId();
            $text = $view->translate('Remove from basket');
        }


        return '<button  class="update_basket" onclick="updateBasket(\''
            .$view->url('site/basket',
                        [
                         'action' => $action,
                         'site-slug' => $site->slug(),
                        ]).'/'.$id.'/'.$id_div.'\','.$id_div.')" title="'.$text.'"><div class="'.$class.'"><span>'.$text.'</span></div></a>';




    }


    protected function basketExistsForMedia($user,$media) {
        $media_entity = $this->entityManager->getRepository('Omeka\Entity\Media')->find($media->id());
        return $this->entityManager->getRepository('Basket\Entity\Basket')
                    ->findOneBy(['media' => $media_entity,
                                 'user' => $user]);


    }

    protected function basketExistsFor($user,$item) {
        $item_entity = $this->entityManager->getRepository('Omeka\Entity\Item')->find($item->id());


        return $this->entityManager->getRepository('Basket\Entity\Basket')
                                   ->findOneBy(['item' => $item_entity,
                                                'user' => $user]);

    }

    public function setEntityManager($entityManager) {
        $this->entityManager=$entityManager;
    }

    public function setAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

}

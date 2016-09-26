<?php
namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Omeka\Module\Manager as ModuleManager;

class AddMediaToBasketLink extends AbstractHelper
{
    protected $entityManager,
        $authenticationService;

    public function __invoke($item)
    {
        $view = $this->getView();
        $action = 'add';
        if (!($user = $this->authenticationService->getIdentity()))
            return '';
        $class = "add_basket";
        $action = 'addmedia';
        $id = $item->id();
        $text = $view->translate('Add to basket');
        if ($basket = $this->basketExistsFor($user,$media)) {
            $class = "remove_basket";
            $action = 'delete';
            $id = $basket->getId();
            $text = $view->translate('Remove from basket');
        }


        return '<a href="'
            .$view->url(null,['controller' => 'basket',
                              'action' => $action,
                              'site-slug' => $view->site->slug(),
                              ]).'/'.$id
            .'" alt="'.$text.'"><div class="'.$class.'"><span>'.$text.'</span></div></a>';



    }
    protected function basketExistsFor($user,$media) {

        return $this->entityManager->getRepository('Basket\Entity\Basket')
                    ->findOneBy(['media' => $media,
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

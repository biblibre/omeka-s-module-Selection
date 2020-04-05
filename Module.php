<?php
namespace Basket;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;

/**
 * Basket.
 *
 * @copyright Biblibre, 2016
 * @copyright Daniel Berthereau 2019-2020
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    // Guest is an optional dependency, not a required one.
    // protected $dependency = 'Guest';

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        // Since Omeka 1.4, modules are ordered, so Guest come after Basket.
        // See \Guest\Module::onBootstrap().
        if (!$acl->hasRole('guest')) {
            $acl->addRole('guest');
        }

        $roles = $acl->getRoles();
        $acl
            ->allow(
                $roles,
                [
                    Entity\BasketItem::class,
                    Api\Adapter\BasketItemAdapter::class,
                    'Basket\Controller\Site\Basket',
                    'Basket\Controller\Site\GuestBoard',
                ]
        );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'handleViewShowAfter']
        );

        // Guest integration.
        $sharedEventManager->attach(
            \Guest\Controller\Site\GuestController::class,
            'guest.widgets',
            [$this, 'handleGuestWidgets']
        );
    }

    public function handleViewShowAfter(Event $event)
    {
        $view = $event->getTarget();
        echo $view->partial('common/basket-item');
    }

    public function handleGuestWidgets(Event $event)
    {
        $widgets = $event->getParam('widgets');
        $helpers = $this->getServiceLocator()->get('ViewHelperManager');
        $translate = $helpers->get('translate');
        $partial = $helpers->get('partial');

        $widget = [];
        $widget['label'] = $translate('Basket'); // @translate
        $widget['content'] = $partial('guest/site/guest/widget/basket');
        $widgets['basket'] = $widget;

        $event->setParam('widgets', $widgets);
    }
}

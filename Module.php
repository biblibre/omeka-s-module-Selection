<?php
namespace Selection;

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
 * Selection.
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

        // Since Omeka 1.4, modules are ordered, so Guest come after Selection.
        // See \Guest\Module::onBootstrap().
        if (!$acl->hasRole('guest')) {
            $acl->addRole('guest');
        }

        $roles = $acl->getRoles();
        $acl
            ->allow(
                $roles,
                [
                    Entity\SelectionItem::class,
                    Api\Adapter\SelectionItemAdapter::class,
                    'Selection\Controller\Site\Selection',
                    'Selection\Controller\Site\GuestBoard',
                ]
        );
    }

    protected function postInstall()
    {
        // Upgrade from old module Basket if any.
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('Basket');
        if ($module
            && version_compare($module->getIni('version'), '0.2.0', '>')
        ) {
            // Check if Basket was really installed.
            try {
                $connection->fetchAll('SELECT id FROM basket_item LIMIT 1;');
                // So upgrade Basket.
                $filepath = $this->modulePath() . '/data/scripts/upgrade_from_basket.php';
                require_once $filepath;
                return;
            } catch (\Exception $e) {
            }
        }
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
        echo $view->partial('common/selection-item');
    }

    public function handleGuestWidgets(Event $event)
    {
        $widgets = $event->getParam('widgets');
        $helpers = $this->getServiceLocator()->get('ViewHelperManager');
        $translate = $helpers->get('translate');
        $partial = $helpers->get('partial');

        $widget = [];
        $widget['label'] = $translate('Selection'); // @translate
        $widget['content'] = $partial('guest/site/guest/widget/selection');
        $widgets['selection'] = $widget;

        $event->setParam('widgets', $widgets);
    }
}

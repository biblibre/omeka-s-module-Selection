<?php declare(strict_types=1);

namespace Selection;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;

/**
 * Selection.
 *
 * @copyright Biblibre, 2016
 * @copyright Daniel Berthereau 2019-2023
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    // Guest is an optional dependency, not a required one.
    /*
    protected $dependencies = [
        'Guest',
    ];
    */

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');

        // Since Omeka 1.4, modules are ordered, so Guest come before Selection.
        $roles = $acl->getRoles();

        $acl
            ->allow(
                $roles,
                [
                    Entity\Selection::class,
                    Entity\SelectionResource::class,
                    Api\Adapter\SelectionAdapter::class,
                    Api\Adapter\SelectionResourceAdapter::class,
                    'Selection\Controller\Site\Selection',
                    'Selection\Controller\Site\GuestBoard',
                ]
            )
            // This right is checked in controller in order to avoid to check
            // the site here.
            ->allow(
                null,
                'Selection\Controller\Site\Selection'
            )
        ;
    }

    protected function postInstall(): void
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('Basket');
        if ($module) {
            $message = new PsrMessage(
                'The module Basket is present. Upgrade from it was removed since version 3.4.7. Install the previous version if you want to upgrade it.', // @translate
            );
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning($message);
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'handleViewShowAfter']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\ItemSet',
            'view.show.after',
            [$this, 'handleViewShowAfter']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Media',
            'view.show.after',
            [$this, 'handleViewShowAfter']
        );

        // Guest integration.
        $sharedEventManager->attach(
            \Guest\Controller\Site\GuestController::class,
            'guest.widgets',
            [$this, 'handleGuestWidgets']
        );

        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
    }

    public function handleViewShowAfter(Event $event): void
    {
        $view = $event->getTarget();
        $siteSetting = $view->getHelperPluginManager()->get('siteSetting');

        $user = $view->identity();
        $allowVisitor = $siteSetting('selection_visitor_allow', true);
        if (!$user && !$allowVisitor) {
            return;
        }

        // In Omeka S v4, if the selection is set in the view, don't add it.
        $view = $event->getTarget();
        if (version_compare(\Omeka\Module::VERSION, '4', '>=')) {
            /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
            $resource = $view->resource;
            $services = $this->getServiceLocator();
            $currentTheme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
            $blockLayoutManager = $services->get('Omeka\ResourcePageBlockLayoutManager');
            $resourcePageBlocks = $blockLayoutManager->getResourcePageBlocks($currentTheme);
            foreach ($resourcePageBlocks[$resource->resourceName()] ?? [] as $blocks) {
                if (in_array('selection', $blocks)) {
                    return;
                }
            }
        }

        $selectionContainer = $this->getServiceLocator()->get('ControllerPluginManager')->get('selectionContainer');
        $selectionContainer();

        echo $view->partial('common/selection-resource');
    }

    public function handleGuestWidgets(Event $event): void
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

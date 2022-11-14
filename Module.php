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
 * @copyright Daniel Berthereau 2019-2022
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    // Guest is an optional dependency, not a required one.
    // protected $dependency = 'Guest';

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
        // Upgrade from old module Basket if any.
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('Basket');
        if ($module
            && version_compare($module->getIni('version') ?? '', '0.2.0', '>')
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

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $controllers = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\ItemSet',
            'Omeka\Controller\Site\Media',
        ];
        foreach ($controllers as $controller) {
            $sharedEventManager->attach(
                $controller,
                'view.show.after',
                [$this, 'handleViewShowAfter']
            );
        }

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

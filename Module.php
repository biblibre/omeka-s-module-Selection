<?php declare(strict_types=1);

namespace Selection;

if (!class_exists('Common\TraitModule', false)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\Stdlib\PsrMessage;
use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Module\AbstractModule;

/**
 * Selection.
 *
 * @copyright Biblibre, 2016
 * @copyright Daniel Berthereau 2019-2025
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        // Since Omeka 1.4, modules are ordered, so Guest come before Selection.
        $roles = $acl->getRoles();

        $acl
            ->allow(
                null,
                [
                    'Selection\Controller\Site\Selection',
                ]
            )
            ->allow(
                $roles,
                [
                    Entity\Selection::class,
                    Entity\SelectionResource::class,
                    Api\Adapter\SelectionAdapter::class,
                    Api\Adapter\SelectionResourceAdapter::class,
                    'Selection\Controller\Site\Guest',
                ]
            )
        ;
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translate = $services->get('ControllerPluginManager')->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.69')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.69'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
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
        // Display block in resources pages for old themes.
        // The site is not set yet, so checks are done in method.
        foreach ([
            'selection_placement_button' => 'handleShowSelectionButton',
            'selection_placement_list' => 'handleShowSelectionList',
        ] as $method) {
            $sharedEventManager->attach(
                'Omeka\Controller\Site\Item',
                'view.show.before',
                [$this, $method]
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\Media',
                'view.show.before',
                [$this, $method]
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\ItemSet',
                'view.show.before',
                [$this, $method]
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\Item',
                'view.show.after',
                [$this, $method]
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\Media',
                'view.show.after',
                [$this, $method]
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\ItemSet',
                'view.show.after',
                [$this, $method]
            );
        }

        // No need to listen user.logout: session is automatically destroyed.

        // Guest integration.
        $sharedEventManager->attach(
            \Guest\Controller\Site\GuestController::class,
            'guest.widgets',
            [$this, 'handleGuestWidgets']
        );

        // Site config.
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
    }

    public function handleShowSelectionButton(Event $event): void
    {
        /**
         * @var \Omeka\Settings\SiteSettings $siteSettings
         * @var \Omeka\Entity\User $user
         * @var \Laminas\View\Renderer\PhpRenderer $view
         * @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource
         * @see \Selection\View\Helper\SelectionButton
         */

        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');

        $user = $services->get('Omeka\AuthenticationService')->getIdentity();
        $disableAnonymous = (bool) $siteSettings->get('selection_disable_anonymous');
        if ($disableAnonymous && !$user) {
            return;
        }

        $view = $event->getTarget();
        $resource = $view->resource;
        $resourceName = $resource->resourceName();

        $selectables = $siteSettings->get('selection_selectable_resources', ['items']);
        if (!in_array($resourceName, $selectables)) {
            return;
        }

        $placements = $siteSettings->get('selection_placement_button', []);
        if (in_array('before/' . $resourceName, $placements)
            || in_array('after/' . $resourceName, $placements)
        ) {
            echo $view->selectionButton($resource);
        }
    }

    public function handleShowSelectionList(Event $event): void
    {
        /**
         * @var \Omeka\Settings\SiteSettings $siteSettings
         * @var \Omeka\Entity\User $user
         * @var \Laminas\View\Renderer\PhpRenderer $view
         * @see \Selection\View\Helper\SelectionList
         */

        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');

        $user = $services->get('Omeka\AuthenticationService')->getIdentity();
        $disableAnonymous = (bool) $siteSettings->get('selection_disable_anonymous');
        if ($disableAnonymous && !$user) {
            return;
        }

        $view = $event->getTarget();
        $resource = $view->resource;
        $resourceName = $resource->resourceName();

        $placements = $siteSettings->get('selection_placement_list', []);
        if (in_array('before/' . $resourceName, $placements)
            || in_array('after/' . $resourceName, $placements)
        ) {
            echo $view->selectionList();
        }
    }

    public function handleGuestWidgets(Event $event): void
    {
        $services = $this->getServiceLocator();
        $plugins = $services->get('ViewHelperManager');
        $partial = $plugins->get('partial');
        $translate = $plugins->get('translate');
        $siteSettings = $services->get('Omeka\Settings\Site');

        $widget = [];
        $widget['label'] = $siteSettings->get('selection_label', $translate('Selection')); // @translate
        $widget['content'] = $partial('guest/site/guest/widget/selection');

        $widgets = $event->getParam('widgets');
        $widgets['selection'] = $widget;
        $event->setParam('widgets', $widgets);
    }
}

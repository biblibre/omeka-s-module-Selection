<?php declare(strict_types=1);

namespace Selection;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Module\AbstractModule;
use Omeka\Stdlib\Message;

/**
 * Selection.
 *
 * @copyright Biblibre, 2016
 * @copyright Daniel Berthereau 2019-2025
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{

    const NAMESPACE = __NAMESPACE__;

    /**
     * Get the config of the current module.
     *
     * @return array
     */
    public function getConfig()
    {
        return include OMEKA_PATH . '/modules/' . static::NAMESPACE . '/config/module.config.php';
    }

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

    public function install(ServiceLocatorInterface $serviceLocator): void
    {
        $translator = $serviceLocator->get('ControllerPluginManager')->get('translate');
        $this->setServiceLocator($serviceLocator);

        $sqlFile = OMEKA_PATH . '/modules/' . static::NAMESPACE . '/data/install/schema.sql';
        if (!$this->checkNewTablesFromFile($sqlFile)) {
            $message = new Message(
                $translator->translate('This module cannot install its tables, because they exist already. Try to remove them first.') // @translate
            );
            throw new ModuleCannotInstallException((string) $message);
        }

        $this->execSqlFromFile($sqlFile);

        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $serviceLocator->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('Basket');
        if ($module) {
            $message = new Message(
                'The module Basket is present. Upgrade from it was removed since version 3.4.7. Install the previous version if you want to upgrade it.', // @translate
            );
            $messenger = $serviceLocator->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning($message);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator): void
    {
        $this->setServiceLocator($serviceLocator);
        $this->execSqlFromFile(OMEKA_PATH . '/modules/' . static::NAMESPACE . '/data/install/uninstall.sql');
    }

    /**
     * Check if new tables can be installed and remove empty existing tables.
     *
     * If a new table exists and is empty, it is removed, because it is probably
     * related to a broken installation.
     */
    protected function checkNewTablesFromFile(string $filepath): bool
    {
        if (!file_exists($filepath) || !filesize($filepath) || !is_readable($filepath)) {
            return true;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        // Get the list of all tables.
        $tables = $connection->executeQuery('SHOW TABLES;')->fetchFirstColumn();

        $dropTables = [];

        // Use single statements for execution.
        // See core commit #2689ce92f.
        $sql = file_get_contents($filepath);
        $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($sqls as $sql) {
            if (mb_strtoupper(mb_substr($sql, 0, 13)) !== 'CREATE TABLE ') {
                continue;
            }
            $table = trim(strtok(mb_substr($sql, 13), '('), "\"`' \n\r\t\v\0");
            if (!in_array($table, $tables)) {
                continue;
            }
            $result = $connection->executeQuery("SELECT * FROM `$table` LIMIT 1;")->fetchOne();
            if ($result !== false) {
                return false;
            }
            $dropTables[] = $table;
        }

        if (count($dropTables)) {
            // No check: if a table cannot be removed, an exception will be
            // thrown later.
            foreach ($dropTables as $table) {
                $connection->executeStatement("SET FOREIGN_KEY_CHECKS=0; DROP TABLE `$table`;");
            }

            $message = new Message(sprintf(
                'The module removed tables "%s" from a previous broken install.', // @translate
                implode('", "', $dropTables)
            ));
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning($message);
        }

        return true;
    }

    /**
     * Execute a sql from a file.
     *
     * @param string $filepath
     * @return int|null
     */
    protected function execSqlFromFile(string $filepath): ?int
    {
        if (!file_exists($filepath) || !filesize($filepath) || !is_readable($filepath)) {
            return null;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        // Use single statements for execution.
        // See core commit #2689ce92f.
        $sql = file_get_contents($filepath);
        $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($sqls as $sql) {
            $result = $connection->executeStatement($sql);
        }

        return $result;
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

    public function handleSiteSettings(Event $event): void
    {
        $this->handleAnySettings($event, 'site_settings');
    }

        /**
     * Prepare a settings fieldset.
     *
     * @param Event $event
     * @param string $settingsType
     * @return \Laminas\Form\Fieldset|null
     */
    protected function handleAnySettings(Event $event, string $settingsType): ?\Laminas\Form\Fieldset
    {
        global $globalNext;

        $services = $this->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');

        // TODO Check fieldsets in the config of the module.
        $settingFieldsets = [
            // 'config' => static::NAMESPACE . '\Form\ConfigForm',
            'settings' => static::NAMESPACE . '\Form\SettingsFieldset',
            'site_settings' => static::NAMESPACE . '\Form\SiteSettingsFieldset',
            'user_settings' => static::NAMESPACE . '\Form\UserSettingsFieldset',
        ];
        if (!isset($settingFieldsets[$settingsType])
            || !$formElementManager->has($settingFieldsets[$settingsType])
        ) {
            return null;
        }

        $settingsTypes = [
            // 'config' => 'Omeka\Settings',
            'settings' => 'Omeka\Settings',
            'site_settings' => 'Omeka\Settings\Site',
            'user_settings' => 'Omeka\Settings\User',
        ];

        $settings = $services->get($settingsTypes[$settingsType]);

        switch ($settingsType) {
            case 'settings':
                $id = null;
                break;
            case 'site_settings':
                $site = $services->get('ControllerPluginManager')->get('currentSite');
                $id = $site()->id();
                break;
            case 'user_settings':
                /** @var \Laminas\Router\Http\RouteMatch $routeMatch */
                $routeMatch = $services->get('Application')->getMvcEvent()->getRouteMatch();
                $id = $routeMatch->getParam('id');
                break;
            default:
                return null;
        }

        // Simplify config of settings.
        if (empty($globalNext)) {
            $globalNext = true;
            $ckEditorHelper = $services->get('ViewHelperManager')->get('ckEditor');
            $ckEditorHelper();
        }

        // Allow to use a form without an id, for example to create a user.
        if ($settingsType !== 'settings' && !$id) {
            $data = [];
        } else {
            $this->initDataToPopulate($settings, $settingsType, $id);
            $data = $this->prepareDataToPopulate($settings, $settingsType);
            if ($data === null) {
                return null;
            }
        }

        $space = strtolower(static::NAMESPACE);

        /**
         * @var \Laminas\Form\Fieldset $fieldset
         * @var \Laminas\Form\Form $form
         */
        $fieldset = $formElementManager->get($settingFieldsets[$settingsType]);
        $fieldset->setName($space);
        $form = $event->getTarget();

        // In Omeka S v4, settings  are no more managed with fieldsets, but with
        // "element groups", to de-correlate setting storage and display.

        // Handle form loading.
        // There are default element groups:
        // - Settings:
        //   - general
        //   - security
        // - Site settings:
        //   - general
        //   - language
        //   - browse
        //   - show
        //   - search
        // - User settings: fieldsets "user-information"; "user-settings", "change-password"
        // and "edit-keys" are kept, but groups are added to fieldset "user-settings":
        //   - columns
        //   - browse_defaults
        // There are two possibilities to manage module features in settings:
        // - make each module a group
        // - or create new groups for each set of features: resource metadata,
        // site and pages params, viewers, contributions, public browse, public
        // resource, jobs to run…
        // The second way is more readable for admin, but in most of the cases,
        // features are very different, so there will be a group by module
        // anyway. Similar to module config, but config is not end-user friendly
        // (multiple pages).
        // So for now, let each module choose during upgrade to v4.
        // Nevertheless, to use group feature smartly, it is recommended to use
        // a generic list of groups similar to the site settings ones.
        // Maybe sub-groups may be interesting, but not possible for now.
        // In practice, there is a new option to set in each fieldset the group
        // where params are displayed.

        // TODO Order element groups.
        // TODO Move main params to site settings and user settings.

        $fieldsetElementGroups = $fieldset->getOption('element_groups');
        if ($fieldsetElementGroups) {
            $form->setOption('element_groups', array_merge($form->getOption('element_groups') ?: [], $fieldsetElementGroups));
        }

        // The user view is managed differently.
        if ($settingsType === 'user_settings') {
            // This process allows to save first level elements automatically.
            // @see \Omeka\Controller\Admin\UserController::editAction()
            $formFieldset = $form->get('user-settings');
            foreach ($fieldset->getFieldsets() as $subFieldset) {
                $formFieldset->add($subFieldset);
            }
            foreach ($fieldset->getElements() as $element) {
                $formFieldset->add($element);
            }
            $formFieldset->populateValues($data);
            $fieldset = $formFieldset;
        } else {
            // Allow to save data and to manage modules compatible with
            // Omeka S v3 and v4.
            //
            // In Omeka S v4, settings are no more de-nested, next to the new
            // "element group" feature, where default elements are attached
            // directly to the main form with a fake fieldset (not managed by
            // laminas), without using the formCollection() option.
            // So un-de-nested params are checked, but no more automatically
            // saved.
            // And when data is populated, it is not possible to determinate
            // directly if the form is valid or not as a whole, because the
            // check is done after the filling inside the controller.
            // To manage this new feature, either remove fieldsets and attach
            // elements directly to the form, either save elements via event
            // "view.browse.before", where the form is available.
            // This second way is simpler to manage modules compatible with
            // Omeka S v3 and v4, but it is not possible because there is a
            // redirect in the controller when post is successfull.
            // So append all elements and sub-fieldsets on the root of the form.
            if (version_compare(\Omeka\Module::VERSION, '4', '<')) {
                $form->add($fieldset);
                $form->get($space)->populateValues($data);
            } else {
                foreach ($fieldset->getFieldsets() as $subFieldset) {
                    $form->add($subFieldset);
                }
                foreach ($fieldset->getElements() as $element) {
                    $form->add($element);
                }
                $form->populateValues($data);
                $fieldset = $form;
            }
        }

        return $fieldset;
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        return $this->getConfigFormAuto($renderer);
    }

    protected function getConfigFormAuto(PhpRenderer $renderer): ?string
    {
        $services = $this->getServiceLocator();

        $formManager = $services->get('FormElementManager');
        $formClass = static::NAMESPACE . '\Form\ConfigForm';
        if (!$formManager->has($formClass)) {
            return null;
        }

        // Simplify config of modules.
        $renderer->ckEditor();

        $settings = $services->get('Omeka\Settings');

        $this->initDataToPopulate($settings, 'config');
        $data = $this->prepareDataToPopulate($settings, 'config');
        if ($data === null) {
            return null;
        }

        $form = $formManager->get($formClass);
        $form->init();
        $form->setData($data);
        $form->prepare();
        return $renderer->formCollection($form);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        return $this->handleConfigFormAuto($controller);
    }

    protected function handleConfigFormAuto(AbstractController $controller): bool
    {
        $defaultSettings = $this->getModuleConfig('config');
        if (!$defaultSettings) {
            return true;
        }

        $services = $this->getServiceLocator();
        $formManager = $services->get('FormElementManager');
        $formClass = static::NAMESPACE . '\Form\ConfigForm';
        if (!$formManager->has($formClass)) {
            return true;
        }

        $params = $controller->getRequest()->getPost();

        $form = $formManager->get($formClass);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $params = $form->getData();

        $settings = $services->get('Omeka\Settings');
        $params = array_intersect_key($params, $defaultSettings);
        foreach ($params as $name => $value) {
            $settings->set($name, $value);
        }
        return true;
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

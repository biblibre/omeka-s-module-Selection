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
use Omeka\Settings\SettingsInterface;

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
        return include OMEKA_PATH . '/modules/Selection/config/module.config.php';
    }

    /** 
     * Get the site settings of the current module.
     *
     * The config of the module is not merged with Omeka main config for
     * services before the end of install. So it is locally cached to avoid to
     * reload and reprocess the file. It is used to manage the forms too.
     */
    protected function getModuleSiteConfig(): ?array
    {
        static $localConfig;

        if (!isset($localConfig)) {
            $localConfig = $this->getConfig();
            $localConfig = $localConfig['selection'] ?? false;
        }

        if ($localConfig === false) {
            return null;
        }

        return $localConfig['site_settings'] ?? [];
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

        $sqlFile = OMEKA_PATH . '/modules/Selection/data/install/schema.sql';
        if (!$this->checkNewTablesFromFile($sqlFile)) {
            $message = new Message(
                $translator->translate('This module cannot install its tables, because they exist already. Try to remove them first.') // @translate
            );
            throw new ModuleCannotInstallException((string) $message);
        }

        $this->execSqlFromFile($sqlFile);

        $defaultSettings = $this->getModuleSiteConfig();

        // Adds settings needed by module
        if ($defaultSettings)
        {
            $settings = $this->getServiceLocator()->get('Omeka\Settings\Site');
            $api = $this->getServiceLocator()->get('Omeka\ApiManager');
            $ids = $api->search('sites', [], ['returnScalar' => 'id'])->getContent();
            foreach ($ids as $id) {
                $settings->setTargetId($id);
                foreach ($defaultSettings as $name => $value) {
                    $settings->set($name, $value);
                }
            }
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator): void
    {
        $this->setServiceLocator($serviceLocator);
        $this->execSqlFromFile(OMEKA_PATH . '/modules/Selection/data/install/uninstall.sql');
        
        $defaultSettings = $this->getModuleSiteConfig();

        // Delete settings added by module, optional
        if ($defaultSettings)
        {
            $settings = $serviceLocator->get('Omeka\Settings\Site');
            $api = $this->getServiceLocator()->get('Omeka\ApiManager');
            $ids = $api->search('sites', [], ['returnScalar' => 'id'])->getContent();
            foreach ($ids as $id) {
                $settings->setTargetId($id);
                foreach ($defaultSettings as $name => $value) {
                    $settings->delete($name);
                }
            }
        }
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

    public function handleSiteSettings(Event $event): ?\Laminas\Form\Fieldset
    {
        global $globalNext;

        $services = $this->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');

        $settings = $services->get('Omeka\Settings\Site');

        $site = $services->get('ControllerPluginManager')->get('currentSite');


        // Simplify config of settings.
        if (empty($globalNext)) {
            $globalNext = true;
            $ckEditorHelper = $services->get('ViewHelperManager')->get('ckEditor');
            $ckEditorHelper();
        }

        $this->initDataToPopulate($settings, $site()->id());
        $data = $this->prepareDataToPopulate($settings);
        if ($data === null) {
            return null;
        }


        /**
         * @var \Laminas\Form\Fieldset $fieldset
         * @var \Laminas\Form\Form $form
         */
        $fieldset = $formElementManager->get('Selection\Form\SiteSettingsFieldset');
        $fieldset->setName('selection');
        $form = $event->getTarget();

        $fieldsetElementGroups = $fieldset->getOption('element_groups');
        if ($fieldsetElementGroups) {
            $form->setOption('element_groups', array_merge($form->getOption('element_groups') ?: [], $fieldsetElementGroups));
        }

        foreach ($fieldset->getFieldsets() as $subFieldset) {
            $form->add($subFieldset);
        }
        foreach ($fieldset->getElements() as $element) {
            $form->add($element);
        }
        $form->populateValues($data);
        $fieldset = $form;

        return $fieldset;
    }

    /**
     * Initialize each site settings.
     *
     * If the default settings were never registered, it means an incomplete
     * config, install or upgrade, or a new site or a new user. In all cases,
     * check it and save default value first.
     *
     * @param SettingsInterface $settings
     * @param int $id Site id or user id.
     * @param bool True if processed.
     */
    protected function initDataToPopulate(SettingsInterface $settings, $id = null): bool
    {
        // This method is not in the interface, but is set for config, site and
        // user settings.
        if (!method_exists($settings, 'getTableName')) {
            return false;
        }

        $defaultSettings = $this->getModuleSiteConfig();
        if (!$defaultSettings) {
            return false;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');
        if ($id) {
            if (!method_exists($settings, 'getTargetIdColumnName')) {
                return false;
            }
            $sql = sprintf('SELECT id, value FROM %s WHERE %s = :target_id', $settings->getTableName(), $settings->getTargetIdColumnName());
            $stmt = $connection->executeQuery($sql, ['target_id' => $id]);
        } else {
            $sql = sprintf('SELECT id, value FROM %s', $settings->getTableName());
            $stmt = $connection->executeQuery($sql);
        }

        $currentSettings = $stmt->fetchAllKeyValue();

        // Skip settings that are arrays, because the fields "multi-checkbox"
        // and "multi-select" are removed when no value are selected, so it's
        // not possible to determine if it's a new setting or an old empty
        // setting currently. So fill them via upgrade in that case or fill the
        // values.
        // TODO Find a way to save empty multi-checkboxes and multi-selects (core fix).
        $defaultSettings = array_filter($defaultSettings, fn ($v) => !is_array($v));
        $missingSettings = array_diff_key($defaultSettings, $currentSettings);

        foreach ($missingSettings as $name => $value) {
            $settings->set($name, $value);
        }

        return true;
    }

    /**
     * Prepare data for a form or a fieldset.
     *
     *
     * @todo Use form methods to populate.
     *
     * @param SettingsInterface $settings
     * @return array|null
     */
    protected function prepareDataToPopulate(SettingsInterface $settings): ?array
    {
        // TODO Explain this feature.
        // Use isset() instead of empty() to give the possibility to display a
        // specific form.
        $defaultSettings = $this->getModuleSiteConfig();
        if ($defaultSettings === null) {
            return null;
        }

        $data = [];
        foreach ($defaultSettings as $name => $value) {
            $val = $settings->get($name, is_array($value) ? [] : null);
            $data[$name] = $val;
        }
        return $data;
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

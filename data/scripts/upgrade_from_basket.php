<?php declare(strict_types=1);
namespace Selection;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Omeka\Module\Manager $moduleManager
 * @var \Omeka\Settings\Settings $settings
 */
$connection = $services->get('Omeka\Connection');
$settings = $services->get('Omeka\Settings');
$moduleManager = $services->get('Omeka\ModuleManager');
$basketModule = $moduleManager->getModule('Basket');
$messenger = $services->get('ControllerPluginManager')->get('messenger');

if (!$basketModule) {
    return;
}

$oldVersion = $basketModule->getIni('version');
if (version_compare($oldVersion, '0.2.0', '<=')) {
    $message = new \Omeka\Stdlib\Message(
        'The version of the module Basket should be at least %s.', // @translate
        '0.2.1'
    );
    $messenger->addWarning($message);
    return;
}

// Apply all upgrades since 0.2.1.
// None.

// Check if Basket was really installed.
try {
    $connection->fetchAll('SELECT id FROM basket_item LIMIT 1;');
} catch (\Exception $e) {
    return;
}

// Rename tables for module Basket.

// Copy is used instead of rename. The tables are created during install.
$sql = <<<SQL
INSERT selection_item (id, user_id, resource_id, created)
SELECT id, user_id, resource_id, created FROM basket_item;

# Uninstall the module Basket.
DROP TABLE IF EXISTS `basket_item`;

DELETE FROM module WHERE id = "Basket";
SQL;

$sqls = array_filter(array_map('trim', explode(";\n", $sql)));
foreach ($sqls as $sql) {
    $connection->exec($sql);
}

// Convert the settings.
// None.

$message = new \Omeka\Stdlib\Message(
    'The module Basket was upgraded by module Selection and uninstalled.' // @translate
);
$messenger->addWarning($message);

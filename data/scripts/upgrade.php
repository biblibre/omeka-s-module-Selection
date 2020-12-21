<?php declare(strict_types=1);
namespace Selection;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$config = require dirname(__DIR__, 2) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$space = strtolower(__NAMESPACE__);

if (version_compare($oldVersion, '3.3.4.1', '<')) {
    $sqls = <<<'SQL'
ALTER TABLE `selection_item` DROP FOREIGN KEY FK_CB95FBE3A76ED395;
ALTER TABLE `selection_item`
CHANGE `user_id` `owner_id` int(11) NOT NULL AFTER `id`,
RENAME TO `selection_resource`;
ALTER TABLE `selection_resource` ADD CONSTRAINT FK_6B34815E7E3C61F9 FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
SQL;
    foreach (explode(";\n", $sqls) as $sql) {
        $connection->exec($sql);
    }
}

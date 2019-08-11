<?php
namespace Basket;

/**
 * @var Module $this
 * @var \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$space = strtolower(__NAMESPACE__);

if (version_compare($oldVersion, '0.2.0', '<')) {
    $connection->exec('
        RENAME TABLE basket TO basket_item
    ');
    $connection->exec('
        ALTER TABLE basket_item
        ADD COLUMN resource_id INT NULL AFTER media_id
    ');
    $connection->exec('
        UPDATE basket_item
        SET resource_id = COALESCE(item_id, media_id)
    ');
    $connection->exec('
        ALTER TABLE basket_item
        MODIFY COLUMN resource_id INT NOT NULL
    ');
    $connection->exec('
        ALTER TABLE basket_item
        DROP COLUMN item_id,
        DROP COLUMN media_id
    ');
    $connection->exec('
        ALTER TABLE basket_item
        MODIFY COLUMN user_id INT NOT NULL
        ADD INDEX IDX_D4943C2BA76ED395 (user_id),
        ADD INDEX IDX_D4943C2B89329D25 (resource_id),
        ADD CONSTRAINT FK_D4943C2BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
        ADD CONSTRAINT FK_D4943C2B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE
    ');
}

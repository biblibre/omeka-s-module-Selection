<?php declare(strict_types=1);

namespace Selection;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$settings = $services->get('Omeka\Settings');
$config = require dirname(__DIR__, 2) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$space = strtolower(__NAMESPACE__);

if (version_compare($oldVersion, '3.3.4.1', '<')) {
    $sqls = '';
    $sm = $connection->getSchemaManager();
    $keys = [
        'FK_CB95FBE3A76ED395',
        'selection_resource_ibfk_1',
        'FK_6B34815E7E3C61F9',
        'FK_6B34815EE48EFE78',
        'FK_CB95FBE389329D25',
        'idx_cb95fbe3a76ed395',
        'idx_cb95fbe389329d25',
    ];
    $keys = array_map('strtoupper', $keys);
    $foreignKeys = $sm->listTableForeignKeys('selection_item');
    foreach ($foreignKeys as $foreignKey) {
        if ($foreignKey && in_array(strtoupper($foreignKey->getName()), $keys)) {
            $sqls .= "ALTER TABLE `selection_item` DROP FOREIGN KEY {$foreignKey->getName()};\n";
        }
    }
    $sqls .= <<<'SQL'
ALTER TABLE `selection_item`
CHANGE `user_id` `owner_id` int(11) NOT NULL AFTER `id`,
RENAME TO `selection_resource`;
SQL;

    $sm = $connection->getSchemaManager();
    $foreignKeys = $sm->listTableForeignKeys('selection_resource');
    foreach ($foreignKeys as $foreignKey) {
        if ($foreignKey && in_array(strtoupper($foreignKey->getName()), $keys)) {
            $sqls .= "ALTER TABLE `selection_resource` DROP FOREIGN KEY {$foreignKey->getName()};\n";
        }
    }
    $indexes = $sm->listTableIndexes('selection_resource');
    foreach ($indexes as $index) {
        if ($index && in_array(strtoupper($index->getName()), $keys)) {
            $sql .= 'DROP INDEX ' . $index->getName() . ' ON `selection_resource`;' . PHP_EOL;
        }
    }
    foreach (explode(";\n", $sqls) as $sql) {
        $connection->exec($sql);
    }

    $sqls = <<<'SQL'
CREATE TABLE `selection` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `owner_id` INT NOT NULL,
    `label` VARCHAR(190) NOT NULL,
    `comment` LONGTEXT DEFAULT NULL,
    `created` DATETIME NOT NULL,
    INDEX IDX_96A50CD77E3C61F9 (`owner_id`),
    UNIQUE INDEX UNIQ_96A50CD77E3C61F9EA750E8 (`owner_id`, `label`),
    PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE `selection` ADD CONSTRAINT FK_96A50CD77E3C61F9 FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `selection_resource` ADD `selection_id` INT DEFAULT NULL AFTER `resource_id`;
CREATE UNIQUE INDEX UNIQ_6B34815E89329D25E48EFE78 ON `selection_resource` (`resource_id`, `selection_id`);
CREATE INDEX IDX_6B34815E7E3C61F9 ON `selection_resource` (`owner_id`);
CREATE INDEX IDX_6B34815E89329D25 ON `selection_resource` (`resource_id`);
CREATE INDEX IDX_6B34815EE48EFE78 ON `selection_resource` (`selection_id`);
ALTER TABLE selection_resource ADD CONSTRAINT FK_6B34815E7E3C61F9 FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE selection_resource ADD CONSTRAINT FK_6B34815EE48EFE78 FOREIGN KEY (`selection_id`) REFERENCES `selection` (`id`) ON DELETE CASCADE;
ALTER TABLE selection_resource ADD CONSTRAINT FK_CB95FBE389329D25 FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE CASCADE;
ALTER TABLE selection_resource ADD CONSTRAINT selection_resource_ibfk_1 FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);
SQL;
    foreach (explode(";\n", $sqls) as $sql) {
        $connection->exec($sql);
    }
}

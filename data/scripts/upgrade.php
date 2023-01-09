<?php declare(strict_types=1);

namespace Selection;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

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
            $sql .= 'DROP INDEX ' . $index->getName() . ' ON `selection_resource`;' . "\n";
        }
    }
    foreach (explode(";\n", $sqls) as $sql) {
        $connection->executeStatement($sql);
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
        $connection->executeStatement($sql);
    }
}

if (version_compare($oldVersion, '3.3.4.2', '<')) {
    $sm = $connection->getSchemaManager();
    $indexes = $sm->listTableIndexes('selection');
    foreach ($indexes as $index) {
        if ($index && strtoupper($index->getName()) === 'UNIQ_96A50CD77E3C61F9EA750E8') {
            $sql = 'DROP INDEX UNIQ_96A50CD77E3C61F9EA750E8 ON `selection`;';
            $connection->executeStatement($sql);
            break;
        }
    }

    $sqls = <<<'SQL'
ALTER TABLE `selection`
ADD `is_public` TINYINT(1) DEFAULT 0 NOT NULL AFTER `owner_id`,
ADD `is_dynamic` TINYINT(1) DEFAULT 0 NOT NULL AFTER `is_public`,
ADD `search_query` LONGTEXT DEFAULT NULL AFTER `comment`,
ADD `modified` DATETIME DEFAULT NULL AFTER `created`;

CREATE UNIQUE INDEX UNIQ_96A50CD77E3C61F9EA750E85D978C7C ON `selection` (`owner_id`, `label`, `is_dynamic`);

UPDATE `selection` SET `is_dynamic` = 0, `search_query` = NULL WHERE `search_query` IS NULL OR TRIM(search_query) = "";
UPDATE `selection` SET `is_dynamic` = 1, `search_query` = TRIM(`search_query`) WHERE `search_query` IS NOT NULL AND TRIM(search_query) != "";
SQL;
    foreach (explode(";\n", $sqls) as $sql) {
        $connection->executeStatement($sql);
    }
}

if (version_compare($oldVersion, '3.3.4.4', '<')) {
    $message = new Message(
        'Helpers and templates were renamed. Old ones will be removed in a future version.' // @translate
    );
    $messenger->addWarning($message);
}

if (version_compare($oldVersion, '3.3.4.5', '<')) {
    $sql = <<<'SQL'
ALTER TABLE `selection`
ADD `structure` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)' AFTER `search_query`;
SQL;
    $connection->executeStatement($sql);

    // Add at least one selection by user for all selected resources.
    // Selections by owner.
    $sql = <<<'SQL'
SELECT id, owner_id
FROM selection_resource
WHERE selection_id IS NULL
ORDER BY owner_id;
SQL;
    $selectionResources = $connection->executeQuery($sql)->fetchAllKeyValue();

    // Create a new selection for all selections, even if the user has one.
    if ($selectionResources) {
        $sql = <<<'SQL'
INSERT INTO selection (owner_id, label, created)
SELECT DISTINCT owner_id, '__SELECTION__', NOW()
FROM selection_resource
WHERE selection_id IS NULL;
SQL;
        $connection->executeStatement($sql);
        $sql = <<<'SQL'
UPDATE selection_resource
JOIN selection
    ON selection.owner_id =  selection_resource.owner_id
        AND selection.label = "__SELECTION__"
SET selection_id = selection.id;
SQL;
        $connection->executeStatement($sql);
        $sql = <<<'SQL'
UPDATE selection
SET label = "%s"
WHERE label = "__SELECTION__";
SQL;
        $translate = $services->get('ControllerPluginManager')->get('translate');
        $connection->executeStatement(sprintf($sql, $translate('Selection'))); // @translate
    }

    $message = new Message(
        'It is now possible to organize selected resources in a structured way.' // @translate
    );
    $messenger->addSuccess($message);
    $message = new Message(
        'The structure is not available for anonymous visitors yet.' // @translate
    );
    $messenger->addWarning($message);
    $message = new Message(
        'Some url routes have been updated to use query arguments. Check you theme if needed.' // @translate
    );
    $messenger->addWarning($message);
}

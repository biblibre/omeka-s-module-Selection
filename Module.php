<?php

namespace Basket;

use Omeka\Module\AbstractModule;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mvc\MvcEvent;

/**
 * Basket.
 *
 * Allow basket
 *
 * @copyright Biblibre, 2016
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $roles = $acl->getRoles();
        $acl->allow($roles, Entity\BasketItem::class);
        $acl->allow($roles, Api\Adapter\BasketItemAdapter::class);
        $acl->allow($roles , 'Basket\Controller\Index');
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = '
            CREATE TABLE basket_item (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                resource_id INT NOT NULL,
                created DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_D4943C2BA76ED395 (user_id),
                INDEX IDX_D4943C2B89329D25 (resource_id),
                CONSTRAINT FK_D4943C2BA76ED395
                  FOREIGN KEY (user_id) REFERENCES user (id)
                  ON DELETE CASCADE,
                CONSTRAINT FK_D4943C2B89329D25
                  FOREIGN KEY (resource_id) REFERENCES resource (id)
                  ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
        ';
        $connection->exec($sql);
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

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
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec('DROP TABLE IF EXISTS basket_item');
    }
}

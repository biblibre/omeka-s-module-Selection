<?php
namespace Basket;

use Omeka\Module\AbstractModule;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\Mvc\Controller\AbstractController;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;


/**
 * Basket
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
        $acl->allow(null, 'Basket\Controller\Index');


    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = '
            CREATE TABLE IF NOT EXISTS `basket` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `item_id` int(11) DEFAULT NULL,
`media_id` int(11) DEFAULT NULL,
 `created` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ';
        $connection->exec($sql);

    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = 'DROP TABLE IF EXISTS `basket`';
        $connection->exec($sql);

    }


}
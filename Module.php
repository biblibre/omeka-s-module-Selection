<?php
namespace Basket;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Zend\Mvc\MvcEvent;

/**
 * Basket.
 *
 * @copyright Biblibre, 2016
 * @copyright Daniel Berthereau 2019
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $roles = $acl->getRoles();
        $acl
            ->allow(
                $roles,
                [
                    Entity\BasketItem::class,
                    Api\Adapter\BasketItemAdapter::class,
                    'Basket\Controller\Index',
                ]
        );
    }
}

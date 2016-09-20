<?php
namespace Basket\Service\Controller;

use Basket\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class BasketIndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $authenticationService = $services->get('Omeka\AuthenticationService');
        $entityManager = $services->get('Omeka\EntityManager');


        $controller = new IndexController;
        $controller->setAuthenticationService($authenticationService);
        $controller->setEntityManager($entityManager);

        return $controller;
    }
}

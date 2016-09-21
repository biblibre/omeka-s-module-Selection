<?php

namespace Basket\Service\Helper;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Basket\View\Helper\AddItemToBasketLink;

class AddToBasketLinkFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $entityManager = $services->get('Omeka\EntityManager');
        $authenticationService = $services->get('Omeka\AuthenticationService');

        $element = new AddItemToBasketLink;
        $element->setEntityManager($entityManager);
        $element->setAuthenticationService($authenticationService);
        return $element;
    }
}

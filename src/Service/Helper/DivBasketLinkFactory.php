<?php

namespace Basket\Service\Helper;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Basket\View\Helper\AddItemToBasketLink;
use Basket\View\Helper\DivBasketLink;

class DivBasketLinkFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $entityManager = $services->get('Omeka\EntityManager');
        $authenticationService = $services->get('Omeka\AuthenticationService');

        $element = new DivBasketLink;
        $button = new AddItemToBasketLink;
        $button->setEntityManager($entityManager);
        $button->setAuthenticationService($authenticationService);
        $element->setButton($button);
        return $element;
    }
}

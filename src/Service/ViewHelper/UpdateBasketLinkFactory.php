<?php

namespace Basket\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Basket\View\Helper\UpdateBasketLink;

class UpdateBasketLinkFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $authenticationService = $services->get('Omeka\AuthenticationService');

        return new UpdateBasketLink($authenticationService);
    }
}

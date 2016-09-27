<?php

namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

class UpdateBasketLink extends AbstractHelper
{
    protected $authenticationService;

    public function __construct($authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function __invoke($resource)
    {
        $view = $this->getView();

        $user = $this->authenticationService->getIdentity();
        if (!$user) {
            return '';
        }

        $action = 'add';

        $basket = $this->basketExistsFor($user->getId(), $resource->id());
        if ($basket) {
            $action = 'delete';
        }

        return $view->partial('basket/basket-button', [
            'action' => $action,
            'resource' => $resource,
        ]);
    }

    protected function basketExistsFor($userId, $resourceId)
    {
        $api = $this->getView()->api();
        $basket_items = $api->search('basket_items', [
            'user_id' => $userId,
            'resource_id' => $resourceId,
        ])->getContent();

        return !empty($basket_items) ? $basket_items[0] : null;
    }
}

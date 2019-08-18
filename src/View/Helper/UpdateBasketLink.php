<?php

namespace Basket\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class UpdateBasketLink extends AbstractHelper
{
    public function __invoke(AbstractResourceEntityRepresentation $resource)
    {
        $view = $this->getView();

        $user = $view->identity();
        if (!$user) {
            return '';
        }

        $basket = $this->basketExistsFor($user->getId(), $resource->id());
        $action = $basket
            ? 'delete'
            : 'add';

        $view->headScript()->appendFile($view->assetUrl('js/basket.js', 'Basket'));

        return $view->partial('common/basket-button', [
            'action' => $action,
            'resource' => $resource,
            'url' => $view->url('site/basket-id', ['action' => $action, 'id' => $resource->id()], true),
        ]);
    }

    protected function basketExistsFor($userId, $resourceId)
    {
        return $this->getView()->api()
            ->searchOne('basket_items', [
                'user_id' => $userId,
                'resource_id' => $resourceId,
            ])
            ->getContent();
    }
}

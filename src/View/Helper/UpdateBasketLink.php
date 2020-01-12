<?php

namespace Basket\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class UpdateBasketLink extends AbstractHelper
{
    /**
     * Create a button to add or remove a resource to/from the basket.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param array $options Options for the partial.
     * @return string
     */
    public function __invoke(AbstractResourceEntityRepresentation $resource, array $options = [])
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

        $view->headScript()->appendFile($view->assetUrl('js/basket.js', 'Basket'), 'text/javascript', ['defer' => 'defer']);

        $template = isset($options['template']) ? $options['template'] : 'common/basket-button';
        unset($options['template']);

        $params = [
            'action' => $action,
            'resource' => $resource,
            'url' => $view->url('site/basket-id', ['action' => $action, 'id' => $resource->id()], true),
        ];

        return $view->partial($template, $params + $options);
    }

    protected function basketExistsFor($userId, $resourceId)
    {
        return (bool) $this->getView()->api()
            ->searchOne('basket_items', [
                'user_id' => $userId,
                'resource_id' => $resourceId,
            ])
            ->getTotalResults();
    }
}

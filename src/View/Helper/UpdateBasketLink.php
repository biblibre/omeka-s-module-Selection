<?php
namespace Basket\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class UpdateBasketLink extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/basket-button';

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

        $defaultOptions = [
            'template' => self::PARTIAL_NAME,
        ];
        $options += $defaultOptions;

        $basket = $this->basketExistsFor($user->getId(), $resource->id());
        $action = $basket ? 'delete' : 'add';

        $view->headScript()
            ->appendFile($view->assetUrl('js/basket.js', 'Basket'), 'text/javascript', ['defer' => 'defer']);

        $template = $options['template'];
        unset($options['template']);

        $params = [
            'action' => $action,
            'resource' => $resource,
            'url' => $view->url('site/basket-id', ['action' => $action, 'id' => $resource->id()], true),
        ];

        return $view->partial($template, $params + $options);
    }

    /**
     * Check if a resource is in the user basket.
     *
     * @param int $userId
     * @param int $resourceId
     * @return bool
     */
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

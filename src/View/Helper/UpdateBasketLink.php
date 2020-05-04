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
     * @param array $options Options for the partial. Managed key:
     * - action: "add" or "delete". If not specified, the action is "toggle".
     * @return string
     */
    public function __invoke(AbstractResourceEntityRepresentation $resource, array $options = [])
    {
        $view = $this->getView();

        $user = $view->identity();
        if (!$user) {
            return '';
        }

        if (!array_key_exists('basketItem', $options)) {
            $options['basketItem'] = $this->getView()->api()->searchOne(
                'basket_items',
                [
                    'user_id' => $user->getId(),
                    'resource_id' => $resource->id(),
                ])
                ->getContent();
        }

        $defaultOptions = [
            'template' => self::PARTIAL_NAME,
            'action' => 'toggle',
        ];
        $options += $defaultOptions;

        $view->headScript()
            ->appendFile($view->assetUrl('js/basket.js', 'Basket'), 'text/javascript', ['defer' => 'defer']);

        $template = $options['template'];
        unset($options['template']);

        $params = [
            'resource' => $resource,
            'url' => $view->url('site/basket-id', ['action' => $options['action'], 'id' => $resource->id()], true),
            // @deprecated Kept for old themes.
            'action' => $options['basketItem'] ? 'delete' : 'add',
        ];

        return $view->partial($template, $params + $options);
    }
}

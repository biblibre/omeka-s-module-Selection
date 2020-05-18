<?php
namespace Selection\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class UpdateSelectionLink extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/selection-button';

    /**
     * Create a button to add or remove a resource to/from the selection.
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

        if (!array_key_exists('selectionItem', $options)) {
            $options['selectionItem'] = $this->getView()->api()->searchOne(
                'selection_items',
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
            ->appendFile($view->assetUrl('js/selection.js', 'Selection'), 'text/javascript', ['defer' => 'defer']);

        $template = $options['template'];
        unset($options['template']);

        $params = [
            'resource' => $resource,
            'url' => $view->url('site/selection-id', ['action' => $options['action'], 'id' => $resource->id()], true),
            // @deprecated Kept for old themes.
            'action' => $options['selectionItem'] ? 'delete' : 'add',
        ];

        return $view->partial($template, $params + $options);
    }
}

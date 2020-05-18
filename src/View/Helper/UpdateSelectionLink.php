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
        $siteSetting = $view->plugin('siteSetting');

        $user = $view->identity();
        $allowVisitor = $siteSetting('selection_visitor_allow', true);
        if (!$allowVisitor && !$user) {
            return '';
        }

        $userFillMain = $user && $siteSetting('selection_user_fill_main');
        // User selection.
        if ($userFillMain) {
            if (!array_key_exists('selectionItem', $options)) {
                $options['selectionItem'] = $view->api()
                    ->searchOne('selection_items', ['user_id' => $user->getId(), 'resource_id' => $resource->id()])
                    ->getContent();
            }
        } else {
            $container = new \Zend\Session\Container('Selection');
            $options['selectionItem'] = isset($container->records[$resource->id()]);
        }

        $defaultOptions = [
            'template' => self::PARTIAL_NAME,
            'action' => 'toggle',
        ];
        $options += $defaultOptions;

        $template = $options['template'];
        unset($options['template']);

        $params = [
            'resource' => $resource,
            'url' => $view->url('site/selection-id', ['action' => $options['action'], 'id' => $resource->id()], true),
        ];

        return $view->partial($template, $params + $options);
    }
}

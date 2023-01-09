<?php declare(strict_types=1);

namespace Selection\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class Selection implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Selection'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        $siteSetting = $view->getHelperPluginManager()->get('siteSetting');

        $user = $view->identity();
        $allowVisitor = $siteSetting('selection_visitor_allow', true);
        if (!$user && !$allowVisitor) {
            return;
        }

        $selectionContainer = $this->getServiceLocator()->get('ControllerPluginManager')->get('selectionContainer');
        $selectionContainer();

        return $view->partial('common/resource-page-block-layout/selection', [
            'resource' => $resource,
        ]);
    }
}

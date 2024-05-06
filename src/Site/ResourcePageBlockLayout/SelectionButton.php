<?php declare(strict_types=1);

namespace Selection\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class SelectionButton implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Selection button'; // @translate
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
        $plugins = $view->getHelperPluginManager();
        $siteSetting = $plugins->get('siteSetting');

        $user = $view->identity();
        $disableAnonymous = (bool) $siteSetting('selection_disable_anonymous');
        if ($disableAnonymous && !$user) {
            return '';
        }

        return $view->partial('common/resource-page-block-layout/selection-button', [
            'site' => $view->layout()->site,
            'resource' => $resource,
        ]);
    }
}

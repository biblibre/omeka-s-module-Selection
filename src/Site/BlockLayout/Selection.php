<?php

namespace Selection\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class Selection extends AbstractBlockLayout
{
    public function getLabel ()
    {
        return 'Selection'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null) {
        return $view->translate('Selection');
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $user = $view->identity();
        if (!$user) {
            return '';
        }

        $selectionResources = $view->api()->search('selection_resources', ['owner_id' => $user->getId()])->getContent();

        $resources = [];
        foreach ($selectionResources as $selectionResource) {
            $resources[] = $selectionResource->resource();
        }

        $values = [
            'site' => $view->layout()->site,
            'selectionResources' => $selectionResources,
            'resources' => $resources,
        ];

        return $view->partial('guest/site/guest/selection', $values);
    }
}

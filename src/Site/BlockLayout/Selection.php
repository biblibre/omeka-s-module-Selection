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
        $view->headLink()->appendStylesheet($view->assetUrl('css/selection.css', 'Selection'));
        $view->headScript()->appendFile($view->assetUrl('js/selection.js', 'Selection'), 'text/javascript', ['defer' => 'defer']);
        return $view->partial('common/selection-list', ['open' => true]);
    }
}

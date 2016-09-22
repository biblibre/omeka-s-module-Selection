<?php
namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Omeka\Module\Manager as ModuleManager;
use Omeka\Api\Representation\ItemRepresentation;
class ShowBasketLink extends AbstractHelper
{
    protected $button;

    public function __invoke($content)
    {
        $view = $this->getView();
        return '<a class="show_basket" href="'.$view->url('site/basket' ,['action' => 'show',
                                                      'site-slug' => $view->site->slug()]).'">'.
            $content."</a>";
    }

}

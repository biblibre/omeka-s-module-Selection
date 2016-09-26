<?php
namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Omeka\Module\Manager as ModuleManager;
use Omeka\Api\Representation\ItemRepresentation;
class DivBasketLink extends AbstractHelper
{
    protected $button;

    public function __invoke($item,$site=null)
    {
        $this->button->setView($this->getView());
        return '<div id="update_basket'.$item->id().'">'.call_user_func($this->button,$item,$site).'</div>';

    }

    public function setButton($button) {
        $this->button = $button;
    }
}

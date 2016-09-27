<?php

namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ShowBasketLink extends AbstractHelper
{
    protected $button;

    public function __invoke($content)
    {
        $view = $this->getView();

        return $view->partial('basket/basket-link');
    }
}

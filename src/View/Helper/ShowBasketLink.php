<?php

namespace Basket\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ShowBasketLink extends AbstractHelper
{
    /**
     * Get the link to the user basket.
     *
     * @return string
     */
    public function __invoke()
    {
        $view = $this->getView();
        return $view->partial('common/basket-link');
    }
}

<?php

namespace Selection\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ShowSelectionLink extends AbstractHelper
{
    /**
     * Get the link to the user selection.
     *
     * @return string
     */
    public function __invoke()
    {
        $view = $this->getView();
        return $view->partial('common/selection-link');
    }
}

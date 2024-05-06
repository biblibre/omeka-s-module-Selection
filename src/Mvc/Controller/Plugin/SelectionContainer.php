<?php declare(strict_types=1);

namespace Selection\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Container;

class SelectionContainer extends AbstractPlugin
{
    /**
     * @var \Selection\View\Helper\SelectionContainer
     */
    protected $selectionContainer;

    public function __construct(\Selection\View\Helper\SelectionContainer $selectionContainer)
    {
        $this->selectionContainer = $selectionContainer;
    }

    /**
     * Prepare and check the session container for the current visitor or user.
     *
     * @uses \Selection\ViewHelper\SelectionContainer
     */
    public function __invoke(): Container
    {
        return $this->selectionContainer->__invoke();
    }
}

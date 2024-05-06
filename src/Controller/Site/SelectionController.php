<?php declare(strict_types=1);

namespace Selection\Controller\Site;

class SelectionController extends AbstractSelectionController
{
    public function __construct(bool $isGuestActive)
    {
        $this->isGuestActive = $isGuestActive;
    }

    public function indexAction()
    {
        $params = $this->params()->fromRoute();
        $params['action'] = 'browse';
        return $this->forward()->dispatch('Selection\Controller\Site\Selection', $params);
    }
}

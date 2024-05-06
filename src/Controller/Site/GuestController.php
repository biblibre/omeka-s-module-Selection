<?php declare(strict_types=1);

namespace Selection\Controller\Site;

class GuestController extends AbstractSelectionController
{
    protected $isGuestActive = true;

    public function indexAction()
    {
        $params = $this->params()->fromRoute();
        $params['action'] = 'browse';
        return $this->forward()->dispatch('Selection\Controller\Site\Guest', $params);
    }
}

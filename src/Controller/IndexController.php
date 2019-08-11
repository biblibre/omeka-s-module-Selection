<?php

/*
 * Copyright BibLibre, 2016
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Basket\Controller;

use Zend\Http\Response;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    protected $authenticationService;

    public function addAction()
    {
        $id = $this->params('id');
        if (!$id) {
            return $this->jsonErrorNotFound();
        }

        $resource = $this->api()->read('resources', $id)->getContent();
        if (!$resource) {
            return $this->jsonErrorNotFound();
        }

        $user = $this->getAuthenticationService()->getIdentity();
        $basketItems = $this->api()->search('basket_items', [
            'user_id' => $user->getId(),
            'resource_id' => $resource->id(),
        ])->getContent();

        if (!empty($basketItems)) {
            return new JsonModel(['error' => 'alreadyIn']);
        }

        $this->createBasketItem($user->getId(), $resource->id());

        $updateBasketLink = $this->viewHelpers()->get('updateBasketLink');

        return new JsonModel(['content' => $updateBasketLink($resource)]);
    }

    public function deleteAction()
    {
        $id = $this->params('id');
        if (!$id) {
            return $this->jsonErrorNotFound();
        }

        $user = $this->getAuthenticationService()->getIdentity();

        $basketItems = $this->api()->search('basket_items', [
            'user_id' => $user->getId(),
            'resource_id' => $id,
        ])->getContent();

        if (empty($basketItems)) {
            return $this->jsonErrorNotFound();
        }

        $basketItem = $basketItems[0];
        $resource = $basketItem->resource();

        $this->api()->delete('basket_items', $basketItem->id());

        $updateBasketLink = $this->viewHelpers()->get('updateBasketLink');
        $content = $updateBasketLink($resource);

        return new JsonModel(['content' => $content]);
    }

    public function showAction()
    {
        $user = $this->getAuthenticationService()->getIdentity();

        $query = $this->params()->fromQuery();
        $query['user_id'] = $user->getId();

        $basketItems = $this->api()->search('basket_items', $query)->getContent();

        $view = new ViewModel;
        $view->setVariable('basketItems', $basketItems);

        return $view;
    }

    public function setAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function getAuthenticationService()
    {
        return $this->authenticationService;
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function jsonErrorNotFound()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_404);

        return new JsonModel(['error' => 'NotFound']);
    }

    protected function createBasketItem($userId, $resourceId)
    {
        $this->api()->create('basket_items', [
            'o:user_id' => $userId,
            'o:resource_id' => $resourceId,
        ]);
    }
}

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

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Omeka\Mvc\Exception\RuntimeException;
use Omeka\Service\Paginator;
use Basket\Entity\Basket;

class IndexController extends AbstractActionController
{
    protected $authenticationService;


    public function additemAction() {
        if (!$id_item = $this->params('id'))
            return ;
        if (!($user = $this->getAuthenticationService()->getIdentity())) {
            return $this->redirect()->toUrl($this->currentSite()->url());
        }

        if (!$item = $this->getEntityManager()->getRepository('Omeka\Entity\Item')->find($id_item))
            return;
        if ($this->basketExistsFor($user,$item))
            return;
        $this->createBasket($user,$item);
    }

    protected function createBasket($user,$item,$media = null) {
        $basket = new Basket;
        $basket->setUser($user);
        $basket->setItem($item);
        $basket->setMedia($media);
        $this->save($basket);

    }

    public function addmediaAction() {
        if (!$id_media = $this->params('id'))
            return ;
        if (!($user = $this->getAuthenticationService()->getIdentity())) {
            return $this->redirect()->toUrl($this->currentSite()->url());
        }

        if (!$media = $this->getEntityManager()->getRepository('Omeka\Entity\Media')->find($id_media))
            return;
        if ($this->basketExistsFor($user,$media))
            return;
        $this->createBasket($user,null,$media);
    }


    protected function basketExistsFor($user,$item) {
        return $this->getEntityManager()
                    ->getRepository('Basket\Entity\Basket')
                    ->findOneBy(['item' => $item,
                                 'user' => $user]);
    }

    public function removeitemAction() {
        if (!$id_item = $this->params('id'))
            return ;
        if (!($user = $this->getAuthenticationService()->getIdentity())) {
            return $this->redirect()->toUrl($this->currentSite()->url());
        }

        if (!$item = $this->getEntityManager()->getRepository('Omeka\Entity\Item')->find($id_item))
            return;
        if (!$basket = $this->basketExistsFor($user,$item))
            return;
        $this->getEntityManager()->remove($basket);
        $this->getEntityManager()->flush();
    }

    public function deleteAction() {
        if (!$id_basket = $this->params('id'))
            return;
        if (!$basket = $this->getEntityManager()
            ->getRepository('Basket\Entity\Basket')
            ->find($id_basket))
            return;
        $this->getEntityManager()->remove($basket);
        $this->getEntityManager()->flush();

    }

    public function showAction() {
        if (!($user = $this->getAuthenticationService()->getIdentity())) {
            return $this->redirect()->toUrl($this->currentSite()->url());
        }

        $baskets = $this->getEntityManager()
                        ->getRepository('Basket\Entity\Basket')
                        ->findBy(['user' => $user ]);
        $items = [];
        $medias = [];

        foreach ($baskets as $basket) {
            if ($item = $basket->getItem())
                $items[]=$item;
            if ($media = $basket->getMedia())
                $medias[]=$media;


        }
        $view = new ViewModel;
        $site = $this->currentSite();
        $view->setVariable('site', $site);
        $view->setVariable('items', $items);
        $view->setVariable('medias', $medias);
        $view->setVariable('resourceName', 'media');
        $view->setVariable('title', 'Panier');
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

    protected function save($entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }



}

<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2019-2022
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

namespace Selection\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class GuestBoardController extends AbstractActionController
{
    public function indexAction()
    {
        $params = $this->params()->fromRoute();
        $params['action'] = 'resource-browse';
        return $this->forward()->dispatch('Selection\Controller\Site\GuestBoard', $params);
    }

    public function resourceBrowseAction()
    {
        $user = $this->identity();

        if (!isset($user)) {
            return;
        }

        $query = $this->params()->fromQuery();
        $query['owner_id'] = $user->getId();

        $selectionResources = $this->api()->search('selection_resources', $query)->getContent();

        $classesToTypes = [
            \Omeka\Api\Representation\ItemRepresentation::class => 'items',
            \Omeka\Api\Representation\ItemSetRepresentation::class => 'item_sets',
            \Omeka\Api\Representation\MediaRepresentation::class => 'media',
            \Annotate\Api\Representation\AnnotationRepresentation::class => 'annotations',
        ];

       $selecteds = [];
       $resources = [];
       $resourcesByType = [
           'items' => [],
           'item_sets' => [],
           'media' => [],
           'annotations' => []
       ];
       foreach ($selectionResources as $selectionResource) {
           $resource = $selectionResource->resource();
           $resourceType = $classesToTypes[get_class($resource)] ?? 'resources';
           $resourceId = $resource->id();
           $typeAndId = $resourceType . '/' . $resourceId;
           $selecteds[$typeAndId] = $selectionResource;
           $resources[$typeAndId] = $resource;
           $resourcesByType[$resourceType][$resourceId] = $resource;
       }
       $selectionResources = $selecteds;

        $view = new ViewModel([
            'site' => $this->currentSite(),
            'selectionResources' => $selectionResources,
            'resources' => $resources,
            'resourcesByType' => $resourcesByType,
        ]);
        return $view
            ->setTemplate('guest/site/guest/selection-resource-browse');
    }
}

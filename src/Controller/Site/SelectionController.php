<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2019-2020
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

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class SelectionController extends AbstractActionController
{
    public function addAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->jsonErrorNotFound();
        }

        $siteSettings = $this->siteSettings();
        $allowVisitor = $siteSettings->get('selection_visitor_allow', true);
        $user = $this->identity();
        if (!$allowVisitor && !$user) {
            return $this->jsonErrorNotFound();
        }

        $resources = $this->requestedResources();
        if (empty($resources['has_result'])) {
            return $this->jsonErrorNotFound();
        }
        $isMultiple = $resources['is_multiple'];
        $resources = $resources['resources'];

        $api = $this->api();
        $results = [];
        $userId = $user ? $user->getId() : false;

        // When a user is set, the session and the database are sync.
        $container = $this->containerSelection();

        foreach ($resources as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, true);
            if (isset($container->records[$resourceId])) {
                $data['status'] = 'fail';
                $data['message'] = $this->translate('Already in'); // @translate
            } else {
                $container->records[$resourceId] = $data;
                $data['status'] = 'success';
                if ($userId) {
                    try {
                        $api->create('selection_resources', ['o:owner' => ['o:id' => $userId], 'o:resource' => ['o:id' => $resourceId]])->getContent();
                    } catch (\Exception $e) {
                    }
                }
            }
            $results[$resourceId] = $data;
        }

        if ($isMultiple) {
            $data = [
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection_resource' => reset($results),
            ];
        }

        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function deleteAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->jsonErrorNotFound();
        }

        $siteSettings = $this->siteSettings();
        $allowVisitor = $siteSettings->get('selection_visitor_allow', true);
        $user = $this->identity();
        if (!$allowVisitor && !$user) {
            return $this->jsonErrorNotFound();
        }

        $resources = $this->requestedResources();
        if (empty($resources['has_result'])) {
            return $this->jsonErrorNotFound();
        }
        $isMultiple = $resources['is_multiple'];
        $resources = $resources['resources'];

        $api = $this->api();
        $results = [];
        $userId = $user ? $user->getId() : false;

        // When a user is set, the session and the database are sync.
        $container = $this->containerSelection();

        foreach ($resources as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, false);
            $data['status'] = 'success';
            unset($container->records[$resourceId]);
            if ($userId) {
                try {
                    $api->delete('selection_resources', ['owner' => $userId, 'resource' => $resourceId]);
                } catch (\Exception $e) {
                }
            }
            $results[$resourceId] = $data;
        }

        if ($isMultiple) {
            $data = [
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection_resource' => reset($results),
            ];
        }

        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function toggleAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->jsonErrorNotFound();
        }

        $siteSettings = $this->siteSettings();
        $allowVisitor = $siteSettings->get('selection_visitor_allow', true);
        $user = $this->identity();
        if (!$allowVisitor && !$user) {
            return $this->jsonErrorNotFound();
        }

        $resources = $this->requestedResources();
        if (empty($resources['has_result'])) {
            return $this->jsonErrorNotFound();
        }
        $isMultiple = $resources['is_multiple'];
        $resources = $resources['resources'];

        $api = $this->api();
        $results = [];
        $userId = $user ? $user->getId() : false;

        // When a user is set, the session and the database are sync.
        $container = $this->containerSelection();

        $add = [];
        $delete = [];
        foreach ($resources as $resourceId => $resource) {
            if (isset($container->records[$resourceId])) {
                $delete[$resourceId] = $resource;
            } else {
                $add[$resourceId] = $resource;
            }
        }
        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $add */
        foreach ($add as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, true);
            $data['status'] = 'success';
            $container->records[$resourceId] = $data;
            $results[$resourceId] = $data;
            if ($userId) {
                try {
                    $api->create('selection_resources', ['o:owner' => ['o:id' => $userId], 'o:resource' => ['o:id' => $resourceId]])->getContent();
                } catch (\Exception $e) {
                }
            }
        }
        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $delete */
        foreach ($delete as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, false);
            $data['status'] = 'success';
            unset($container->records[$resourceId]);
            $results[$resourceId] = $data;
            if ($userId) {
                try {
                    $api->delete('selection_resources', ['owner' => $userId, 'resource' => $resourceId]);
                } catch (\Exception $e) {
                }
            }
        }

        if ($isMultiple) {
            $data = [
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection_resource' => reset($results),
            ];
        }

        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    protected function requestedResources()
    {
        $params = $this->params();
        $id = $params->fromRoute('id') ?: $params->fromQuery('id');
        if (!$id) {
            return ['has_result' => false];
        }

        $isMultiple = is_array($id);
        $ids = $isMultiple ? $id : [$id];

        $api = $this->api();
        // Check resources.
        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $resources */
        $resources = [];
        foreach ($ids as $id) {
            try {
                $resource = $api->read('resources', ['id' => $id])->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                return ['has_result' => false];
            }
            $resources[$id] = $resource;
        }

        return [
            'has_result' => (bool) count($resources),
            'is_multiple' => $isMultiple,
            'resources' => $resources,
        ];
    }

    /**
     * Format a resource for the container.
     *
     * Copy in \Selection\Mvc\Controller\Plugin\ContainerSelection::selectionResourceForResource()
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param bool $isSelected
     * @return array
     */
    protected function selectionResourceForResource(AbstractResourceEntityRepresentation $resource, $isSelected)
    {
        static $siteSlug;
        static $url;
        if (is_null($siteSlug)) {
            $siteSlug = $this->currentSite()->slug();
            $url = $this->url();
        }
        return [
            'id' => $resource->id(),
            'type' => $resource->getControllerName(),
            'url' => $resource->siteUrl($siteSlug, true),
            'url_remove' => $url->fromRoute('site/selection-id', ['site-slug' => $siteSlug, 'action' => 'delete', 'id' => $resource->id()]),
            // String is required to avoid error in container when the title is
            // a resource.
            'title' => (string) $resource->displayTitle(),
            'value' => $isSelected ? 'selected' : 'unselected',
        ];
    }

    protected function jsonErrorNotFound()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_404);
        return new JsonModel([
            'status' => 'error',
            'message' => $this->translate('Not found'), // @translate
        ]);
    }
}

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

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Entity\User;
use Selection\Api\Representation\SelectionRepresentation;

/**
 * @todo Include the selection by default in all actions.
 */
class SelectionController extends AbstractActionController
{
    /**
     * Select resource(s) to add to a selection.
     */
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
        $container = $this->selectionContainer();

        foreach ($resources as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, true);
            if (isset($container->records[$resourceId])) {
                $data['status'] = 'fail';
                $data['data'] = [
                    'message' => $this->translate('Already in'), // @translate
                ];
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

    /**
     * Delete resource(s) from a selection.
     */
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
        $container = $this->selectionContainer();

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

    /**
     * Toggle select/unselect resource(s) for a selection.
     */
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
        $container = $this->selectionContainer();

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

    /**
     * Move resource(s) between groups of a selection.
     */
    public function moveAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->jsonErrorNotFound();
        }

        $siteSettings = $this->siteSettings();
        $allowVisitor = $siteSettings->get('selection_visitor_allow', true);
        $user = $this->identity();
        // TODO Manage group for visitor by session.
        if (!$allowVisitor || !$user) {
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
        // TODO Manage session container.

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        if (empty($id)) {
            $selection = $this->defaultStaticSelection($user);
        } else {
            try {
                $selection = $api->read('selections', ['owner' => $user->getId()])->getContent();
            } catch (\Exception $e) {
                return $this->jsonErrorNotFound();
            }
        }

        // Add the group only if it does not exist.
        $structure = $selection->structure();

        $source = trim((string) $this->params()->fromQuery('group'));
        $destination = trim((string) $this->params()->fromQuery('destination'));
        if ($source === $destination) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group is unchanged.'), // @translate
                ],
            ]);
        }

        if (strlen($source) && !isset($structure[$source])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => sprintf(
                        $this->translate('The group "%s" does not exist.'), // @translate
                        str_replace('/', ' / ', $source)
                    ),
                ],
            ]);
        }

        if (strlen($destination) && !isset($structure[$destination])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => sprintf(
                        $this->translate('The destination group "%s" does not exist.'), // @translate
                        str_replace('/', ' / ', $destination)
                    ),
                ],
            ]);
        }

        if (strlen($destination) && !isset($structure[$destination])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => sprintf(
                        $this->translate('The destination group "%s" does not exist.'), // @translate
                        str_replace('/', ' / ', $destination)
                        ),
                ],
            ]);
        }

        // TODO Moe all resources of a group.
        $moveAllResources = false;
        if ($moveAllResources) {
            $sourceResources = $selection->resourcesForGroup($source);
            if (!$sourceResources) {
                return new JsonModel([
                    'status' => 'fail',
                    'data' => [
                        'message' => $this->translate('There are no resources to move.'), // @translate
                    ],
                ]);
            }
            unset($structure[$source]['resources']);
            if (strlen($destination)) {
                if (empty($structure[$destination]['resources'])) {
                    $structure[$destination]['resources'] = array_keys($sourceResources);
                } else {
                    $structure[$destination]['resources'] += array_keys($sourceResources);
                }
            }
        } else {
            if (strlen($source)) {
                $sourceResources = $selection->resourcesForGroup($source);
                $structure[$source]['resources'] = array_keys(array_diff_key($sourceResources, $resources));
            }
            if (strlen($destination)) {
                if (empty($structure[$destination]['resources'])) {
                    $structure[$destination]['resources'] = array_keys($resources);
                } else {
                    $structure[$destination]['resources'] += array_keys($resources);
                }
            }
        }

        try {
            $api->update('selections', $selection->id(), [
                'o:structure' => $structure,
            ], [], ['isPartial' => true])->getContent();
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'errof',
                'message' => $this->translate('An internal error occurred.'), // @translate
            ]);
        }

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection,
                'source' => $structure[$source] ?? null,
                'group' =>  $structure[$destination] ?? null,
            ],
        ]);
    }

    /**
     * Add a group to a selection.
     */
    public function addGroupAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->jsonErrorNotFound();
        }

        $siteSettings = $this->siteSettings();
        $allowVisitor = $siteSettings->get('selection_visitor_allow', true);
        $user = $this->identity();
        // TODO Manage group for visitor by session.
        if (!$allowVisitor || !$user) {
            return $this->jsonErrorNotFound();
        }

        $path = trim((string) $this->params()->fromQuery('group'));
        $groupName = trim((string) $this->params()->fromQuery('name'));
        if (!strlen($groupName)) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('No group set.'), // @translate
                ],
            ]);
        }

        $invalidCharacters = '/\\?<>*%|"`&';
        $invalidCharactersRegex = '~/|\\|\?|<|>|\*|\%|\||"|`|&~';
        if  ($groupName === '/'
            || $groupName === '\\'
            || $groupName === '.'
            || $groupName === '..'
            || preg_match($invalidCharactersRegex, $groupName)
        ) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => sprintf(
                        $this->translate('The group name contains invalid characters (%s).'), // @translate
                        $invalidCharacters
                    ),
                ],
            ]);
        }

        $api = $this->api();

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        if (empty($id)) {
            $selection = $this->defaultStaticSelection($user);
        } else {
            try {
                $selection = $api->read('selections', ['owner' => $user->getId()])->getContent();
            } catch (\Exception $e) {
                return $this->jsonErrorNotFound();
            }
        }

        // Add the group only if it does not exist.
        $structure = $selection->structure();

        // Check the parent for security.
        $hasParent = strlen($path);
        if ($hasParent && !isset($structure[$path])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The parent group does not exist.'), // @translate
                ],
            ]);
        }

        $fullPath = "$path/$groupName";
        if (isset($structure[$fullPath])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group exists already.'), // @translate
                ],
            ]);
        }

        // Insert the group inside the parent path.
        if ($hasParent) {
            $group = [
                // path + id = full path.
                'id' => $groupName,
                'path' => $path,
            ];
            $s = [];
            foreach ($structure as $sFullPath=> $sGroup) {
                $s[$sFullPath] = $sGroup;
                if ($sFullPath === $path) {
                    $s[$fullPath] = $group;
                }
            }
            $structure = $s;
        } else {
            $group = [
                'id' => $groupName,
                'path' => '/',
            ];
            $structure[$fullPath] = $group;
        }

        try {
            $api->update('selections', $selection->id(), [
                'o:structure' => $structure,
            ], [], ['isPartial' => true])->getContent();
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'errof',
                'message' => $this->translate('An internal error occurred.'), // @translate
            ]);
        }

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection,
                'group' => $group,
            ],
        ]);
    }

    /**
     * Get selected resources from the query and prepare them.
     */
    protected function requestedResources()
    {
        $params = $this->params();
        $id = $params->fromQuery('id');
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
     * Copy in \Selection\Mvc\Controller\Plugin\SelectionContainer::selectionResourceForResource()
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
            'url_remove' => $url->fromRoute('site/selection', ['site-slug' => $siteSlug, 'action' => 'delete'], ['query' => ['id' => $resource->id()]]),
            // String is required to avoid error in container when the title is
            // a resource.
            'title' => (string) $resource->displayTitle(),
            'value' => $isSelected ? 'selected' : 'unselected',
        ];
    }

    /**
     * Get default selection, the first non-dynamic one or a new one when none.
     *
     * When there is a new selection, all selected resources without selection
     * are moved inside it.
     *
     * @todo Simplify: require a selection for the first selected resouce (and manage anonymous visitor).
     */
    protected function defaultStaticSelection(User $user): SelectionRepresentation
    {
        /** @var \Omeka\Api\Manager $api */
        $api = $this->api();
        $selection = $api->searchOne('selections', [
            'owner' => $user->getId(),
            'is_dynamic' => false,
        ])->getContent();
        if (!$selection) {
            $selection = $api->create('selections', [
                'o:owner' => ['o:id' => $user->getId()],
                'o:label' => $this->translate('My selection'), // @translate
            ])->getContent();
            $selecteds = $api->search('selection_resources', [
                'owner_id' => $user->getId(),
                'selection' => 0,
            ], ['returnScalar' => 'id'])->getContent();
            if ($selecteds) {
                $api->batchUpdate('selection_resources', $selecteds, [
                    'o:selection' => ['o:id' => $selection->id()],
                ]);
            }
        }
        return $selection;
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

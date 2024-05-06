<?php declare(strict_types=1);

namespace Selection\Controller\Site;

use Exception;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Omeka\Entity\User;
use Selection\Api\Representation\SelectionRepresentation;

/**
 * Manage selections via db for authenticated user with or without module Guest.
 *
 * There may be multiple selection by user, but only one most of the time.
 * A selected resource must belong to a selection.
 * A static selection is created on first action.
 * There may be multiple static selections, but the action is processed on the
 * first selection by default.
 * Dynamic selections (search requests) are managed only via api currently.
 */
trait TraitDbController
{
    protected function browseDb(User $user)
    {
        // Browse the current selection.

        $query = $this->params()->fromQuery();
        // $query['owner_id'] = $user->getId();

        // Most of the time, the selection is not defined, so take the first or
        // create a static one.
        $selectionId = empty($query['selection_id']) ? 0 : (int) $query['selection_id'];
        $selection = $this->getSelectionStaticOrFirstStaticOrCreate($selectionId, $user);
        $selectionId = $selection->id();

        // Fill selection from session.
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();

        $view = new ViewModel([
            'site' => $this->currentSite(),
            'user' => $user,
            'selectionId' => $selectionId,
            'selections' => $selectionContainer->selections,
            'records' => $selectionContainer->records,
            'isGuestActive' => $this->isGuestActive,
            'isSession' => false,
        ]);
        return $view
            ->setTemplate($this->isGuestActive
                ? 'guest/site/guest/selection-resource-browse'
                : 'selection/site/selection/selection-browse'
            );
    }

    /**
     * Select resource(s) to add to a selection.
     */
    protected function addDb(array $resources, bool $isMultiple, User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        $results = [];

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();
        $userId = $user->getId();

        $selectedResourceIds = $api->search('selection_resources', [
            'owner_id' => $userId,
            'selection_id' => $selectionId,
        ], ['returnScalar' => 'resource'])->getContent();

        $newResources = [];
        foreach ($resources as $resourceId => $resource) {
            $data = $this->normalizeResource($resource, true, $selectionId);
            if (in_array($resourceId, $selectedResourceIds)) {
                $data['status'] = 'fail';
                $data['data'] = [
                    'message' => $this->translate('Already in'), // @translate
                ];
            } else {
                $data['status'] = 'success';
                try {
                    $api->create('selection_resources', [
                        'o:owner' => ['o:id' => $userId],
                        'o:resource' => ['o:id' => $resourceId],
                        'o:selection' => ['o:id' => $selectionId],
                    ])->getContent();
                } catch (Exception $e) {
                }
                $newResources[] = $resourceId;
            }
            $results[$resourceId] = $data;
        }

        $newStructure = $this->addResourcesToStructure($selection->structure(), $newResources);
        if ($newStructure) {
            try {
                $api->update('selections', $selectionId, ['o:structure' => $newStructure], [], ['isPartial' => true]);
            } catch (Exception $e) {
                return $this->jsonInternalError();
            }
        }

        if ($isMultiple) {
            $data = [
                'selection' => $selection ? $selection->getReference() : null,
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection' => $selection ? $selection->getReference() : null,
                'selection_resource' => reset($results),
            ];
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Delete resource(s) from a selection.
     */
    protected function deleteDb(array $resources, bool $isMultiple, User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        $results = [];

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();
        $userId = $user->getId();

        $selectedResourceIds = $api->search('selection_resources', [
            'owner_id' => $userId,
            'selection_id' => $selectionId,
        ], ['returnScalar' => 'resource'])->getContent();

        foreach ($resources as $resourceId => $resource) {
            $data = $this->normalizeResource($resource, false, $selectionId);
            if (!in_array($resourceId, $selectedResourceIds)) {
                // Skip silently.
                $data['status'] = 'success';
                $results[$resourceId] = $data;
                continue;
            }
            $data['status'] = 'success';
            try {
                $api->delete('selection_resources', [
                    'owner' => $userId,
                    'resource' => $resourceId,
                    'selection' => $selectionId,
                ]);
            } catch (Exception $e) {
            }
            $results[$resourceId] = $data;
        }

        $newStructure = $this->removeResourcesFromStructure($selection->structure(), array_keys($results));
        if ($newStructure) {
            try {
                $api->update('selections', $selectionId, ['o:structure' => $newStructure], [], ['isPartial' => true]);
            } catch (Exception $e) {
                return $this->jsonInternalError();
            }
        }

        if ($isMultiple) {
            $data = [
                'selection' => $selection ? $selection->getReference() : null,
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection' => $selection ? $selection->getReference() : null,
                'selection_resource' => reset($results),
            ];
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Toggle select/unselect resource(s) for a selection.
     */
    protected function toggleDb(array $resources, bool $isMultiple, User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        $results = [];

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();
        $userId = $user->getId();

        $selectedResourceIds = $api->search('selection_resources', [
            'owner_id' => $userId,
            'selection_id' => $selectionId,
        ], ['returnScalar' => 'resource'])->getContent();

        $add = [];
        $delete = [];
        foreach ($resources as $resourceId => $resource) {
            if (in_array($resourceId, $selectedResourceIds)) {
                $delete[$resourceId] = $resource;
            } else {
                $add[$resourceId] = $resource;
            }
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $add */
        foreach ($add as $resourceId => $resource) {
            $data = $this->normalizeResource($resource, true, $selectionId);
            $data['status'] = 'success';
            try {
                $api->create('selection_resources', [
                    'o:owner' => ['o:id' => $userId],
                    'o:resource' => ['o:id' => $resourceId],
                    'o:selection' => ['o:id' => $selectionId],
                ])->getContent();
            } catch (Exception $e) {
            }
            $results[$resourceId] = $data;
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $delete */
        foreach ($delete as $resourceId => $resource) {
            $data = $this->normalizeResource($resource, false, $selectionId);
            $data['status'] = 'success';
            try {
                $api->delete('selection_resources', [
                    'owner' => $userId,
                    'resource' => $resourceId,
                    'selection' => $selectionId,
                ]);
            } catch (Exception $e) {
            }
            $results[$resourceId] = $data;
        }

        $oldStructure = $selection->structure();
        $newStructure = $this->addResourcesToStructure($oldStructure, array_keys($add));
        $newStructure = $this->removeResourcesFromStructure($newStructure ?? $oldStructure, array_keys($delete));
        if ($newStructure) {
            try {
                $api->update('selections', $selectionId, ['o:structure' => $newStructure], [], ['isPartial' => true]);
            } catch (Exception $e) {
                return $this->jsonInternalError();
            }
        }

        if ($isMultiple) {
            $data = [
                'selection' => $selection ? $selection->getReference() : null,
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection' => $selection ? $selection->getReference() : null,
                'selection_resource' => reset($results),
            ];
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Move resource(s) between groups of a selection.
     */
    protected function moveDb(array $resources, bool $isMultiple, User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        $structure = $selection->structure();

        $result = $this->moveCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        [$source, $destination] = array_values($result);

        if (strlen($source)) {
            $sourceResources = $selection->resourcesForGroup($source);
            $structure[$source]['resources'] = array_keys(array_diff_key($sourceResources, $resources));
        }

        if (strlen($destination)) {
            if (empty($structure[$destination]['resources'])) {
                $structure[$destination]['resources'] = array_keys($resources);
            } else {
                $structure[$destination]['resources'] = array_merge(array_values($structure[$destination]['resources']), array_keys($resources));
            }
        }

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        try {
            $api->update('selections', $selectionId, ['o:structure' => $structure], [], ['isPartial' => true])->getContent();
        } catch (Exception $e) {
            return $this->jsonInternalError();
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection ? $selection->getReference() : null,
                'source' => $structure[$source] ?? null,
                'group' => $structure[$destination] ?? null,
            ],
        ]);
    }

    /**
     * Add a group to a selection.
     */
    protected function addGroupDb(User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        // Add the group only if it does not exist.
        $structure = $selection->structure();

        $result = $this->addGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        [$group, $structure] = array_values($result);

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        try {
            $api->update('selections', $selectionId, ['o:structure' => $structure], [], ['isPartial' => true])->getContent();
        } catch (Exception $e) {
            return $this->jsonInternalError();
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection ? $selection->getReference() : null,
                'group' => $group,
            ],
        ]);
    }

    /**
     * Rename a group (last part) in a selection.
     */
    protected function renameGroupDb(User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        // Rename the group only if it does not exist.
        $structure = $selection->structure();

        $result = $this->renameGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        $structure = $result;

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        try {
            $api->update('selections', $selectionId, ['o:structure' => $structure], [], ['isPartial' => true])->getContent();
        } catch (Exception $e) {
            return $this->jsonInternalError();
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection ? $selection->getReference() : null,
                'structure' => $selection->structure(),
            ],
        ]);
    }

    /**
     * Move a group in a selection.
     */
    protected function moveGroupDb(User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        // Move the group only if it does not exist.
        $structure = $selection->structure();

        $result = $this->moveGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        $structure = $result;

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        try {
            $api->update('selections', $selectionId, ['o:structure' => $structure], [], ['isPartial' => true])->getContent();
        } catch (Exception $e) {
            return $this->jsonInternalError();
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection ? $selection->getReference() : null,
                'structure' => $selection->structure(),
            ],
        ]);
    }

    /**
     * Delete a group in a selection.
     */
    protected function deleteGroupDb(User $user)
    {
        $selection = $this->checkSelectionFromRouteOrInit($user);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection->id();

        // Delete the group only if it exists.
        $structure = $selection->structure();

        $result = $this->deleteGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        [$selecteds, $structure] = array_values($result);

        /** @var \Omeka\Mvc\Controller\Plugin\Api $api */
        $api = $this->api();

        if ($selecteds) {
            $selectionResourceIds = [];
            foreach ($selection->selectionResources() as $selectionResource) {
                if (in_array($selectionResource->resource()->id(), $selecteds)) {
                    $selectionResourceIds[] = $selectionResource->id();
                }
            }
            if ($selectionResourceIds) {
                $api->batchDelete('selection_resources', $selectionResourceIds);
            }
        }

        try {
            $api->update('selections', $selectionId, ['o:structure' => $structure], [], ['isPartial' => true])->getContent();
        } catch (Exception $e) {
            return $this->jsonInternalError();
        }

        // $this->selectionContainer();

        return new JsonModel([
            'status' => 'success',
            'data' => [
                'selection' => $selection ? $selection->getReference() : null,
                'structure' => $selection->structure(),
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
                // TODO Use a search resources when enabled in omeka.
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

    protected function checkSelectionFromRouteOrInit(User $user): ?SelectionRepresentation
    {
        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $selectionId = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelectionStatic($selectionId, $user);
        if ($selectionId && !$selection) {
            return null;
        }
        return $selection
            ?? $this->getSelectionStaticFirst($user)
            ?? $this->createSelectionStatic($user);
    }

    /**
     * Get a selection or create it.
     */
    protected function getSelectionStaticOrFirstStaticOrCreate(?int $selectionId, User $user): SelectionRepresentation
    {
        return $this->getSelectionStatic($selectionId, $user)
            ?? $this->getSelectionStaticFirst($user)
            ?? $this->createSelectionStatic($user);
    }

    /**
     * Get selection from id.
     */
    protected function getSelectionStatic($id, User $user): ?SelectionRepresentation
    {
        if (!$id) {
            return null;
        }
        try {
            return $this->api()->read('selections', [
                    'id' => $id,
                    'owner' => $user->getId(),
                    'isDynamic' => false,
                ])->getContent();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get default selection, the first non-dynamic one.
     */
    protected function getSelectionStaticFirst(User $user): ?SelectionRepresentation
    {
        return $this->api()->searchOne('selections', [
            'owner_id' => $user->getId(),
            'is_dynamic' => false,
        ])->getContent();
    }

    /**
     * Get default selection, the first non-dynamic one or a new one when none.
     */
    protected function createSelectionStatic(User $user): SelectionRepresentation
    {
        return $api->create('selections', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:label' => $this->translate('My selection'), // @translate
        ])->getContent();
    }
}

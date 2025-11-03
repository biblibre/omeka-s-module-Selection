<?php declare(strict_types=1);

namespace Selection\Controller\Site;

use Selection\Mvc\Controller\Plugin\JSend;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;

/**
 * Manage selections via session for anonymous visitors.
 *
 * There may be multiple selection by user, but only one most of the time.
 * A static selection numbered 1 is created on first action.
 * There may be multiple static selections, but the action is processed on the
 * first selection by default.
 * Dynamic selections (search requests) are managed only via api currently.
 *
 * @todo Replace remote session management by js management and local storage.
 */
trait TraitSessionController
{
    protected function browseSession()
    {
        // Browse the current selection.

        // TODO Query in session is used only for pagination, not implemented yet.
        $query = $this->params()->fromQuery();

        // Read selection from session.
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();

        $selectionId = empty($query['selection_id']) ? 0 : (int) $query['selection_id'];
        $selection = $this->getSelectionFromSessionOrInit($selectionContainer, $selectionId);
        $selectionId = $selection['id'];

        $siteSettings = $this->siteSettings();
        $viewHelpers = $this->viewHelpers();

        if (isset($query['disposition']) && in_array($query['disposition'], ['list', 'hierarchy'])) {
            $disposition = $query['disposition'];
        } else {
            $disposition = $siteSettings->get('selection_browse_disposition') === 'hierarchy' ? 'hierarchy' : 'list';
        }

        $allowIndividualSelect = $siteSettings->get('selection_individual_select', 'no');
        $allowIndividualSelect = ($allowIndividualSelect !== 'no' && $allowIndividualSelect !== 'yes')
            ? $viewHelpers->has('bulkExport') || $viewHelpers->has('contactUs')
            : $allowIndividualSelect === 'yes';

        $view = new ViewModel([
            'site' => $this->currentSite(),
            'user' => null,
            'selectionId' => $selectionId,
            'selections' => $selectionContainer->selections,
            'records' => $selectionContainer->records,
            'isGuestActive' => false,
            'isSession' => true,
            'allowIndividualSelect' => $allowIndividualSelect,
        ]);
        return $view
            ->setTemplate("selection/site/selection/selection-browse-$disposition");
    }

    /**
     * Select resource(s) to add to a selection.
     */
    protected function addSession(array $resources, bool $isMultiple)
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $results = [];

        $selectedRecords = $selectionContainer->records[$selectionId] ?? [];

        $newResources = [];
        foreach ($resources as $resourceId => $resource) {
            if (isset($selectedRecords[$resourceId])) {
                $data = $selectedRecords[$resourceId];
                $data['status'] = 'success';
                $data['data'] = [
                    'message' => $this->translate('Already in'), // @translate
                ];
            } else {
                $data = $this->normalizeResource($resource, true, $selectionId);
                $selectedRecords[$resourceId] = $data;
                $data['status'] = 'success';
                $newResources[] = $resourceId;
            }
            $results[$resourceId] = $data;
        }

        $selectionContainer->records[$selectionId] = $selectedRecords + $results;

        $structure = $selection['structure'];
        $selectionContainer->selections[$selectionId]['structure'] = $this->addResourcesToStructure($structure, $newResources) ?? $structure;

        if ($isMultiple) {
            $data = [
                'selection' => ['o:id' => $selectionId],
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection' => ['o:id' => $selectionId],
                'selection_resource' => reset($results),
            ];
        }

        return $this->selectionJSend(JSend::SUCCESS, $data);
    }

    /**
     * Delete resource(s) from a selection.
     */
    protected function deleteSession(array $resources, bool $isMultiple)
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $results = [];

        $selectedRecords = $selectionContainer->records[$selectionId] ?? [];

        foreach ($resources as $resourceId => $resource) {
            if (!isset($selectedRecords[$resourceId])) {
                // Skip silently.
                $data = ['id' => $resourceId, 'value' => 'unselected'];
                $results[$resourceId] = $data;
                $data['status'] = 'success';
                continue;
            }
            $data = $this->normalizeResource($resource, false, $selectionId);
            $data['status'] = 'success';
            unset($selectedRecords[$resourceId]);
            $results[$resourceId] = $data;
        }

        $selectionContainer->records[$selectionId] = array_diff_key($selectedRecords, $results);

        $structure = $selection['structure'];
        $selectionContainer->selections[$selectionId]['structure'] = $this->removeResourcesFromStructure($structure, array_keys($results)) ?? $structure;

        if ($isMultiple) {
            $data = [
                'selection' => ['o:id' => $selectionId],
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection' => ['o:id' => $selectionId],
                'selection_resource' => reset($results),
            ];
        }

        return $this->selectionJSend(JSend::SUCCESS, $data);
    }

    /**
     * Toggle select/unselect resource(s) for a selection.
     */
    protected function toggleSession(array $resources, bool $isMultiple)
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $results = [];

        $selectedRecords = $selectionContainer->records[$selectionId] ?? [];

        $add = [];
        $delete = [];
        foreach ($resources as $resourceId => $resource) {
            if (isset($selectedRecords[$resourceId])) {
                $delete[$resourceId] = $resource;
            } else {
                $add[$resourceId] = $resource;
            }
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $add */
        foreach ($add as $resourceId => $resource) {
            $data = $this->normalizeResource($resource, true, $selectionId);
            $selectedRecords[$resourceId] = $data;
            $data['status'] = 'success';
            $results[$resourceId] = $data;
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $delete */
        foreach ($delete as $resourceId => $resource) {
            $data = $this->normalizeResource($resource, false, $selectionId);
            $data['status'] = 'success';
            unset($selectedRecords[$resourceId]);
            $results[$resourceId] = $data;
        }

        $selectionContainer->records[$selectionId] = $selectedRecords;

        $structure = $selection['structure'];
        $structure = $this->addResourcesToStructure($structure, array_keys($add)) ?? $structure;
        $structure = $this->removeResourcesFromStructure($structure, array_keys($delete)) ?? $structure;
        $selectionContainer->selections[$selectionId]['structure'] = $structure;

        if ($isMultiple) {
            $data = [
                'selection' => ['o:id' => $selectionId],
                'selection_resources' => $results,
            ];
        } else {
            $data = [
                'selection' => ['o:id' => $selectionId],
                'selection_resource' => reset($results),
            ];
        }

        return $this->selectionJSend(JSend::SUCCESS, $data);
    }

    /**
     * Move resource(s) between groups of a selection.
     */
    protected function moveSession(array $resources, bool $isMultiple)
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $structure = $selection['structure'];

        $result = $this->moveCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        [$source, $destination] = array_values($result);

        if (strlen($source)) {
            $selectedRecords = $selectionContainer->records[$selectionId] ?? [];
            $sourceResources = $this->resourcesForGroupSession($selection, $selectedRecords, $source);
            $structure[$source]['resources'] = array_keys(array_diff_key($sourceResources, $resources));
        }

        if (strlen($destination)) {
            if (empty($structure[$destination]['resources'])) {
                $structure[$destination]['resources'] = array_keys($resources);
            } else {
                $structure[$destination]['resources'] = array_merge(array_values($structure[$destination]['resources']), array_keys($resources));
            }
        }

        $selectionContainer->selections[$selectionId]['structure'] = $structure;

        return $this->selectionJSend(JSend::SUCCESS, [
            'selection' => ['o:id' => $selectionId],
            'source' => $structure[$source] ?? null,
            'group' => $structure[$destination] ?? null,
        ]);
    }

    /**
     * Add a group to a selection.
     */
    protected function addGroupSession()
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $structure = $selection['structure'];

        $result = $this->addGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        [$group, $structure] = array_values($result);

        $selectionContainer->selections[$selectionId]['structure'] = $structure;

        return $this->selectionJSend(JSend::SUCCESS, [
            'selection' => ['o:id' => $selectionId],
            'group' => $group,
        ]);
    }

    /**
     * Rename a group (last part) in a selection.
     */
    protected function renameGroupSession()
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $structure = $selection['structure'];

        $result = $this->renameGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        $structure = $result;

        $selectionContainer->selections[$selectionId]['structure'] = $structure;

        return $this->selectionJSend(JSend::SUCCESS, [
            'selection' => ['o:id' => $selectionId],
            'structure' => $structure,
        ]);
    }

    /**
     * Move a group in a selection.
     */
    protected function moveGroupSession()
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $structure = $selection['structure'];

        $result = $this->moveGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        $structure = $result;

        $selectionContainer->selections[$selectionId]['structure'] = $structure;

        return $this->selectionJSend(JSend::SUCCESS, [
            'selection' => ['o:id' => $selectionId],
            'structure' => $structure,
        ]);
    }

    /**
     * Delete a group in a selection.
     */
    protected function deleteGroupSession()
    {
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $this->selectionContainer();
        $selection = $this->checkSelectionFromRouteOrInitSession($selectionContainer);
        if (!$selection) {
            return $this->jsonErrorNotFound();
        }

        $selectionId = $selection['id'];

        $structure = $selection['structure'];

        $result = $this->deleteGroupCheck($structure);
        if (!is_array($result)) {
            return $result;
        }

        [$selecteds, $structure] = array_values($result);

        if ($selecteds) {
            $selectedRecords = $selectionContainer->records[$selectionId] ?? [];
            $selectionContainer->records[$selectionId] = array_diff_key($selectedRecords, array_flip($selecteds));
        }

        $selectionContainer->selections[$selectionId]['structure'] = $structure;

        return $this->selectionJSend(JSend::SUCCESS, [
            'selection' => ['o:id' => $selectionId],
            'structure' => $structure,
        ]);
    }

    protected function checkSelectionFromRouteOrInitSession(Container $selectionContainer): ?array
    {
        $selectionId = (int) $this->params()->fromRoute('id');
        if ($selectionId && !isset($selectionContainer->selections[$selectionId])) {
            return null;
        }
        return $this->getSelectionFromSessionOrInit($selectionContainer, $selectionId);
    }

    protected function getSelectionFromSessionOrInit(Container $selectionContainer, ?int $selectionId = 0): array
    {
        $selectionId = $selectionId ?: 1;

        // Normally useless: there is always a selection in a container.
        if (isset($selectionContainer->selections[$selectionId])) {
            return $selectionContainer->selections[$selectionId];
        }

        $selection = [
            'id' => $selectionId,
            'label' => $this->siteSettings->get('selection_label', $this->translate('Selection')), // @translate
            'structure' => [],
        ];

        $selectionContainer->selections[$selectionId] = $selection;
        return $selection;
    }

    /**
     * @see \Selection\Api\Representation\SelectionRepresentation::resourcesForGroup()
     */
    protected function resourcesForGroupSession(array $selection, array $selectedRecords, ?string $group): array
    {
        if (!empty($selection['is_dynamic'])) {
            return [];
        }

        $structure = $selection['structure'];
        if (!$structure) {
            return [];
        }

        $group = (string) $group;

        // Get the selected resources or the remaining ones.
        // TODO Optimize process to prepare only needed resources (api call with ids? But types are various).
        if (strlen($group)) {
            $resourceIds = $structure[$group]['resources'] ?? [];
            return $resourceIds
                ? array_intersect_key($selectedRecords, array_flip($resourceIds))
                : [];
        } else {
            $resourceIds = array_merge(...array_column($structure, 'resources'));
            return $resourceIds
                ? array_diff_key($selectedRecords, array_flip($resourceIds))
                : $selectedRecords;
        }
    }
}

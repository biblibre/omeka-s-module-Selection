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

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        } elseif ($user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
        }

        foreach ($resources as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, true, $selection);
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
                        $api->create('selection_resources', [
                            'o:owner' => ['o:id' => $userId],
                            'o:resource' => ['o:id' => $resourceId],
                            'o:selection' => ['o:id' => $selection->id()],
                        ])->getContent();
                    } catch (\Exception $e) {
                    }
                } else {
                    $selection = null;
                }
            }
            $results[$resourceId] = $data;
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

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        }

        foreach ($resources as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, false, $selection);
            $data['status'] = 'success';
            unset($container->records[$resourceId]);
            if ($userId) {
                try {
                    $api->delete('selection_resources', $selection
                        ? [ 'owner' => $userId, 'resource' => $resourceId, 'selection' => $selection->id()]
                        : [ 'owner' => $userId, 'resource' => $resourceId],
                    );
                } catch (\Exception $e) {
                }
            } else {
                $selection = null;
            }
            $results[$resourceId] = $data;
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

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        }

        if ($add && $user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $add */
        foreach ($add as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, true, $selection);
            $data['status'] = 'success';
            $container->records[$resourceId] = $data;
            $results[$resourceId] = $data;
            if ($userId) {
                try {
                    $api->create('selection_resources', [
                        'o:owner' => ['o:id' => $userId],
                        'o:resource' => ['o:id' => $resourceId],
                        'o:selection' => ['o:id' => $selection->id()],
                    ])->getContent();
                } catch (\Exception $e) {
                }
            }
        }
        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] $delete */
        foreach ($delete as $resourceId => $resource) {
            $data = $this->selectionResourceForResource($resource, false, $selection);
            $data['status'] = 'success';
            unset($container->records[$resourceId]);
            $results[$resourceId] = $data;
            if ($userId) {
                try {
                    $api->delete('selection_resources', $selection
                        ? [ 'owner' => $userId, 'resource' => $resourceId, 'selection' => $selection->id()]
                        : [ 'owner' => $userId, 'resource' => $resourceId],
                    );
                } catch (\Exception $e) {
                }
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
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        } elseif ($user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
        }

        $structure = $selection->structure();

        $source = trim((string) $this->params()->fromQuery('group'));
        $destination = trim((string) $this->params()->fromQuery('name'));
        if ($source === $destination) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group name is unchanged.'), // @translate
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
                'selection' => $selection ? $selection->getReference() : null,
                'source' => $structure[$source] ?? null,
                'group' => $structure[$destination] ?? null,
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
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        } elseif ($user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
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
                'selection' => $selection ? $selection->getReference() : null,
                'group' => $group,
            ],
        ]);
    }

    /**
     * Rename a group (last part) in a selection.
     *
     * @todo Factorize with addGroupAction.
     * @todo Factorize with moveGroupAction.
     */
    public function renameGroupAction()
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
        $currentGroupName = basename($path);
        if (!strlen($currentGroupName)) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('No group set.'), // @translate
                ],
            ]);
        }

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

        if ($currentGroupName === $groupName) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group name is unchanged.') // @translate
                ],
            ]);
        }

        $api = $this->api();

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        } elseif ($user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
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

        $currentFullPath = $path;
        $parentPath = dirname($path) === '/' ? '' : dirname($path);

        $fullPath = "$parentPath/$groupName";
        if (isset($structure[$fullPath])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group exists already.'), // @translate
                ],
            ]);
        }

        // Rename the group and all children: even if each group is managed as a
        // full path, this is a structure.
        $s = [];
        foreach ($structure as $sFullPath=> $sGroup) {
            if ($sFullPath === $currentFullPath) {
                $sGroup['id'] = $groupName;
                $s[$fullPath] = $sGroup;
            } elseif (mb_strpos($sFullPath, $currentFullPath . '/') === 0) {
                $sGroup['path'] = $fullPath;
                $s[$fullPath . '/' . $sGroup['id']] = $sGroup;
            } else {
                $s[$sFullPath] = $sGroup;
            }
        }
        $structure = $s;

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
                'selection' => $selection ? $selection->getReference() : null,
                'structure' => $selection->structure(),
            ],
        ]);
    }

    /**
     * Move a group in a selection.
     *
     * @todo Factorize with addGroupAction.
     * @todo Factorize with renameGroupAction.
     */
    public function moveGroupAction()
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

        $source = trim((string) $this->params()->fromQuery('group'));
        $groupName = basename($source);
        if (!strlen($source) || !strlen($groupName)) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('No group set.'), // @translate
                ],
            ]);
        }

        $parentDestination = trim((string) $this->params()->fromQuery('name'));
        if (!strlen($parentDestination)) {
            $parentDestination = '/';
        }

        $destination = ($parentDestination === '/' ? '' : $parentDestination) . '/' . $groupName;
        if ($source === $destination) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group name is unchanged.') // @translate
                ],
            ]);
        }

        if (mb_strpos($destination . '/', $source . '/') === 0) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group cannot be moved inside itself.') // @translate
                ],
            ]);
        }

        $api = $this->api();

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        } elseif ($user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
        }

        // Add the group only if it does not exist.
        $structure = $selection->structure();

        if (!isset($structure[$source])) {
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

        if ($parentDestination !== '/' && !isset($structure[$parentDestination])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => sprintf(
                        $this->translate('The group "%s" does not exist.'), // @translate
                        str_replace('/', ' / ', $parentDestination)
                    ),
                ],
            ]);
        }

        $sourceParentPath = dirname($source);
        $sourceParentPathLength = strlen($sourceParentPath);

        // The group and all sub-groups should be moved at the right place, so prepare them all.
        // Note: normally, the tree of groups is logical: a branch is always
        // after its parent branch.
        $sourceGroups = [];
        if ($parentDestination === '/') {
            foreach ($structure as $sFullPath => $sGroup) {
                if (strpos($sFullPath . '/', $source . '/') === 0) {
                    $newPath = mb_substr($sGroup['path'], $sourceParentPathLength);
                    $sGroup['path'] = $newPath === '' ? '/' : $newPath;
                    $sourceGroups[$newPath . '/' . $sGroup['id']] = $sGroup;
                    unset($structure[$sFullPath]);
                }
            }
        } else {
            foreach ($structure as $sFullPath => $sGroup) {
                if (strpos($sFullPath . '/', $source . '/') === 0) {
                    $newPath = $parentDestination
                        . ($sGroup['path'] === '/' ? '' : mb_substr($sGroup['path'], $sourceParentPathLength));
                    $sGroup['path'] = $newPath;
                    $sourceGroups[$newPath . '/' . $sGroup['id']] = $sGroup;
                    unset($structure[$sFullPath]);
                }
            }
        }

        $s = [];
        // Append.
        if (!isset($structure[$destination]) && $parentDestination === '/') {
            $s = $structure + $sourceGroups;
        }
        // No merge.
        elseif (!isset($structure[$destination])) {
            foreach ($structure as $sFullPath=> $sGroup) {
                $s[$sFullPath] = $sGroup;
                if ($sFullPath === $parentDestination) {
                    $s += $sourceGroups;
                }
            }
        }
        // Merge.
        else {
            foreach ($structure as $sFullPath=> $sGroup) {
                if (isset($s[$sFullPath])) {
                    continue;
                }
                $s[$sFullPath] = $sGroup;
                if ($sFullPath === $parentDestination) {
                    foreach ($sourceGroups as $sourceFullPath => $sourceGroup) {
                        if (isset($structure[$sourceFullPath])) {
                            $s[$sourceFullPath] = $structure[$sourceFullPath];
                            if (empty($s[$sourceFullPath]['resources'])) {
                                $s[$sourceFullPath]['resources'] = $sourceGroup['resources'] ?? [];
                            } elseif (!empty($sourceGroup['resources'])) {
                                $s[$sourceFullPath]['resources'] = array_merge(array_values($s[$sourceFullPath]['resources']), array_values($sourceGroup['resources']));
                            }
                        } else {
                            $s[$sourceFullPath] = $sourceGroup;
                        }
                    }
                }
            }
        }
        $structure = $s;

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
                'selection' => $selection ? $selection->getReference() : null,
                'structure' => $selection->structure(),
            ],
        ]);
    }

    /**
     * Delete a group in a selection.
     *
     * @todo Factorize with addGroupAction.
     */
    public function deleteGroupAction()
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
        if (!strlen($path) || $path === '/') {
            return new JsonModel([
                'status' => 'error',
                'data' => [
                    'message' => $this->translate('No group set.'), // @translate
                ],
            ]);
        }

        $api = $this->api();

        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        $selection = $this->getSelection($id);
        if ($id && !$selection) {
            return $this->jsonErrorNotFound();
        } elseif ($user && !$selection) {
            $selection = $this->defaultStaticSelection($user);
        }

        // Remove the group only if it exists.
        $structure = $selection->structure();

        if (!isset($structure[$path])) {
            return new JsonModel([
                'status' => 'fail',
                'data' => [
                    'message' => $this->translate('The group does not exist.'), // @translate
                ],
            ]);
        }

        // Get selected resources in all children groups.
        $selecteds = [];
        foreach ($structure as $sFullPath => $sGroup) {
            if (strpos($sFullPath . '/', $path . '/') === 0) {
                if (!empty($sGroup['resources'])) {
                    $selecteds = array_merge($selecteds, array_values($sGroup['resources']));
                }
                unset($structure[$sFullPath]);
            }
        }

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
     */
    protected function selectionResourceForResource(
        AbstractResourceEntityRepresentation $resource,
        bool $isSelected,
        ?SelectionRepresentation $selection = null
    ) {
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
            'url_remove' => $selection
                ? $url->fromRoute('site/selection-id', ['site-slug' => $siteSlug, 'action' => 'delete', 'id' => $selection->id()], ['query' => ['id' => $resource->id()]])
                : $url->fromRoute('site/selection', ['site-slug' => $siteSlug, 'action' => 'delete'], ['query' => ['id' => $resource->id()]]),
            // String is required to avoid error in container when the title is
            // a resource.
            'title' => (string) $resource->displayTitle(),
            'value' => $isSelected ? 'selected' : 'unselected',
        ];
    }

    /**
     * Get selection from id.
     */
    protected function getSelection($id): ?SelectionRepresentation
    {
        /** @var \Selection\Api\Representation\SelectionRepresentation $selection */
        $id = (int) $this->params()->fromRoute('id');
        if (!$id) {
            return null;
        }
        try {
            return $this->api()->read('selections', ['id' => $id])->getContent();
        } catch (\Exception $e) {
            return null;
        }
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
            'owner_id' => $user->getId(),
            'is_dynamic' => false,
        ])->getContent();
        if (!$selection) {
            $selection = $api->create('selections', [
                'o:owner' => ['o:id' => $user->getId()],
                'o:label' => $this->translate('My selection'), // @translate
            ])->getContent();
            $selecteds = $api->search('selection_resources', [
                'owner_id' => $user->getId(),
                'selection_id' => 0,
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

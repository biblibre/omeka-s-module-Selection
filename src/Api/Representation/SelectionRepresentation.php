<?php declare(strict_types=1);

/*
 * Copyright Daniel Berthereau, 2020
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

namespace Selection\Api\Representation;

use DateTime;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Representation\UserRepresentation;

class SelectionRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o:Selection';
    }

    public function getJsonLd()
    {
        $searchQuery = $this->searchQuery();

        if ($searchQuery) {
            $selectionResources = null;
            $resources = $this->dynamicResources();
            foreach ($this->resources() as $resource) {
                $resources[] = $resource->getReference();
            }
        } else {
            // The selection resources is useful only to get the date.
            // So another way to present it is to merge the date in the resource
            // reference, but it is not standard.
            $selectionResources = [];
            foreach ($this->selectionResources() as $selectionResource) {
                $selectionResources[] = $selectionResource->getReference();
            }
            $resources = [];
            foreach ($this->resources() as $resource) {
                $resources[] = $resource->getReference();
            }
        }

        $json = [
            'o:owner' => $this->owner()->getReference(),
            'o:is_public' => $this->isPublic(),
            'o:is_dynamic' => $this->isDynamic(),
            'o:label' => $this->label(),
            'o:comment' => $this->comment(),
            'o:search_query' => $searchQuery,
            'o:selection_resources' => $selectionResources,
            'o:resources' => $resources,
            'o:created' => [
                '@value' => $this->getDateTime($this->created()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ],
        ];

        $modified = $this->modified();
        if ($modified) {
            $json['o:modified'] = [
                '@value' => $this->getDateTime($modified),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        return $json;
    }

    public function owner(): UserRepresentation
    {
        $adapter = $this->getAdapter('users');
        return $adapter->getRepresentation($this->resource->getOwner());
    }

    public function isPublic(): bool
    {
        return $this->resource->isPublic();
    }

    public function isDynamic(): bool
    {
        return $this->resource->isDynamic();
    }

    public function label(): ?string
    {
        return $this->resource->getLabel();
    }

    public function comment(): ?string
    {
        return $this->resource->getComment();
    }

    public function searchQuery(): ?string
    {
        return $this->resource->getSearchQuery();
    }

    /**
     * @return SelectionResourceRepresentation[]
     */
    public function selectionResources(): array
    {
        if ($this->isDynamic()) {
            return [];
        }

        $selectionResources = [];
        $selectionResourceAdapter = $this->getAdapter('selection_resources');
        foreach ($this->resource->getSelectionResources() as $selectionResourceEntity) {
            $selectionResources[$selectionResourceEntity->getId()] = $selectionResourceAdapter->getRepresentation($selectionResourceEntity);
        }
        return $selectionResources;
    }

    /**
     * Get list of resources directly without checking each selection element.
     *
     * @return \Omeka\Api\Representation\AbstractResourceEntityRepresentation[]
     */
    public function resources(): array
    {
        if ($this->isDynamic()) {
            return $this->dynamicResources();
        }

        $resources = [];
        $resourceAdapter = $this->getAdapter('resources');
        foreach ($this->resource->getSelectionResources() as $selectionResourceEntity) {
            $resource = $selectionResourceEntity->getResource();
            $resources[$resource->getId()] = $resourceAdapter->getRepresentation($resource);
        }
        return $resources;
    }

    protected function dynamicResources(): array
    {
        // TODO Determine what to output and set a limit.
        if (!$this->isDynamic()) {
            return [];
        }
        return [];
    }

    public function created(): DateTime
    {
        return $this->resource->getCreated();
    }

    public function modified(): ?DateTime
    {
        return $this->resource->getModified();
    }

    public function primaryMedia(): ?MediaRepresentation
    {
        // Return the first media if one exists.
        foreach ($this->resource->getSelectionResources() as $selectionResource) {
            $resource = $selectionResource->getResource();
            if ($resource instanceof \Omeka\Entity\Media) {
                return $this->getAdapter('media')->getRepresentation($resource);
            }
            $media = $this->getAdapter('resources')->getRepresentation($resource)->primaryMedia();
            if ($media) {
                return $media;
            }
        }
        return null;
    }
}

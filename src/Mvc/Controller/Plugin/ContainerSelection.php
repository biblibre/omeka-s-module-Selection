<?php declare(strict_types=1);

namespace Selection\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Container;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class ContainerSelection extends AbstractPlugin
{
    /**
     * Check and get the seleciton list in session container.
     *
     * @return Container
     */
    public function __invoke()
    {
        $controller = $this->getController();

        // Check if the container is ready for the current user.
        $container = new Container('Selection');
        if (empty($container->init)) {
            $container->user = sha1(microtime() . random_bytes(20));
            $container->records = [];
            $container->init = true;
        } elseif (!isset($container->records)) {
            $container->records = [];
        }

        // Sync with the user selected items.
        $user = $controller->identity();
        if ($user) {
            // TODO Add an option to limit size of selection.
            $container->records = [];
            /** @var \Selection\Api\Representation\SelectionItemRepresentation[] $selectionItems*/
            $selectionItems = $controller->api()->search('selection_items', ['user_id' => $user->getId()])->getContent();
            foreach ($selectionItems as $selectionItem) {
                $resource = $selectionItem->resource();
                $container->records[$resource->id()] = $this->selectionItemForResource($resource, true);
            }
        }

        return $container;
    }

    /**
     * Format a resource for the container.
     *
     * Copy in \Selection\Controller\SelectionController::selectionItemForResource()
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param bool $isSelected
     * @return array
     */
    protected function selectionItemForResource(AbstractResourceEntityRepresentation $resource, $isSelected)
    {
        static $siteSlug;
        static $url;
        if (is_null($siteSlug)) {
            $controller = $this->getController();
            $siteSlug = $controller->currentSite()->slug();
            $url = $controller->url();
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
}

<?php declare(strict_types=1);

namespace Selection\View\Helper;

use Exception;
use Laminas\Session\Container;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\User;

class SelectionContainer extends AbstractHelper
{
    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    public function __construct(ApiManager $api)
    {
        $this->api = $api;
    }

    /**
     * Prepare and check the session container for the current visitor or user.
     *
     * There may be multiple selections for a user; but generally, in the user
     * interface, only one is used.
     */
    public function __invoke(): Container
    {
        static $container;

        if ($container !== null) {
            return $container;
        }

        $view = $this->getView();

        $container = new Container('Selection');

        // Always sync session with the user selected resources.
        $user = $view->identity();
        if ($user) {
            $container->init = true;
            $container->selections = $this->normalizeSelections($user);
            $container->records = $this->normalizeResources($user);
        } else {
            $defaultSelection = [
                'id' => 1,
                'label' => $view->siteSetting('selection_label', $view->translate('Selection')), // @translate
                'structure' => [],
            ];
            if (empty($container->init)) {
                $container->init = true;
                $container->selections = [1 => $defaultSelection];
                $container->records = [];
            } else {
                $container->selections ??= [];
                $container->records ??= [];
                if (empty($container->selections)) {
                    $container->selections = [1 => $defaultSelection];
                }
            }
        }

        return $container;
    }

    /**
     * Simplify selections for views. Create a new one if none.
     */
    protected function normalizeSelections(User $user): array
    {
        $view = $this->getView();

        try {
            $selections = $this->api->search('selections', ['owner_id' => $user->getId()])->getContent();
        } catch (Exception $e) {
            $selections = [];
        }

        if (!$selections) {
            $selection = $this->api->create('selections', [
                'o:owner' => ['o:id' => $user->getId()],
                'o:label' => $view->siteSetting('selection_label', $view->translate('Selection')), // @translate
            ])->getContent();
            $selections[] = $selection;
        }

        $result = [];
        foreach ($selections as $selection) {
            $result[$selection->id()] = [
                'id' => $selection->id(),
                'label' => $selection->label(),
                'structure' => $selection->structure(),
            ];
        }
        return $result;
    }

    /**
     * Format all resources to be stored.
     *
     * Adapted in:
     * @see \Selection\View\Helper\SelectionContainer::normalizeResources()
     * @see \Selection\Controller\Site\AbstractSelectionController::normalizeResources()
     */
    protected function normalizeResources(User $user): array
    {
        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $url = $plugins->get('url');
        $siteSlug = $view->currentSite()->slug();

        $siteSetting = $plugins->get('siteSetting');
        $lang = $view->lang();
        $filterLocale = (bool) $siteSetting('filter_locale_values');
        $headingTerm = $siteSetting('browse_heading_property_term');
        $bodyTerm = $siteSetting('browse_body_property_term');
        $defaultLang = $filterLocale ? [$lang, ''] : null;

        try {
            /** @var \Selection\Api\Representation\SelectionResourceRepresentation[] $selectionResources */
            $selectionResources = $this->api->search('selection_resources', ['owner_id' => $user->getId()])->getContent();
        } catch (Exception $e) {
            return [];
        }

        $records = [];
        foreach ($selectionResources as $selectionResource) {
            $resource = $selectionResource->resource();
            if (!$resource) {
                continue;
            }
            $selection = $selectionResource->selection();
            $selectionId = $selection ? $selection->id() : 0;
            $resourceId = $resource->id();
            $title = (string) $resource->displayTitle(null, $defaultLang);
            $description = (string) $resource->displayDescription(null, $defaultLang);
            $heading = $headingTerm ? (string) $resource->value($headingTerm, ['default' => $title]) : $title;
            $body = $bodyTerm ? (string) $resource->value($bodyTerm, ['default' => $description]) : $description;
            $records[$selectionId][$resourceId] = [
                'id' => $resourceId,
                'type' => $resource->getControllerName(),
                'resource_name' => $resource->resourceName(),
                'selection_id' => $selectionId,
                'url' => $resource->siteUrl($siteSlug, true),
                'url_remove' => $selectionId
                    ? $url("site/selection-id", ['site-slug' => $siteSlug, 'action' => 'delete', 'id' => $selectionId], ['query' => ['id' => $resourceId]])
                    : $url("site/selection", ['site-slug' => $siteSlug, 'action' => 'delete'], ['query' => ['id' => $resourceId]]),
                // String is required to avoid error in container when the title
                // is a resource.
                'title' => $title,
                'description' => $description,
                'heading' => $heading,
                'body' => $body,
                // Here, the value is never "unselected" because it is the init
                // of the container.
                'value' => 'selected',
            ];
        }
        return $records;
    }
}

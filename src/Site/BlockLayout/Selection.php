<?php

namespace Selection\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class Selection extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/selection';

    public function getLabel()
    {
        return 'Selection'; // @translate
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['selection']['block_settings']['selection'];
        $blockFieldset = \Selection\Form\Selection::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $user = $view->identity();
        if (!$user) {
            return '';
        }

        $selectionResources = $view->api()->search('selection_resources', ['owner_id' => $user->getId()])->getContent();

        $resourcesByType = [
            'items' => [],
            'item_sets' => [],
            'media' => [],
            'annotations' => [],
        ];

        $resources = [];
        foreach ($selectionResources as $selectionResource) {
            $resource = $selectionResource->resource();
            $resourceType = $classesToTypes[get_class($resource)] ?? 'resources';
            $resourceId = $resource->id();
            $typeAndId = $resourceType . '/' . $resourceId;
            $resources[$typeAndId] = $resource;
            $resourcesByType[$resourceType][$resourceId] = $resource;
        }

        $vars = [
            'site' => $block->page()->site(),
            'block' => $block,
            'selection' => null,
            'selectionResources' => $selectionResources,
            'resources' => $resources,
            'resourcesByType' => $resourcesByType,
            'heading' => $block->dataValue('heading'),
        ];

        $template = $block->dataValue('template', self::PARTIAL_NAME);

        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}

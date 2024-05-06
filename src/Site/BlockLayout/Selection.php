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
        $plugins = $view->getHelperPluginManager();
        $siteSetting = $plugins->get('siteSetting');

        $user = $view->identity();
        $disableAnonymous = (bool) $siteSetting('selection_disable_anonymous');
        if ($disableAnonymous && !$user) {
            return '';
        }

        // TODO Query in session is used only for pagination, not implemented yet.
        $query = $view->params()->fromQuery();

        // Read selection from session. There is always at least one selection.
        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $view->selectionContainer();

        $selectionId = empty($query['selection_id']) ? 0 : (int) $query['selection_id'];
        $selection = $selectionContainer->selections[$selectionId] ?? reset($selectionContainer->selections);
        $selectionId = $selection['id'];

        if (isset($query['disposition']) && in_array($query['disposition'], ['list', 'hierarchy'])) {
            $disposition = $query['disposition'];
        } else {
            $disposition = $block->dataValue('disposition') === 'hierarchy' ? 'hierarchy' : 'list';
        }

        $vars = [
            'site' => $block->page()->site(),
            'block' => $block,
            'user' => $user,
            'selectionId' => $selectionId,
            'selections' => $selectionContainer->selections,
            'records' => $selectionContainer->records,
            'heading' => $block->dataValue('heading'),
            'isGuestActive' => $plugins->has('guestWidget'),
            'isSession' => !$user,
            'disposition' => $disposition,
        ];

        $template = $block->dataValue('template', self::PARTIAL_NAME);

        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}

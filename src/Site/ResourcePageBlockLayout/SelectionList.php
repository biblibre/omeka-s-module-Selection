<?php declare(strict_types=1);

namespace Selection\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class SelectionList implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Selection list'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
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

        /** @var \Laminas\Session\Container $selectionContainer */
        $selectionContainer = $plugins->get('selectionContainer')();

        $selectionId = empty($query['selection_id']) ? 0 : (int) $query['selection_id'];
        $selection = $selectionContainer->selections[$selectionId] ?? reset($selectionContainer->selections);
        $selectionId = $selection['id'];

        /* // Useless here, or add a specific option.
         $allowIndividualSelect = $siteSetting('selection_individual_select', 'auto');
        $allowIndividualSelect = ($allowIndividualSelect !== 'no' && $allowIndividualSelect !== 'yes')
            ? $plugins->has('bulkExport') || $plugins->has('contactUs')
            : $allowIndividualSelect === 'yes';
        */
        $allowIndividualSelect = false;

        return $view->partial('common/resource-page-block-layout/selection-list', [
            'site' => $view->layout()->site,
            'resource' => $resource,
            'user' => $user,
            'selectionId' => $selectionId,
            'selections' => $selectionContainer->selections,
            'records' => $selectionContainer->records,
            'isGuestActive' => $plugins->has('guestWidget'),
            'isSession' => !$user,
            'allowIndividualSelect' => $allowIndividualSelect,
        ]);
    }
}

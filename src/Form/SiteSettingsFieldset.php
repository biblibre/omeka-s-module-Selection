<?php declare(strict_types=1);

namespace Selection\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    /**
     * @var string
     */
    protected $label = 'Selection module'; // @translate

    protected $elementGroups = [
        'selection' => 'Selection', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'selection')
            ->setOption('element_groups', $this->elementGroups)
            ->add([
                'name' => 'selection_disable_anonymous',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'selection',
                    'label' => 'Disable selection stored in session for anonymous visitors', // @translate
                ],
                'attributes' => [
                    'id' => 'selection_disable_anonymous',
                ],
            ])
            ->add([
                'name' => 'selection_selectable_resources',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'selection',
                    'label' => 'Limit selections to specific resources', // @translate
                    'value_options' => [
                        'items' => 'Items', // @translate
                        'media' => 'Medias', // @translate
                        'item_sets' => 'Item sets', // @translate
                        // 'annotations' => 'Annotations',
                    ],
                ],
                'attributes' => [
                    'id' => 'selection_selectable_resources',
                ],
            ])
            ->add([
                'name' => 'selection_resource_show_open',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'selection',
                    'label' => 'Open the selection block by default in resource page', // @translate
                ],
                'attributes' => [
                    'id' => 'selection_resource_show_open',
                ],
            ])
            ->add([
                'name' => 'selection_resource_show_open_list',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'selection',
                    'label' => 'Open the selection list block by default in resource page', // @translate
                ],
                'attributes' => [
                    'id' => 'selection_resource_show_open_list',
                ],
            ])
            // TODO Add a third view with a button to let user choose disposition.
            ->add([
                'name' => 'selection_browse_disposition',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => 'selection',
                    'label' => 'Disposition of page Selection', // @translate
                    'value_options' => [
                        'list' => 'List', // @translate
                        'hierarchy' => 'Hierarchy', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'selection_browse_disposition',
                ],
            ])
        ;
    }
}

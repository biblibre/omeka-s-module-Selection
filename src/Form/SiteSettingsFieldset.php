<?php declare(strict_types=1);

namespace Selection\Form;

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
                'name' => 'selection_open',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'selection',
                    'label' => 'Open the selection block by default', // @translate
                    'info' => 'If enabled, the selection block on resource pages is open by default', // @translate
                ],
                'attributes' => [
                    'id' => 'selection_open',
                ],
            ])
        ;
    }
}

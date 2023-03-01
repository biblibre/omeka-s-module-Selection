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

    public function init(): void
    {
        $this
            ->add([
                'name' => 'selection_visitor_allow',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Enable session selection for visitors', // @translate
                    'info' => 'The selection is automatically saved for logged users.', // @translate
                ],
                'attributes' => [
                    'id' => 'selection_visitor_allow',
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

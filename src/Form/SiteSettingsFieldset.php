<?php
namespace Selection\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    /**
     * @var string
     */
    protected $label = 'Selection module'; // @translate

    public function init()
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
        ;
    }
}

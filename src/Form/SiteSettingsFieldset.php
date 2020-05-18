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
                ],
                'attributes' => [
                    'id' => 'selection_visitor_allow',
                ],
            ])
            ->add([
                'name' => 'selection_user_fill_main',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'For authenticated users, fill the main selection directly', // @translate
                ],
                'attributes' => [
                    'id' => 'selection_user_fill_main',
                ],
            ])
        ;
    }
}

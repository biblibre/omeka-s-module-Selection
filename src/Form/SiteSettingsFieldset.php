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
        ;
    }
}

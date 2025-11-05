<?php declare(strict_types=1);

namespace Selection\Form;

use Selection\Form\Element\OptionalRadio;
use Laminas\Form\Fieldset;

class SelectionFieldset extends Fieldset
{
    public function init(): void
    {
        // Attachments fields are managed separately.

        $this
            // TODO Add a third view with a button to let user choose disposition.
            // TODO Move this to a layout block-template?
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][disposition]',
                'type' => OptionalRadio::class,
                'options' => [
                    'label' => 'Disposition', // @translate
                    'value_options' => [
                        'list' => 'List', // @translate
                        'hierarchy' => 'Hierarchy', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'selection-disposition',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][individual_select]',
                'type' => OptionalRadio::class,
                'options' => [
                    'label' => 'Add individual checkboxes for modules Bulk Export and Contact Us', // @translate
                    'value_options' => [
                        'no' => 'No', // @translate
                        'yes' => 'Yes', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'selection-individual_select',
                ],
            ]);
    }
}

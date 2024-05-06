<?php declare(strict_types=1);

namespace Selection\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class Selection extends Fieldset
{
    public function init(): void
    {
        // Attachments fields are managed separately.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'selection-heading',
                ],
            ])
            // TODO Add a third view with a button to let user choose disposition.
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][disposition]',
                'type' => CommonElement\OptionalRadio::class,
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
            ]);

        if (class_exists('BlockPlus\Form\Element\TemplateSelect')) {
            $this
                ->add([
                    'name' => 'template',
                    'type' => \BlockPlus\Form\Element\TemplateSelect::class,
                    'options' => [
                        'label' => 'Template to display', // @translate
                        'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "selection".', // @translate
                        'template' => 'common/block-layout/selection',
                    ],
                    'attributes' => [
                        'id' => 'selection-template',
                        'class' => 'chosen-select',
                    ],
                ]);
            }
    }
}

<?php
return  [
    'form_elements' => [
        'factories' => [

        ],
    ],
    'controllers' => [
        'factories' => [
            'Basket\Controller\Index' => 'Basket\Service\Controller\BasketIndexControllerFactory',
        ]
    ],
   'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
    ],


    'view_helpers' => [
        'invokables' => [

        ],
    ],
    'service_manager' => [
        'factories' => [

        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'basket' => [
                        'type' => 'segment',
                        'options' => [
                            'route' => '/basket/:action[/:id]',
                            'defaults' => [
                                '__NAMESPACE__' => 'Basket\Controller',
                                'controller' => 'Index',
                            ],

                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view/public/',

        ]
    ],

    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];

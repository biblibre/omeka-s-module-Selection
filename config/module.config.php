<?php

return  [
    'controllers' => [
        'factories' => [
            'Basket\Controller\Index' => 'Basket\Service\Controller\BasketIndexControllerFactory',
        ],
    ],
   'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'basket_items' => 'Basket\Api\Adapter\BasketItemAdapter',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'showBasketLink' => 'Basket\View\Helper\ShowBasketLink',
        ],
        'factories' => [
            'updateBasketLink' => 'Basket\Service\ViewHelper\UpdateBasketLinkFactory',
        ],
    ],
    'router' => [
        'routes' => [
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
            'site' => [
                'child_routes' => [
                    'basket' => [
                        'type' => 'segment',
                        'options' => [
                            'route' => '/basket',
                            'defaults' => [
                                '__NAMESPACE__' => 'Basket\Controller',
                                'controller' => 'Index',
                                'action' => 'show',
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
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];

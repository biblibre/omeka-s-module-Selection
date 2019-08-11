<?php
namespace Basket;

return  [
    'api_adapters' => [
        'invokables' => [
            'basket_items' => Api\Adapter\BasketItemAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view/public/',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'showBasketLink' => View\Helper\ShowBasketLink::class,
        ],
        'factories' => [
            'updateBasketLink' => Service\ViewHelper\UpdateBasketLinkFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'Basket\Controller\Index' => Service\Controller\BasketIndexControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'basket' => [
                        'type' => \Zend\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/basket',
                            'defaults' => [
                                '__NAMESPACE__' => 'Basket\Controller',
                                'controller' => 'Index',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'basket-update' => [
                        'type' => \Zend\Router\Http\Segment::class,
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
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];

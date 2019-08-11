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
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'showBasketLink' => View\Helper\ShowBasketLink::class,
            'updateBasketLink' => View\Helper\UpdateBasketLink::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Basket\Controller\Index' => Controller\IndexController::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'basket' => Site\Navigation\Link\Basket::class,
        ],
    ],
    'navigation' => [
        'site' => [
            [
                'label' => 'Basket', // @translate
                'route' => 'site/basket',
                'controller' => 'Basket\Controller\Index',
                'action' => 'basket',
                'useRouteMatch' => true,
                'visible' => false,
            ],
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

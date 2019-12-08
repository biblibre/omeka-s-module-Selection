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
            'Basket\Controller\Site\Basket' => Controller\Site\BasketController::class,
            'Basket\Controller\Site\GuestBoard' => Controller\Site\GuestBoardController::class,
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
                'route' => 'site/guest/basket',
                'controller' => 'Basket\Controller\Site\GuestBoard',
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
                            'route' => '/basket[/:action]',
                            'constraints' => [
                                'action' => 'add|delete',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Basket\Controller\Site',
                                'controller' => 'Basket',
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'basket-id' => [
                        'type' => \Zend\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/basket/:id[/:action]',
                            'constraints' => [
                                'action' => 'add|delete',
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Basket\Controller\Site',
                                'controller' => 'Basket',
                                'action' => 'update',
                            ],
                        ],
                    ],
                    'guest' => [
                        // The default values for the guest user route are kept
                        // to avoid issues for visitors when an upgrade of
                        // module Guest occurs or when it is disabled.
                        'type' => \Zend\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/guest',
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'basket' => [
                                'type' => \Zend\Router\Http\Literal::class,
                                'options' => [
                                    'route' => '/basket',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Basket\Controller\Site',
                                        'controller' => 'GuestBoard',
                                        'action' => 'show',
                                    ],
                                ],
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

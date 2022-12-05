<?php declare(strict_types=1);

namespace Selection;

return  [
    'api_adapters' => [
        'invokables' => [
            'selections' => Api\Adapter\SelectionAdapter::class,
            'selection_resources' => Api\Adapter\SelectionResourceAdapter::class,
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
            'selectionButtonToggle' => View\Helper\SelectionButtonToggle::class,
            'selectionLinkBrowse' => View\Helper\SelectionLinkBrowse::class,
        ],
        'aliases' => [
            // @deprecated
            'showSelectionLink' => 'selectionLinkBrowse',
            'updateSelectionLink' => 'selectionButtonToggle',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SiteSettingsFieldset::class => Form\SiteSettingsFieldset::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Selection\Controller\Site\Selection' => Controller\Site\SelectionController::class,
            'Selection\Controller\Site\GuestBoard' => Controller\Site\GuestBoardController::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'selectionContainer' => Mvc\Controller\Plugin\SelectionContainer::class,
        ],
        'aliases' => [
            'containerSelection' => 'selectionContainer',
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'selection' => Site\Navigation\Link\Selection::class,
        ],
    ],
    'navigation' => [
        'site' => [
            [
                'label' => 'Selection', // @translate
                'route' => 'site/guest/selection',
                'controller' => 'Selection\Controller\Site\GuestBoard',
                'action' => 'selection',
                'useRouteMatch' => true,
                'visible' => false,
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'selection' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/selection[/:action]',
                            'constraints' => [
                                // Set groups first for regex.
                                'action' => 'add-group|rename-group|move-group|delete-group|add|delete|move|toggle',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Selection\Controller\Site',
                                'controller' => 'Selection',
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'selection-id' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/selection/:id[/:action]',
                            'constraints' => [
                                'action' => 'add-group|rename-group|move-group|delete-group|add|delete|move|toggle',
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Selection\Controller\Site',
                                'controller' => 'Selection',
                                'action' => 'toggle',
                            ],
                        ],
                    ],
                    'guest' => [
                        // The default values for the guest user route are kept
                        // to avoid issues for visitors when an upgrade of
                        // module Guest occurs or when it is disabled.
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/guest',
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'selection' => [
                                'type' => \Laminas\Router\Http\Literal::class,
                                'options' => [
                                    'route' => '/selection',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Selection\Controller\Site',
                                        'controller' => 'GuestBoard',
                                        'action' => 'resource-browse',
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
    'selection' => [
        'site_settings' => [
            'selection_visitor_allow' => true,
        ],
    ],
];

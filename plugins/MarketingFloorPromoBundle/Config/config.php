<?php


return [
    'name'        => 'Marketing floor promo',
    'description' => 'Drive visitor\'s focus on your website with Marketing Floor promo',
    'version'     => '1.0',
    'author'      => 'Anoop',

    'routes' => [
        'main' => [
            'marketingfloor_promo_index' => [
                'path'       => '/promo/{page}',
                'controller' => 'MarketingFloorPromoBundle:Promo:index',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'marketingfloor.promo' => [
                'route'    => 'marketingfloor_promo_index',
                'access'   => 'plugin:promo:items:view',
                'parent'   => 'mautic.core.channels',
                'priority' => 15,
            ],
        ],
    ],
    
      'categories' => [
          'plugin:focus' => 'mautic.focus',
      ],

      'parameters' => [
          'website_snapshot_url' => 'https://mautic.net/api/snapshot',
          'website_snapshot_key' => '',
      ],

  ];

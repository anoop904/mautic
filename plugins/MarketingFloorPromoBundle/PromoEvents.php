<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketingFloorPromoBundle;

/**
 * Class FocusEvents.
 *
 * Events available for MauticFocusBundle
 */
final class PromoEvents
{
    /**
     * The mautic.promo_pre_save event is dispatched right before a promo is persisted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const PRE_SAVE = 'mautic.promo_pre_save';

    /**
     * The mautic.promo_post_save event is dispatched right after a promo is persisted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const POST_SAVE = 'mautic.promo_post_save';

    /**
     * The mautic.promo_pre_delete event is dispatched before a promo is deleted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const PRE_DELETE = 'mautic.promo_pre_delete';

    /**
     * The mautic.promo_post_delete event is dispatched after a promo is deleted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const POST_DELETE = 'mautic.promo_post_delete';
}

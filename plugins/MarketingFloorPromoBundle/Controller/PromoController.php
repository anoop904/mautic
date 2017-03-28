<?php


namespace MauticPlugin\MarketingFloorPromoBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\Response;

class PromoController extends FormController
{

	public function __construct()
	{
			$this->setStandardParameters(
					'promo',
					'plugin:promo:items',
					'marketingfloor_promo',
					'marketingfloor_promo',
					'marketingfloor.promo',
					'MarketingFloorPromoBundle:Promo',
					null,
					'promo'
			);
	}

	public function indexAction($page = 1)
    {
    	return $this->delegateView(
            array(
                'contentTemplate' => 'MarketingFloorPromoBundle:Promo:index.html.php',
            )
        );
    }
}

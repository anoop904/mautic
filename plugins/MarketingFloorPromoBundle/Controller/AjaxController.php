<?php


namespace MauticPlugin\MarketingFloorPromoBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticPromoBundle\Model\PromoModel;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getWebsiteSnapshotAction(Request $request)
    {
        $data = ['success' => 0];

        if ($this->get('mautic.security')->isGranted('plugin:promo:items:create')) {
            $website = InputHelper::url($request->request->get('website'));

            if ($website) {
                // Let's try to extract colors from image
                $id = InputHelper::int($request->request->get('id'));
                if (!empty($id)) {
                    // Tell the JS to not populate with default colors
                    $data['ignoreDefaultColors'] = true;
                }

                $snapshotUrl = $this->get('mautic.helper.core_parameters')->getParameter('website_snapshot_url');
                $snapshotKey = $this->get('mautic.helper.core_parameters')->getParameter('website_snapshot_key');

                $http     = $this->get('mautic.http.connector');
                $response = $http->get($snapshotUrl.'?url='.urlencode($website).'&key='.$snapshotKey, [], 30);

                if ($response->code === 200) {
                    $package = json_decode($response->body, true);
                    if (isset($package['images'])) {
                        $data['image']['desktop'] = $package['images']['desktop'];
                        $data['image']['mobile']  = $package['images']['mobile'];
                        $palette                  = $package['palette'];
                        $data['colors']           = [
                            'primaryColor'    => $palette[0],
                            'textColor'       => PromoModel::isLightColor($palette[0]) ? '#000000' : '#ffffff',
                            'buttonColor'     => $palette[1],
                            'buttonTextColor' => PromoModel::isLightColor($palette[1]) ? '#000000' : '#ffffff',
                        ];
                        $data['success'] = 1;
                    }
                }
            }
        }

        return $this->sendJsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function generatePreviewAction(Request $request)
    {
        $data  = ['html' => '', 'style' => ''];
        $promo = $request->request->all();

        if (isset($promo['promo'])) {
            $promoArray = InputHelper::_($promo['promo']);

            if (!empty($promoArray['style']) && !empty($promoArray['type'])) {
                /** @var \MauticPlugin\MarketingFloorPromoBundle\Model\PromoModel $model */
                $model            = $this->getModel('promo');
                $promoArray['id'] = 'preview';
                $data             = $model->generateJavascript($promoArray, true);
            }
        }

        return $this->sendJsonResponse($data);
    }
}

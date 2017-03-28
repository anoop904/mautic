<?php



namespace MauticPlugin\MarketingFloorPromoBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Model\TrackableModel;
//use MauticPlugin\MarketingFloorPromoBundle\Entity\Promo;
//use MauticPlugin\MarketingFloorPromoBundle\Entity\Stat;
//use MauticPlugin\MarketingFloorPromoBundle\Event\PromoEvent;
//use MauticPlugin\MarketingFloorPromoBundle\PromoEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class PromoModel extends FormModel
{
    /**
     * @var \Mautic\FormBundle\Model\FormModel
     */
    protected $formModel;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var TemplatingHelper
     */
    protected $templating;

    /**
     * PromoModel constructor.
     *
     * @param \Mautic\FormBundle\Model\FormModel $formModel
     * @param TrackableModel                     $trackableModel
     * @param TemplatingHelper                   $templating
     */
    public function __construct(\Mautic\FormBundle\Model\FormModel $formModel, TrackableModel $trackableModel, TemplatingHelper $templating)
    {
        $this->formModel      = $formModel;
        $this->trackableModel = $trackableModel;
        $this->templating     = $templating;
    }

    /**
     * @return string
     */
    public function getActionRouteBase()
    {
        return 'promo';
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'plugin:promo:items';
    }

    /**
     * {@inheritdoc}
     *
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param null                                $action
     * @param array                               $options
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Promo) {
            throw new MethodNotAllowedHttpException(['Promo']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('promo', $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MarketingFloorPromoBundle\Entity\PromoRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MarketingFloorPromoBundle:Promo');
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MarketingFloorPromoBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MarketingFloorPromoBundle:Stat');
    }

    /**
     * {@inheritdoc}
     *
     * @param null $id
     *
     * @return Promo
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Promo();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param Promo      $entity
     * @param bool|false $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);

        // Generate cache after save to have ID available
        $content = $this->generateJavascript($entity);
        $entity->setCache($content);

        $this->getRepository()->saveEntity($entity);
    }

    /**
     * Obtains the cached JS of a form and generates it if missing.
     *
     * @param Promo $promo
     *
     * @return string
     */
    public function getContent(Promo $promo)
    {
        $cached = $promo->getCache();

        if (empty($cached)) {
            $cached = $this->generateJavascript($promo);
            $promo->setCache($cached);
            $this->saveEntity($promo);
        }

        return $cached;
    }

    /**
     * @param      $promo
     * @param bool $preview
     * @param bool $ignoreMinify
     *
     * @return string
     */
    public function generateJavascript($promo, $preview = false, $ignoreMinify = false)
    {
        if ($promo instanceof Promo) {
            $promo = $promo->toArray();
        }

        if (!empty($promo['form'])) {
            $form = $this->formModel->getEntity($promo['form']);
        } else {
            $form = null;
        }

        if ($preview) {
            $content = [
                'style' => '',
                'html'  => $this->templating->getTemplating()->render(
                    'MarketingFloorPromoBundle:Builder:content.html.php',
                    [
                        'promo'   => $promo,
                        'form'    => $form,
                        'preview' => $preview,
                    ]
                ),
            ];
        } else {
            // Generate link if applicable
            $url = '';
            if ($promo['type'] == 'link') {
                $trackable = $this->trackableModel->getTrackableByUrl(
                    $promo['properties']['content']['link_url'],
                    'promo',
                    $promo['id']
                );

                $url = $this->trackableModel->generateTrackableUrl($trackable, ['channel' => ['promo', $promo['id']]]);
            }

            $content = $this->templating->getTemplating()->render(
                'MarketingFloorPromoBundle:Builder:generate.js.php',
                [
                    'promo'        => $promo,
                    'form'         => $form,
                    'preview'      => $preview,
                    'ignoreMinify' => $ignoreMinify,
                    'clickUrl'     => $url,
                ]
            );

            if (!$ignoreMinify) {
                $content = \JSMin::minify($content);
            }
        }

        return $content;
    }

    /**
     * Get whether the color is light or dark.
     *
     * @param $hex
     * @param $level
     *
     * @return bool
     */
    public static function isLightColor($hex, $level = 200)
    {
        $hex = str_replace('#', '', $hex);
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));

        $compareWith = ((($r * 299) + ($g * 587) + ($b * 114)) / 1000);

        return $compareWith >= $level;
    }

    /**
     * Add a stat entry.
     *
     * @param Promo $promo
     * @param       $type
     * @param null  $data
     * @param null  $lead
     */
    public function addStat(Promo $promo, $type, $data = null, $lead = null)
    {
        switch ($type) {
            case Stat::TYPE_FORM:
                /** @var \Mautic\FormBundle\Entity\Submission $data */
                $typeId = $data->getId();
                break;
            case Stat::TYPE_NOTIFICATION:
                /** @var Request $data */
                $typeId = null;
                break;
            case Stat::TYPE_CLICK:
                /** @var \Mautic\PageBundle\Entity\Hit $data */
                $typeId = $data->getId();
                break;
        }

        $stat = new Stat();
        $stat->setPromo($promo)
            ->setDateAdded(new \DateTime())
            ->setType($type)
            ->setTypeId($typeId)
            ->setLead($lead);

        $this->getStatRepository()->saveEntity($stat);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|PromoEvent|void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Promo) {
            throw new MethodNotAllowedHttpException(['Promo']);
        }

        switch ($action) {
            case 'pre_save':
                $name = PromoEvents::PRE_SAVE;
                break;
            case 'post_save':
                $name = PromoEvents::POST_SAVE;
                break;
            case 'pre_delete':
                $name = PromoEvents::PRE_DELETE;
                break;
            case 'post_delete':
                $name = PromoEvents::POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PromoEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Promo          $promo
     * @param                $unit
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param null           $dateFormat
     * @param bool           $canViewOthers
     *
     * @return array
     */
    public function getStats(Promo $promo, $unit, \DateTime $dateFrom = null, \DateTime $dateTo = null, $dateFormat = null, $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $unit);

        $q = $query->prepareTimeDataQuery('promo_stats', 'date_added');
        if (!$canViewOthers) {
            $this->limitQueryToCreator($q);
        }
        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.promo.graph.views'), $data);

        if ($promo->getType() != 'notification') {
            if ($promo->getType() == 'link') {
                $q = $query->prepareTimeDataQuery('promo_stats', 'date_added', ['type' => Stat::TYPE_CLICK]);
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                $chart->setDataset($this->translator->trans('mautic.promo.graph.clicks'), $data);
            } else {
                $q = $query->prepareTimeDataQuery('promo_stats', 'date_added', ['type' => Stat::TYPE_FORM]);
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                $chart->setDataset($this->translator->trans('mautic.promo.graph.submissions'), $data);
            }
        }

        return $chart->render();
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder $q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'promo', 'm', 'e.id = t.promo_id')
            ->andWhere('m.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }
}

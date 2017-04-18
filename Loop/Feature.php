<?php

namespace OptimizeThelia\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\FeatureI18nQuery;
use Thelia\Model\FeatureQuery;
use Thelia\Model\ProductQuery;
use Thelia\Type\BooleanOrBothType;
use Thelia\Model\FeatureTemplateQuery;
use Thelia\Model\TemplateQuery;
use Thelia\Model\Map\FeatureTemplateTableMap;

/**
 *
 * Feature loop
 *
 */
class Feature extends \Thelia\Core\Template\Loop\Feature
{
    public function buildModelCriteria()
    {
        $search = FeatureQuery::create();

        /* manage translations */
        $this->configureI18nProcessing($search);

        $id = $this->getId();

        if (null !== $id) {
            $search->filterById($id, Criteria::IN);
        }

        $exclude = $this->getExclude();

        if (null !== $exclude) {
            $search->filterById($exclude, Criteria::NOT_IN);
        }

        $visible = $this->getVisible();

        if ($visible != BooleanOrBothType::ANY) {
            $search->filterByVisible($visible);
        }

        $product = $this->getProduct();
        $template = $this->getTemplate();
        $excludeTemplate = $this->getExcludeTemplate();

        $this->useFeaturePosition = true;

        if (null !== $product) {
            // Find all template assigned to the products.
            $products = ProductQuery::create()->findById($product);

            // Ignore if the product cannot be found.
            if ($products !== null) {
                // Create template array
                if ($template == null) {
                    $template = array();
                }

                foreach ($products as $product) {
                    if (!$this->getBackendContext()) {
                        $search
                            ->useFeatureProductQuery()
                                ->filterByProduct($product)
                            ->endUse()
                        ;
                    }
                    $tplId = $product->getTemplateId();

                    if (! is_null($tplId)) {
                        $template[] = $tplId;
                    }
                }
            }

            // franck@cqfdev.fr - 05/12/2013 : if the given product has no template
            // or if the product cannot be found, do not return anything.
            if (empty($template)) {
                return null;
            }

        }

        if (! empty($template)) {
            // Join with feature_template table to get position
            $search
                ->withColumn(FeatureTemplateTableMap::POSITION, 'position')
                ->filterByTemplate(TemplateQuery::create()->findById($template), Criteria::IN)
            ;

            $this->useFeaturePosition = false;
        }

        if (null !== $excludeTemplate) {
            $search
                ->filterById(
                    FeatureTemplateQuery::create()->filterByTemplateId($excludeTemplate)->select('feature_id')->find(),
                    Criteria::NOT_IN
                )
            ;
        }

        $title = $this->getTitle();

        if (null !== $title) {
            //find all feature that match exactly this title and find with all locales.
            $features = FeatureI18nQuery::create()
                ->filterByTitle($title, Criteria::LIKE)
                ->select('id')
                ->find();

            if ($features) {
                $search->filterById(
                    $features,
                    Criteria::IN
                );
            }
        }

        $orders  = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case "id":
                    $search->orderById(Criteria::ASC);
                    break;
                case "id_reverse":
                    $search->orderById(Criteria::DESC);
                    break;
                case "alpha":
                    $search->addAscendingOrderByColumn('i18n_TITLE');
                    break;
                case "alpha-reverse":
                    $search->addDescendingOrderByColumn('i18n_TITLE');
                    break;
                case "manual":
                    if ($this->useFeaturePosition) {
                        $search->orderByPosition(Criteria::ASC);
                    } else {
                        $search->addAscendingOrderByColumn(FeatureTemplateTableMap::POSITION);
                    }
                    break;
                case "manual_reverse":
                    if ($this->useFeaturePosition) {
                        $search->orderByPosition(Criteria::DESC);
                    } else {
                        $search->addDescendingOrderByColumn(FeatureTemplateTableMap::POSITION);
                    }
                    break;
            }
        }

        return $search;
    }
}

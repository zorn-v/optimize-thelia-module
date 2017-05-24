<?php

namespace OptimizeThelia\Loop;

use Thelia\Core\Template\Loop\FeatureAvailability as BaseFeatureAV;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

class FeatureAvailability extends BaseFeatureAV
{

    public function buildModelCriteria()
    {
        $search = parent::buildModelCriteria();

        $search->useFeatureProductQuery()
            ->filterByFreeTextValue(null)
        ->endUse();

        return $search;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var FeatureAv $featureAv */
        foreach ($loopResult->getResultDataCollection() as $featureAv) {
            $loopResultRow = new LoopResultRow($featureAv);
            $loopResultRow->set("ID", $featureAv->getId())
                ->set("IS_TRANSLATED", $featureAv->getVirtualColumn('IS_TRANSLATED'))
                ->set("LOCALE", $this->locale)
                ->set("FEATURE_ID", $featureAv->getFeatureId())
                ->set("TITLE", $featureAv->getVirtualColumn('i18n_TITLE'))
                ->set("CHAPO", $featureAv->getVirtualColumn('i18n_CHAPO'))
                ->set("DESCRIPTION", $featureAv->getVirtualColumn('i18n_DESCRIPTION'))
                ->set("POSTSCRIPTUM", $featureAv->getVirtualColumn('i18n_POSTSCRIPTUM'))
                ->set("POSITION", $featureAv->getPosition());
            $this->addOutputFields($loopResultRow, $featureAv);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}

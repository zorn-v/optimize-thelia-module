<?php

namespace OptimizeThelia\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Exception\TaxEngineException;
use Thelia\Model\ProductQuery;
use Thelia\Type;
use OptimizeThelia\TaxEngine\Calculator;

class Product extends \Thelia\Core\Template\Loop\Product
{
    public function parseSimpleResults(LoopResult $loopResult)
    {
        $taxCalculator = new Calculator();
        $taxCountry = $this->container->get('thelia.taxEngine')->getDeliveryCountry();
        /** @var \Thelia\Core\Security\SecurityContext $securityContext */
        $securityContext = $this->container->get('thelia.securityContext');

        /** @var \Thelia\Model\Product $product */
        foreach ($loopResult->getResultDataCollection() as $product) {
            $loopResultRow = new LoopResultRow($product);
            $price = $product->getVirtualColumn('price');

            if ($securityContext->hasCustomerUser() && $securityContext->getCustomerUser()->getDiscount() > 0) {
                $price = $price * (1-($securityContext->getCustomerUser()->getDiscount()/100));
            }

            try {
                $taxRule = $product->getTaxRule();
                $taxedPrice = round($taxCalculator->load($product, $taxCountry)->getTaxedPrice($price), 2);
            } catch (TaxEngineException $e) {
                $taxedPrice = null;
            }
            $promoPrice = $product->getVirtualColumn('promo_price');

            if ($securityContext->hasCustomerUser() && $securityContext->getCustomerUser()->getDiscount() > 0) {
                $promoPrice = $promoPrice * (1-($securityContext->getCustomerUser()->getDiscount()/100));
            }
            try {
                $taxedPromoPrice = round($taxCalculator->load($product, $taxCountry)->getTaxedPrice($price), 2);
            } catch (TaxEngineException $e) {
                $taxedPromoPrice = null;
            }
            // Find previous and next product, in the default category.
            $default_category_id = $product->getVirtualColumn('DefaultCategoryId');

            $loopResultRow
                ->set("WEIGHT", $product->getVirtualColumn('weight'))
                ->set("QUANTITY", $product->getVirtualColumn('quantity'))
                ->set("EAN_CODE", $product->getVirtualColumn('ean_code'))
                ->set("BEST_PRICE", $product->getVirtualColumn('is_promo') ? $promoPrice : $price)
                ->set("BEST_PRICE_TAX", $taxedPrice - $product->getVirtualColumn('is_promo') ? $taxedPromoPrice - $promoPrice : $taxedPrice - $price)
                ->set("BEST_TAXED_PRICE", $product->getVirtualColumn('is_promo') ? $taxedPromoPrice : $taxedPrice)
                ->set("PRICE", $price)
                ->set("PRICE_TAX", $taxedPrice - $price)
                ->set("TAXED_PRICE", $taxedPrice)
                ->set("PROMO_PRICE", $promoPrice)
                ->set("PROMO_PRICE_TAX", $taxedPromoPrice - $promoPrice)
                ->set("TAXED_PROMO_PRICE", $taxedPromoPrice)
                ->set("IS_PROMO", $product->getVirtualColumn('is_promo'))
                ->set("IS_NEW", $product->getVirtualColumn('is_new'))
                ->set("PRODUCT_SALE_ELEMENT", $product->getVirtualColumn('pse_id'))
                ->set("PSE_COUNT", $product->getVirtualColumn('pse_count'))
            ;
            $this->addOutputFields($loopResultRow, $product);

            $loopResult->addRow($this->associateValues($loopResultRow, $product, $default_category_id));
        }

        return $loopResult;
    }

    public function parseComplexResults(LoopResult $loopResult)
    {
        $taxCalculator = new Calculator();
        $taxCountry = $this->container->get('thelia.taxEngine')->getDeliveryCountry();

        /** @var \Thelia\Core\Security\SecurityContext $securityContext */
        $securityContext = $this->container->get('thelia.securityContext');

        /** @var \Thelia\Model\Product $product */
        foreach ($loopResult->getResultDataCollection() as $product) {
            $loopResultRow = new LoopResultRow($product);

            $price = $product->getRealLowestPrice();

            if ($securityContext->hasCustomerUser() && $securityContext->getCustomerUser()->getDiscount() > 0) {
                $price = $price * (1-($securityContext->getCustomerUser()->getDiscount()/100));
            }

            try {
                $taxedPrice = round($taxCalculator->load($product, $taxCountry)->getTaxedPrice($price), 2);
            } catch (TaxEngineException $e) {
                $taxedPrice = null;
            }

            // Find previous and next product, in the default category.
            $default_category_id = $product->getVirtualColumn('DefaultCategoryId');

            $loopResultRow
                ->set("BEST_PRICE", $price)
                ->set("BEST_PRICE_TAX", $taxedPrice - $price)
                ->set("BEST_TAXED_PRICE", $taxedPrice)
                ->set("IS_PROMO", $product->getVirtualColumn('main_product_is_promo'))
                ->set("IS_NEW", $product->getVirtualColumn('main_product_is_new'))
            ;

            $loopResult->addRow($this->associateValues($loopResultRow, $product, $default_category_id));
        }

        return $loopResult;
    }

    private function associateValues($loopResultRow, $product, $default_category_id)
    {
        $display_initial_price = $product->getVirtualColumn('display_initial_price');

        if (is_null($display_initial_price)) {
            $display_initial_price = 1;
        }

        $loopResultRow
            ->set("ID", $product->getId())
            ->set("REF", $product->getRef())
            ->set("IS_TRANSLATED", $product->getVirtualColumn('IS_TRANSLATED'))
            ->set("LOCALE", $this->locale)
            ->set("TITLE", $product->getVirtualColumn('i18n_TITLE'))
            ->set("CHAPO", $product->getVirtualColumn('i18n_CHAPO'))
            ->set("DESCRIPTION", $product->getVirtualColumn('i18n_DESCRIPTION'))
            ->set("POSTSCRIPTUM", $product->getVirtualColumn('i18n_POSTSCRIPTUM'))
            ->set("URL", $product->getUrl($this->locale))
            ->set("META_TITLE", $product->getVirtualColumn('i18n_META_TITLE'))
            ->set("META_DESCRIPTION", $product->getVirtualColumn('i18n_META_DESCRIPTION'))
            ->set("META_KEYWORDS", $product->getVirtualColumn('i18n_META_KEYWORDS'))
            ->set("POSITION", $product->getPosition())
            ->set("VIRTUAL", $product->getVirtual() ? "1" : "0")
            ->set("VISIBLE", $product->getVisible() ? "1" : "0")
            ->set("TEMPLATE", $product->getTemplateId())
            ->set("DEFAULT_CATEGORY", $default_category_id)
            ->set("TAX_RULE_ID", $product->getTaxRuleId())
            ->set("BRAND_ID", $product->getBrandId() ?: 0)
            ->set("SHOW_ORIGINAL_PRICE", $display_initial_price)
        ;

        if ($this->getBackend_context() || $this->getWithPrevNextInfo()) {
            $visible = $this->getWithPrevNextVisible();

            // Find previous and next category
            $previousSearch = ProductQuery::create()
                ->joinProductCategory()
                ->where('ProductCategory.category_id = ?', $default_category_id)
                ->filterByPosition($product->getPosition(), Criteria::LESS_THAN)
                ->orderByPosition(Criteria::DESC)
            ;

            if ($visible !== Type\BooleanOrBothType::ANY) {
                $previousSearch->filterByVisible($visible ? 1 : 0);
            }

            $previous = $previousSearch->findOne();

            $nextSearch = ProductQuery::create()
                ->joinProductCategory()
                ->where('ProductCategory.category_id = ?', $default_category_id)
                ->filterByPosition($product->getPosition(), Criteria::GREATER_THAN)
                ->orderByPosition(Criteria::ASC)
            ;

            if ($visible !== Type\BooleanOrBothType::ANY) {
                $nextSearch->filterByVisible($visible ? 1 : 0);
            }

            $next = $nextSearch->findOne();

            $loopResultRow
                ->set("HAS_PREVIOUS", $previous != null ? 1 : 0)
                ->set("HAS_NEXT", $next != null ? 1 : 0)
                ->set("PREVIOUS", $previous != null ? $previous->getId() : -1)
                ->set("NEXT", $next != null ? $next->getId() : -1)
            ;
        }

        return $loopResultRow;
    }

    public function buildModelCriteria()
    {
        $search = parent::buildModelCriteria();
        $search
            ->leftJoin('ProductCategory')
            ->addJoinCondition('ProductCategory', 'ProductCategory.DefaultCategory = 1')
            ->withColumn('COALESCE(ProductCategory.CategoryId, 0)', 'DefaultCategoryId');
        ;
        return $search;
    }
}

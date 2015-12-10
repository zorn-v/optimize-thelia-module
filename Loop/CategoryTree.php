<?php

namespace OptimizeThelia\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Model\CategoryQuery;
use Thelia\Type\TypeCollection;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;
use Thelia\Core\Template\Element\BaseI18nLoop;

class CategoryTree extends \Thelia\Core\Template\Loop\CategoryTree
{
    private $categories;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('category', null, true),
            Argument::createIntTypeArgument('depth', PHP_INT_MAX),
            Argument::createBooleanTypeArgument('need_count_child', false),
            Argument::createBooleanTypeArgument('need_url', false),
            Argument::createBooleanOrBothTypeArgument('visible', true, false),
            Argument::createIntListTypeArgument('exclude', array()),
            new Argument(
                'order',
                new TypeCollection(
                    new Type\EnumListType(array('position', 'position_reverse', 'id', 'id_reverse', 'alpha', 'alpha_reverse'))
                ),
                'position'
            )
        );
    }

    // changement de rubrique
    protected function buildCategoryTree($parent, $visible, $level, $previousLevel, $maxLevel, $exclude, &$resultsList)
    {
        if ($level > $maxLevel) {
            return;
        }

        if ($this->categories === null) {
            $this->categories = [];

            $search = CategoryQuery::create();
            $this->configureI18nProcessing($search, array('TITLE'));

            if ($visible !== BooleanOrBothType::ANY) {
                $search->filterByVisible($visible);
            }

            if ($exclude != null) {
                $search->filterById($exclude, Criteria::NOT_IN);
            }

            $orders  = $this->getOrder();

            foreach ($orders as $order) {
                switch ($order) {
                    case "position":
                        $search->orderByPosition(Criteria::ASC);
                        break;
                    case "position_reverse":
                        $search->orderByPosition(Criteria::DESC);
                        break;
                    case "id":
                        $search->orderById(Criteria::ASC);
                        break;
                    case "id_reverse":
                        $search->orderById(Criteria::DESC);
                        break;
                    case "alpha":
                        $search->addAscendingOrderByColumn('i18n_TITLE');
                        break;
                    case "alpha_reverse":
                        $search->addDescendingOrderByColumn('i18n_TITLE');
                        break;
                }
            }

            $results = $search->find();

            $needCountChild = $this->getNeedCountChild();
            $needUrl = $this->getNeedUrl();

            foreach ($results as $result) {
                if (!isset($this->categories[$result->getParent()])) {
                    $this->categories[$result->getParent()] = [];
                }
                $row = [
                    "ID" => $result->getId(),
                    "TITLE" => $result->getVirtualColumn('i18n_TITLE'),
                    "PARENT" => $result->getParent(),
                    "VISIBLE" => $result->getVisible() ? "1" : "0",
                ];
                if ($needUrl) {
                    $row['URL'] = $result->getUrl($this->locale);
                }
                if ($needCountChild) {
                    $row['CHILD_COUNT'] = $result->countChild();
                }
                $this->categories[$result->getParent()][] = $row;
            }
        }
        if (isset($this->categories[$parent])) {
            foreach ($this->categories[$parent] as $category) {
                $row = $category;
                $row['LEVEL'] = $level;
                $row['PREV_LEVEL'] = $previousLevel;

                $resultsList[] = $row;

                $this->buildCategoryTree($row['ID'], $visible, 1 + $level, $level, $maxLevel, $exclude, $resultsList);
            }
        }
    }

    public function buildArray()
    {
        $id = $this->getCategory();
        $depth = $this->getDepth();
        $visible = $this->getVisible();
        $exclude = $this->getExclude();

        $resultsList = array();

        $this->categories = null;
        $this->buildCategoryTree($id, $visible, 0, 0, $depth, $exclude, $resultsList);

        return $resultsList;
    }
}

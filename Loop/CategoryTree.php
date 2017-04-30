<?php

namespace OptimizeThelia\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
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

    protected function buildCategoryTree($parent, $visible, $level, $previousLevel, $maxLevel, $exclude, &$resultsList)
    {
        if ($level > $maxLevel) {
            return;
        }

        if ($this->categories === null) {
            $categories = $this->container->get('category.cache.service')->getCategoryTree();
            $locale = $this->getCurrentRequest()->getSession()->getLang()->getLocale();
            $this->categories = $categories[$locale];

            $orders  = $this->getOrder();

            foreach ($this->categories as $parentId=>$children) {
                $sortId = [];
                $sortPosition = [];
                $sortTitle = [];
                foreach ($children as $k => $v) {
                    if ($visible !== BooleanOrBothType::ANY) {
                        if ($visible && $v['VISIBLE'] == '0') {
                            unset($this->categories[$parentId][$k]);
                            continue;
                        }
                        if (!$visible && $v['VISIBLE'] == '1') {
                            unset($this->categories[$parentId][$k]);
                            continue;
                        }
                    }
                    if (null !== $exclude && in_array($v['ID'], $exclude)) {
                        unset($this->categories[$parentId][$k]);
                        continue;
                    }

                    $sortId[$k] = $v['ID'];
                    $sortPosition[$k] = $v['POSITION'];
                    $sortTitle[$k] = $v['TITLE'];
                }

                switch ($orders[0]) {
                    case "position":
                        array_multisort($sortPosition, SORT_ASC, SORT_NUMERIC, $this->categories[$parentId]);
                        break;
                    case "position_reverse":
                        array_multisort($sortPosition, SORT_DESC, SORT_NUMERIC, $this->categories[$parentId]);
                        break;
                    case "id":
                        array_multisort($sortId, SORT_ASC, SORT_NUMERIC, $this->categories[$parentId]);
                        break;
                    case "id_reverse":
                        array_multisort($sortId, SORT_DESC, SORT_NUMERIC, $this->categories[$parentId]);
                        break;
                    case "alpha":
                        array_multisort($sortTitle, SORT_ASC, $this->categories[$parentId]);
                        break;
                    case "alpha_reverse":
                        array_multisort($sortTitle, SORT_DESC, $this->categories[$parentId]);
                        break;
                }

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

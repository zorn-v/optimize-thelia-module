<?php

namespace OptimizeThelia\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\Common\Cache\FilesystemCache;
use Thelia\Model\CategoryQuery;
use Thelia\Model\LangQuery;

class CategoryCache extends ContainerAware
{
    const CATEGORY_TREE = 'category.tree';

    private $cache;

    public function __construct($cacheDir)
    {
        $this->cache = new FilesystemCache($cacheDir);
    }

    public function getCategoryTree()
    {
        $categories = $this->cache->fetch(self::CATEGORY_TREE);
        if ($categories === false) {
            $categories = $this->generate();
        }
        return $categories;
    }

    public function generate()
    {
        $categories = [];
        $categoryQuery = CategoryQuery::create();
        $categoryQuery
            ->withColumn('(SELECT COUNT(*) FROM category ChildCategory WHERE ChildCategory.parent=category.id)', 'ChildCount')
            ->withColumn('(SELECT COUNT(*) FROM product_category WHERE product_category.category_id=category.id)', 'ProductCount')
        ;
        $results = $categoryQuery->find();
        foreach ($results as $result) {
            $langs = LangQuery::create()
                ->filterByActive(true)
            ->find();
            foreach ($langs as $lang) {
                $categories[$lang->getLocale()][$result->getParent()][$result->getId()] = [
                    'ID' => $result->getId(),
                    'PARENT' => $result->getParent(),
                    'VISIBLE' => $result->getVisible() ? '1' : '0',
                    'POSITION' => $result->getPosition(),
                    'CHILD_COUNT' => $result->getVirtualColumn('ChildCount'),
                    'PRODUCT_COUNT' => $result->getVirtualColumn('ProductCount'),
                    'TITLE' => $result->setLocale($lang->getLocale())->getTitle(),
                    'URL' => $result->getUrl($lang->getLocale()),
                ];
            }
        }
        $this->cache->save(self::CATEGORY_TREE, $categories);
        return $categories;
    }

    public function clear()
    {
        $this->cache->delete(self::CATEGORY_TREE);
    }
}

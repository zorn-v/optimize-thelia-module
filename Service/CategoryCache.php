<?php

namespace OptimizeThelia\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\Common\Cache\FilesystemCache;
use Thelia\Model\CategoryQuery;

class CategoryCache extends ContainerAware
{
    private $cache;

    public function __construct($cacheDir)
    {
        $this->cache = new FilesystemCache($cacheDir);
    }

    public function getCategoryTree()
    {
        $categories = $this->cache->fetch('category.tree');
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
            $categories[$result->getParent()][$result->getId()] = [
                'ID' => $result->getId(),
                'PARENT' => $result->getParent(),
                'VISIBLE' => $result->getVisible() ? "1" : "0",
                'CHILD_COUNT' => $result->getVirtualColumn('ChildCount'),
                'PRODUCT_COUNT' => $result->getVirtualColumn('ProductCount')
            ];
        }
        $this->cache->save('category.tree', $categories);
        return $categories;
    }
}

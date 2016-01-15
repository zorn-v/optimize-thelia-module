<?php

namespace OptimizeThelia\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\Common\Cache\FilesystemCache;

class CategoryCache extends ContainerAware
{
    private $cache;

    public function __construct($cacheDir)
    {
        $this->cache = new FilesystemCache($cacheDir);
    }

    public function getCategories()
    {
        $categories = $this->cache->fetch('category.cache');
        if ($categories === false) {
            $categories = $this->generate();
        }
        return $categories;
    }

    public function generate()
    {
        
    }
}

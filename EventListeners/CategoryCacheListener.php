<?php

namespace OptimizeThelia\EventListeners;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\TheliaEvents;

class CategoryCacheListener extends ContainerAware implements EventSubscriberInterface
{
    private $categoryCache;

    public function __construct($categoryCache)
    {
        $this->categoryCache = $categoryCache;
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::CATEGORY_CREATE => 'clearCache',
            TheliaEvents::CATEGORY_UPDATE => 'clearCache',
            TheliaEvents::CATEGORY_DELETE => 'clearCache',
            TheliaEvents::CATEGORY_TOGGLE_VISIBILITY => 'clearCache',
            TheliaEvents::CATEGORY_UPDATE_POSITION => 'clearCache',
            TheliaEvents::PRODUCT_CREATE => 'clearCache',
            TheliaEvents::PRODUCT_UPDATE => 'clearCache',
            TheliaEvents::PRODUCT_DELETE => 'clearCache',
            TheliaEvents::PRODUCT_ADD_CATEGORY => 'clearCache',
            TheliaEvents::PRODUCT_REMOVE_CATEGORY => 'clearCache',
            TheliaEvents::PRODUCT_TOGGLE_VISIBILITY => 'clearCache',
            TheliaEvents::LANG_UPDATE => 'clearCache',
            TheliaEvents::LANG_CREATE => 'clearCache',
            TheliaEvents::LANG_DELETE => 'clearCache',
            TheliaEvents::LANG_TOGGLEACTIVE => 'clearCache',
        ];
    }

    public function clearCache()
    {
        $this->categoryCache->clear();
    }
}

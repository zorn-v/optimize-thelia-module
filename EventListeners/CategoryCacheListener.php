<?php

namespace OptimizeThelia\EventListeners;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\TheliaEvents;

class CategoryCacheListener extends ContainerAware implements EventSubscriberInterface
{
    public function __construct($container)
    {
        $this->setContainer($container);
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::CATEGORY_CREATE => 'clearCache',
            TheliaEvents::CATEGORY_UPDATE => 'clearCache',
            TheliaEvents::CATEGORY_DELETE => 'clearCache',
            TheliaEvents::PRODUCT_CREATE => 'clearCache',
            TheliaEvents::PRODUCT_UPDATE => 'clearCache',
            TheliaEvents::PRODUCT_DELETE => 'clearCache',
            TheliaEvents::PRODUCT_ADD_CATEGORY => 'clearCache',
            TheliaEvents::PRODUCT_REMOVE_CATEGORY => 'clearCache'
        ];
    }

    public function clearCache()
    {
        $this->container->get('category.cache.service')->generate();
    }
}
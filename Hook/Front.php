<?php

namespace OptimizeThelia\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class Front extends BaseHook
{
    public function onCategorySidebarBody(HookRenderEvent $event)
    {
        $content = $this->render('category-sidebar.html');
        $event->add($content);
    }
}

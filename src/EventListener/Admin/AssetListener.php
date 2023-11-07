<?php

namespace SocialData\Connector\LinkedIn\EventListener\Admin;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::CSS_PATHS => 'addCssFiles',
            BundleManagerEvents::JS_PATHS  => 'addJsFiles',
        ];
    }

    public function addCssFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/socialdatalinkedinconnector/css/admin.css'
        ]);
    }

    public function addJsFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/socialdatalinkedinconnector/js/connector/linkedin-connector.js',
            '/bundles/socialdatalinkedinconnector/js/feed/linkedin-feed.js',
        ]);
    }
}

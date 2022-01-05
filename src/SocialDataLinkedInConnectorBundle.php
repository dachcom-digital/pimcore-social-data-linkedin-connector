<?php

namespace SocialData\Connector\LinkedIn;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class SocialDataLinkedInConnectorBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/social-data-linkedin-connector';

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/socialdatalinkedinconnector/css/admin.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/socialdatalinkedinconnector/js/connector/linkedin-connector.js',
            '/bundles/socialdatalinkedinconnector/js/feed/linkedin-feed.js',
        ];
    }
}

<?php

namespace SocialData\Connector\LinkedIn;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class SocialDataLinkedInConnectorBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    const PACKAGE_NAME = 'dachcom-digital/social-data-linkedin-connector';

    /**
     * {@inheritdoc}
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

    /**
     * @return array
     */
    public function getCssPaths()
    {
        return [
            '/bundles/socialdatalinkedinconnector/css/admin.css'
        ];
    }

    /**
     * @return string[]
     */
    public function getJsPaths()
    {
        return [
            '/bundles/socialdatalinkedinconnector/js/connector/linkedin-connector.js',
            '/bundles/socialdatalinkedinconnector/js/feed/linkedin-feed.js',
        ];
    }
}

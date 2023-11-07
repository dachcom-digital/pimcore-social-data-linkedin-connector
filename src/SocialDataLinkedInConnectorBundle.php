<?php

namespace SocialData\Connector\LinkedIn;

use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SocialDataLinkedInConnectorBundle extends Bundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/social-data-linkedin-connector';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}

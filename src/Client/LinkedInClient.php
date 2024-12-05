<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace SocialData\Connector\LinkedIn\Client;

use SocialData\Connector\LinkedIn\Model\EngineConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LinkedInClient
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getClient(EngineConfiguration $configuration): LinkedInSDK
    {
        return new LinkedInSDK([
            'api_key'      => $configuration->getClientId(),
            'api_secret'   => $configuration->getClientSecret(),
            'callback_url' => $this->generateConnectUri()
        ]);
    }

    protected function generateConnectUri(): string
    {
        return $this->urlGenerator->generate('social_data_connector_linkedin_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}

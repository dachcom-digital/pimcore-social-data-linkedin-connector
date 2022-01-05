<?php

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

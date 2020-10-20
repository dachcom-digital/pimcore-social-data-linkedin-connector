<?php

namespace SocialData\Connector\LinkedIn\Model;

use SocialDataBundle\Connector\ConnectorEngineConfigurationInterface;
use SocialData\Connector\LinkedIn\Form\Admin\Type\LinkedInEngineType;

class EngineConfiguration implements ConnectorEngineConfigurationInterface
{
    /**
     * @var string
     *
     * @internal
     */
    protected $accessToken;

    /**
     * @var null|\DateTime
     *
     * @internal
     */
    protected $accessTokenExpiresAt;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * {@inheritdoc}
     */
    public static function getFormClass()
    {
        return LinkedInEngineType::class;
    }

    /**
     * @param string $token
     * @param bool   $forceUpdate
     */
    public function setAccessToken($token, $forceUpdate = false)
    {
        // symfony: if there are any fields on the form that aren’t included in the submitted data,
        // those fields will be explicitly set to null.
        if ($token === null && $forceUpdate === false) {
            return;
        }

        $this->accessToken = $token;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param null|\DateTime $expiresAt
     * @param bool           $forceUpdate
     */
    public function setAccessTokenExpiresAt($expiresAt, $forceUpdate = false)
    {
        // symfony: if there are any fields on the form that aren’t included in the submitted data,
        // those fields will be explicitly set to null.
        if ($expiresAt === null && $forceUpdate === false) {
            return;
        }

        $this->accessTokenExpiresAt = $expiresAt;
    }

    /**
     * @return null|\DateTime
     */
    public function getAccessTokenExpiresAt()
    {
        return $this->accessTokenExpiresAt;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }
}

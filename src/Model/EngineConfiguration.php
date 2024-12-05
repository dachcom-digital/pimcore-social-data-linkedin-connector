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

namespace SocialData\Connector\LinkedIn\Model;

use SocialData\Connector\LinkedIn\Form\Admin\Type\LinkedInEngineType;
use SocialDataBundle\Connector\ConnectorEngineConfigurationInterface;

class EngineConfiguration implements ConnectorEngineConfigurationInterface
{
    /**
     * @internal
     */
    protected ?string $accessToken = null;

    /**
     * @internal
     */
    protected ?\DateTime $accessTokenExpiresAt = null;

    protected ?string $clientId;
    protected ?string $clientSecret;

    public static function getFormClass(): string
    {
        return LinkedInEngineType::class;
    }

    public function setAccessToken(?string $token, bool $forceUpdate = false): void
    {
        // symfony: if there are any fields on the form that are not included in the submitted data,
        // those fields will be explicitly set to null.
        if ($token === null && $forceUpdate === false) {
            return;
        }

        $this->accessToken = $token;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessTokenExpiresAt(?\DateTime $expiresAt, bool $forceUpdate = false): void
    {
        // symfony: if there are any fields on the form that are not included in the submitted data,
        // those fields will be explicitly set to null.
        if ($expiresAt === null && $forceUpdate === false) {
            return;
        }

        $this->accessTokenExpiresAt = $expiresAt;
    }

    public function getAccessTokenExpiresAt(): ?\DateTime
    {
        return $this->accessTokenExpiresAt;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }
}

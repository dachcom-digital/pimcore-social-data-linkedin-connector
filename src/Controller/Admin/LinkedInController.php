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

namespace SocialData\Connector\LinkedIn\Controller\Admin;

use Carbon\Carbon;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use SocialData\Connector\LinkedIn\Client\LinkedInClient;
use SocialData\Connector\LinkedIn\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LinkedInController extends AdminAbstractController
{
    use ConnectResponseTrait;

    public function __construct(
        protected LinkedInClient $linkedInClient,
        protected EnvironmentServiceInterface $environmentService,
        protected ConnectorServiceInterface $connectorService
    ) {
    }

    public function connectAction(Request $request): Response
    {
        try {
            $connectorDefinition = $this->getConnectorDefinition();
            $connectorEngineConfig = $this->getConnectorEngineConfig($connectorDefinition);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        $client = $this->linkedInClient->getClient($connectorEngineConfig);
        $definitionConfiguration = $connectorDefinition->getDefinitionConfiguration();

        $loginUrl = $client->getLoginUrl($definitionConfiguration['api_connect_permission']);

        return $this->redirect($loginUrl);
    }

    /**
     * @throws \Exception
     */
    public function checkAction(Request $request): Response
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        if ($request->query->has('error')) {
            return $this->buildConnectErrorResponse(500, $request->query->get('error'), 'connection error', $request->query->get('error_description', 'Unknown Error'));
        }

        $client = $this->linkedInClient->getClient($connectorEngineConfig);

        $accessToken = $client->getAccessToken($request->query->get('code'));
        $tokenExpiresIn = $client->getAccessTokenExpiration();

        if (!$accessToken) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'invalid access token', $request->query->get('error_message', 'Unknown Error'));
        }

        $expireDate = Carbon::now();
        $expireDate->addSeconds($tokenExpiresIn);

        $connectorEngineConfig->setAccessToken($accessToken, true);
        $connectorEngineConfig->setAccessTokenExpiresAt($expireDate->toDateTime(), true);
        $this->connectorService->updateConnectorEngineConfiguration('linkedIn', $connectorEngineConfig);

        return $this->buildConnectSuccessResponse();
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function debugTokenAction(Request $request): JsonResponse
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        if (empty($connectorEngineConfig->getAccessToken())) {
            return $this->adminJson(['error' => true, 'message' => 'acccess token is empty']);
        }

        try {
            $payload = [
                'client_id'     => $connectorEngineConfig->getClientId(),
                'client_secret' => $connectorEngineConfig->getClientSecret(),
                'token'         => $connectorEngineConfig->getAccessToken(),
            ];

            $client = $this->linkedInClient->getClient($connectorEngineConfig);
            $accessTokenMetadata = $client->fetchOAuth('introspectToken', $payload, 'POST');
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        if (isset($accessTokenMetadata['authorized_at'])) {
            $accessTokenMetadata['authorized_at'] = Carbon::createFromTimestamp($accessTokenMetadata['authorized_at'])->toDayDateTimeString();
        }

        if (isset($accessTokenMetadata['created_at'])) {
            $accessTokenMetadata['created_at'] = Carbon::createFromTimestamp($accessTokenMetadata['created_at'])->toDayDateTimeString();
        }

        if (isset($accessTokenMetadata['expires_at'])) {
            $accessTokenMetadata['expires_at'] = Carbon::createFromTimestamp($accessTokenMetadata['expires_at'])->toDayDateTimeString();
        }

        return $this->adminJson([
            'success' => true,
            'data'    => $accessTokenMetadata
        ]);
    }

    protected function getConnectorDefinition(): ConnectorDefinitionInterface
    {
        $connectorDefinition = $this->connectorService->getConnectorDefinition('linkedIn', true);

        if (!$connectorDefinition->engineIsLoaded()) {
            throw new HttpException(400, 'Engine is not loaded.');
        }

        return $connectorDefinition;
    }

    protected function getConnectorEngineConfig(ConnectorDefinitionInterface $connectorDefinition): EngineConfiguration
    {
        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();
        if (!$connectorEngineConfig instanceof EngineConfiguration) {
            throw new HttpException(400, 'Invalid linkedIn configuration. Please configure your connector "linkedIn" in backend first.');
        }

        return $connectorEngineConfig;
    }
}

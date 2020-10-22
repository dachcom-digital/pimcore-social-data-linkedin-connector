<?php

namespace SocialData\Connector\LinkedIn\Controller\Admin;

use Carbon\Carbon;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use SocialData\Connector\LinkedIn\Model\EngineConfiguration;
use SocialData\Connector\LinkedIn\Client\LinkedInClient;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LinkedInController extends AdminController
{
    use ConnectResponseTrait;

    /**
     * @var LinkedInClient
     */
    protected $linkedInClient;

    /**
     * @var EnvironmentServiceInterface
     */
    protected $environmentService;

    /**
     * @var ConnectorServiceInterface
     */
    protected $connectorService;

    /**
     * @param LinkedInClient              $linkedInClient
     * @param EnvironmentServiceInterface $environmentService
     * @param ConnectorServiceInterface   $connectorService
     */
    public function __construct(
        LinkedInClient $linkedInClient,
        EnvironmentServiceInterface $environmentService,
        ConnectorServiceInterface $connectorService
    ) {
        $this->linkedInClient = $linkedInClient;
        $this->environmentService = $environmentService;
        $this->connectorService = $connectorService;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function connectAction(Request $request)
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
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function checkAction(Request $request)
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
    public function debugTokenAction(Request $request)
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

        if (!is_array($accessTokenMetadata)) {
            return $this->adminJson(['error' => true, 'message' => 'invalid token data']);
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

    /**
     * @return ConnectorDefinitionInterface
     */
    protected function getConnectorDefinition()
    {
        $connectorDefinition = $this->connectorService->getConnectorDefinition('linkedIn', true);

        if (!$connectorDefinition->engineIsLoaded()) {
            throw new HttpException(400, 'Engine is not loaded.');
        }

        return $connectorDefinition;
    }

    /**
     * @param ConnectorDefinitionInterface $connectorDefinition
     *
     * @return EngineConfiguration
     */
    protected function getConnectorEngineConfig(ConnectorDefinitionInterface $connectorDefinition)
    {
        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();
        if (!$connectorEngineConfig instanceof EngineConfiguration) {
            throw new HttpException(400, 'Invalid linkedIn configuration. Please configure your connector "linkedIn" in backend first.');
        }

        return $connectorEngineConfig;
    }
}

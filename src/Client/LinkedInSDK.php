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

/**
 * Adapted from https://github.com/ashwinks/PHP-LinkedIn-SDK.
 */
class LinkedInSDK
{
    protected const API_BASE = 'https://api.linkedin.com/v2';
    protected const OAUTH_BASE = 'https://www.linkedin.com/oauth/v2';
    public const R_AD_CAMPAIGNS = 'r_ad_campaigns';                        // View advertising campaigns you manage
    public const R_ADS = 'r_ads';                                          // Retrieve your advertising accounts
    public const R_ADS_LEADGEN_AUTOMATION = 'r_ads_leadgen_automation';    // Access your Lead Gen Forms and retrieve leads
    public const R_ADS_REPORTING = 'r_ads_reporting';                      // Retrieve reporting for your advertising accounts
    public const R_EMAILADDRESS = 'r_emailaddress';                        // Use the primary email address associated with your LinkedIn account
    public const R_LITEPROFILE = 'r_liteprofile';                          // Use your name, headline, and photo
    public const R_BASICPROFILE = 'r_basicprofile';                        // Required to retrieve name, photo, headline, and vanity name for the authenticated user. Please review Basic Profile Fields. Note that the v2 r_basicprofile permission grants only a subset of fields provided in v1.
    public const R_MEMBER_SOCIAL = 'r_member_social';                      // Retrieve your posts, comments, likes, and other engagement data
    public const R_ORGANIZATION_SOCIAL = 'r_organization_social';          // Retrieve your organizations' posts, including any comments, likes and other engagement data
    public const RW_AD_CAMPAIGNS = 'rw_ad_campaigns';                      // Manage your advertising campaigns
    public const RW_ADS = 'rw_ads';                                        // Manage your advertising accounts
    public const RW_DMP_SEGMENTS = 'rw_dmp_segments';                      // Create and manage your matched audiences
    public const RW_ORGANIZATION_ADMIN = 'rw_organization_admin';          // Manage your organizations' pages and retrieve reporting data
    public const RW_ORGANIZATION = 'rw_organization';                      // Manage your organization's page and post updates
    public const W_MEMBER_SOCIAL = 'w_member_social';                      // Post, comment and like posts on your behalf
    public const W_ORGANIZATION_SOCIAL = 'w_organization_social';          // Post, comment and like posts on your organization's behalf
    protected const HTTP_METHOD_GET = 'GET';
    protected const HTTP_METHOD_POST = 'POST';
    protected const HTTP_METHOD_PUT = 'PUT';
    protected const HTTP_METHOD_DELETE = 'DELETE';

    protected array $config = [];
    protected ?string $state = null;
    protected ?string $accessToken = null;
    protected ?string $accessTokenExpires = null;
    protected ?array $debugInfo = null;
    protected ?\CurlHandle $curlHandle = null;

    /**
     * @param array $config (api_key, api_secret, callback_url)
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(array $config)
    {
        if (!isset($config['api_key']) || empty($config['api_key'])) {
            throw new \InvalidArgumentException('Invalid api key - make sure api_key is defined in the config array');
        }

        if (!isset($config['api_secret']) || empty($config['api_secret'])) {
            throw new \InvalidArgumentException('Invalid api secret - make sure api_secret is defined in the config array');
        }

        if (!isset($config['callback_url']) || empty($config['callback_url'])) {
            throw new \InvalidArgumentException('Invalid callback url - make sure callback_url is defined in the config array');
        }

        if (!extension_loaded('curl')) {
            throw new \RuntimeException('PHP CURL extension does not seem to be loaded');
        }

        $this->config = $config;
    }

    /**
     * Get the login url, pass scope to request specific permissions.
     *
     * @param array       $scope - an array of requested permissions (can use scope constants defined in this class)
     * @param string|null $state - a unique identifier for this user, if none is passed, one is generated via uniqid
     */
    public function getLoginUrl(array $scope = [], ?string $state = null): string
    {
        $safeScope = '';
        if (!empty($scope)) {
            $safeScope = implode('%20', $scope);
        }

        if (empty($state)) {
            $state = uniqid('', true);
        }

        $this->setState($state);

        return self::OAUTH_BASE . "/authorization?response_type=code&client_id={$this->config['api_key']}&scope={$safeScope}&state={$state}&redirect_uri=" . urlencode($this->config['callback_url']);
    }

    /**
     * Exchange the authorization code for access token.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getAccessToken(?string $authorization_code = null): string|bool
    {
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        if (empty($authorization_code)) {
            throw new \InvalidArgumentException('Invalid authorization code. Pass in the "code" parameter from your callback url');
        }

        $params = [
            'grant_type'    => 'authorization_code',
            'code'          => $authorization_code,
            'client_id'     => $this->config['api_key'],
            'client_secret' => $this->config['api_secret'],
            'redirect_uri'  => $this->config['callback_url']
        ];

        /** Temp bug fix as per https://developer.linkedin.com/comment/28938#comment-28938 **/
        $tmp_params = http_build_query($params);

        $data = $this->_makeRequest(self::OAUTH_BASE . '/accessToken?' . $tmp_params, [], self::HTTP_METHOD_POST, ['x-li-format: json', 'Content-length: 0']);
        if (isset($data['error'], $data['error_description']) && !empty($data['error']) && !empty($data['error_description'])) {
            throw new \RuntimeException('Access Token Request Error: ' . $data['error'] . ' -- ' . $data['error_description']);
        }

        $this->accessToken = $data['access_token'];
        $this->accessTokenExpires = $data['expires_in'];

        return $this->accessToken;
    }

    /**
     * This timestamp is "expires in". In other words, the token will expire in now() + expires_in.
     */
    public function getAccessTokenExpiration(): ?string
    {
        return $this->accessTokenExpires;
    }

    /**
     * Set the access token manually.
     *
     * @throws \InvalidArgumentException
     */
    public function setAccessToken(string $token): self
    {
        $token = trim($token);
        if (empty($token)) {
            throw new \InvalidArgumentException('Invalid access token');
        }

        $this->accessToken = $token;

        return $this;
    }

    /**
     * Set the state manually. State is a unique identifier for the user.
     *
     * @throws \InvalidArgumentException
     */
    public function setState(string $state): self
    {
        $state = trim($state);
        if (empty($state)) {
            throw new \InvalidArgumentException('Invalid state. State should be a unique identifier for this user');
        }

        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * GET an authenticated API endpoind w/ payload.
     */
    public function get(string $endpoint, array $payload = [], array $headers = [], array $curlOptions = []): array
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_GET, $headers, $curlOptions);
    }

    /**
     * GET an authenticated API endpoind w/ payload.
     */
    public function getEncoded(string $endpoint, array $payload = [], array $headers = [], array $curlOptions = []): array
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_GET, $headers, $curlOptions, true);
    }

    /**
     * POST to an authenticated API endpoint w/ payload.
     */
    public function post(string $endpoint, array $payload = [], array $headers = [], array $curlOptions = []): array
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_POST, $headers, $curlOptions);
    }

    /**
     * PUT to an authenticated API endpoint w/ payload.
     */
    public function put(string $endpoint, array $payload = [], array $headers = [], array $curlOptions = []): array
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_PUT, $headers, $curlOptions);
    }

    public function fetch(string $endpoint, array $payload = [], string $method = 'GET', array $headers = [], array $curlOptions = [], bool $encodeQuery = false): array
    {
        $endpoint = self::API_BASE . '/' . trim($endpoint, '/\\') . '?oauth2_access_token=' . $this->getAccessToken();

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Restli-Protocol-Version: 2.0.0';

        return $this->_makeRequest($endpoint, $payload, $method, $headers, $curlOptions, $encodeQuery);
    }

    public function fetchOAuth(string $endpoint, array $payload = [], string $method = 'GET', array $headers = [], array $curlOptions = []): array
    {
        $endpoint = self::OAUTH_BASE . '/' . trim($endpoint, '/\\');

        return $this->_makeRequest($endpoint, $payload, $method, $headers, $curlOptions);
    }

    /**
     * Get debug info from the CURL request.
     */
    public function getDebugInfo(): ?array
    {
        return $this->debugInfo;
    }

    /**
     * Make a CURL request.
     *
     * @throws \JsonException
     */
    protected function _makeRequest(
        string $url,
        array $payload = [],
        string $method = 'GET',
        array $headers = [],
        array $curlOptions = [],
        bool $useEncodedQuery = false
    ): array {
        $ch = $this->_getCurlHandle();

        $options = [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_SSL_VERIFYPEER => false,
        ];

        if (!empty($payload)) {
            if (in_array($options[CURLOPT_CUSTOMREQUEST], [self::HTTP_METHOD_POST, self::HTTP_METHOD_PUT], true)) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = http_build_query($payload);

                $headers[] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);

                $options[CURLOPT_HTTPHEADER] = $headers;
            } else {
                $query = http_build_query($payload);
                $options[CURLOPT_URL] = sprintf('%s&%s', $options[CURLOPT_URL], ($useEncodedQuery ? urldecode($query) : $query));
            }
        }

        if (!empty($curlOptions)) {
            $options = array_merge($options, $curlOptions);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $this->debugInfo = curl_getinfo($ch);

        if ($response === false) {
            throw new \RuntimeException('Request Error: ' . curl_error($ch));
        }

        $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (isset($response['status']) && is_numeric($response['status']) && ($response['status'] < 200 || $response['status'] > 300)) {
            throw new \RuntimeException(json_encode($response, JSON_THROW_ON_ERROR));
        }

        return $response;
    }

    protected function _getCurlHandle(): \CurlHandle
    {
        if (!$this->curlHandle) {
            $this->curlHandle = curl_init();
        }

        return $this->curlHandle;
    }

    public function __destruct()
    {
        if ($this->curlHandle) {
            curl_close($this->curlHandle);
        }
    }
}

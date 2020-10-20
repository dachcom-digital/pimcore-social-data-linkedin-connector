<?php

namespace SocialData\Connector\LinkedIn\Client;

/**
 * Adapted from https://github.com/ashwinks/PHP-LinkedIn-SDK
 *
 * @package SocialData\Connector\LinkedIn\Client
 */
class LinkedInSDK
{
    const API_BASE = 'https://api.linkedin.com/v2';
    const OAUTH_BASE = 'https://www.linkedin.com/oauth/v2';

    const R_AD_CAMPAIGNS = 'r_ad_campaigns';                        // View advertising campaigns you manage
    const R_ADS = 'r_ads';                                          // Retrieve your advertising accounts
    const R_ADS_LEADGEN_AUTOMATION = 'r_ads_leadgen_automation';    // Access your Lead Gen Forms and retrieve leads
    const R_ADS_REPORTING = 'r_ads_reporting';                      // Retrieve reporting for your advertising accounts
    const R_EMAILADDRESS = 'r_emailaddress';                        // Use the primary email address associated with your LinkedIn account
    const R_LITEPROFILE = 'r_liteprofile';                          // Use your name, headline, and photo
    const R_MEMBER_SOCIAL = 'r_member_social';                      // Retrieve your posts, comments, likes, and other engagement data
    const R_ORGANIZATION_SOCIAL = 'r_organization_social';          // Retrieve your organizations' posts, including any comments, likes and other engagement data
    const RW_AD_CAMPAIGNS = 'rw_ad_campaigns';                      // Manage your advertising campaigns
    const RW_ADS = 'rw_ads';                                        // Manage your advertising accounts
    const RW_DMP_SEGMENTS = 'rw_dmp_segments';                      // Create and manage your matched audiences
    const RW_ORGANIZATION_ADMIN = 'rw_organization_admin';          // Manage your organizations' pages and retrieve reporting data
    const RW_ORGANIZATION = 'rw_organization';                      // Manage your organization's page and post updates
    const W_MEMBER_SOCIAL = 'w_member_social';                      // Post, comment and like posts on your behalf
    const W_ORGANIZATION_SOCIAL = 'w_organization_social';          // Post, comment and like posts on your organization's behalf

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $state = null;

    /**
     * @var string
     */
    protected $accessToken = null;

    /**
     * @var string
     */
    protected $accessTokenExpires = null;

    /**
     * @var array
     */
    protected $debugInfo = null;

    /**
     * @var null
     */
    protected $curlHandle = null;

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
     * Get the login url, pass scope to request specific permissions
     *
     * @param array  $scope - an array of requested permissions (can use scope constants defined in this class)
     * @param string $state - a unique identifier for this user, if none is passed, one is generated via uniqid
     *
     * @return string $url
     */
    public function getLoginUrl(array $scope = [], $state = null)
    {
        if (!empty($scope)) {
            $scope = implode('%20', $scope);
        }

        if (empty($state)) {
            $state = uniqid('', true);
        }

        $this->setState($state);

        return self::OAUTH_BASE . "/authorization?response_type=code&client_id={$this->config['api_key']}&scope={$scope}&state={$state}&redirect_uri=" . urlencode($this->config['callback_url']);
    }

    /**
     * Exchange the authorization code for an access token
     *
     * @param string $authorization_code
     *
     * @return string|boolean $access_token
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getAccessToken($authorization_code = null)
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
        if (isset($data->error) && !empty($data->error) && !empty($data->error_description)) {
            throw new \RuntimeException('Access Token Request Error: ' . $data->error . ' -- ' . $data->error_description);
        }

        $this->accessToken = $data->access_token;
        $this->accessTokenExpires = $data->expires_in;

        return $this->accessToken;
    }

    /**
     * This timestamp is "expires in". In other words, the token will expire in now() + expires_in
     *
     * @return int access token expiration time -
     */
    public function getAccessTokenExpiration()
    {
        return $this->accessTokenExpires;
    }

    /**
     * Set the access token manually
     *
     * @param string $token
     *
     * @return LinkedInSDK
     * @throws \InvalidArgumentException
     */
    public function setAccessToken($token)
    {
        $token = trim($token);
        if (empty($token)) {
            throw new \InvalidArgumentException('Invalid access token');
        }

        $this->accessToken = $token;

        return $this;
    }

    /**
     * Set the state manually. State is a unique identifier for the user
     *
     * @param string $state
     *
     * @return LinkedInSDK
     * @throws \InvalidArgumentException
     */
    public function setState($state)
    {
        $state = trim($state);
        if (empty($state)) {
            throw new \InvalidArgumentException('Invalid state. State should be a unique identifier for this user');
        }

        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * POST to an authenciated API endpoint w/ payload
     *
     * @param string $endpoint
     * @param array  $payload
     * @param array  $headers
     * @param array  $curlOptions
     *
     * @return array
     */
    public function post($endpoint, $payload = [], $headers = [], $curlOptions = [])
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_POST, $headers, $curlOptions);
    }

    /**
     * GET an authenticated API endpoind w/ payload
     *
     * @param string $endpoint
     * @param array  $payload
     * @param array  $headers
     * @param array  $curlOptions
     *
     * @return array
     */
    public function get($endpoint, $payload = [], $headers = [], $curlOptions = [])
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_GET, $headers, $curlOptions);
    }

    /**
     * PUT to an authenciated API endpoint w/ payload
     *
     * @param string $endpoint
     * @param array  $payload
     * @param array  $headers
     * @param array  $curlOptions
     *
     * @return array
     */
    public function put($endpoint, $payload = [], $headers = [], $curlOptions = [])
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_PUT, $headers, $curlOptions);
    }

    /**
     * @param string $endpoint
     * @param array  $payload
     * @param string $method
     * @param array  $headers
     * @param array  $curlOptions
     *
     * @return array
     */
    public function fetch($endpoint, $payload = [], $method = 'GET', array $headers = [], array $curlOptions = [])
    {
        $endpoint = self::API_BASE . '/' . trim($endpoint, '/\\') . '?oauth2_access_token=' . $this->getAccessToken();
        $headers[] = 'x-li-format: json';

        return $this->_makeRequest($endpoint, $payload, $method, $headers, $curlOptions);
    }

    /**
     * @param string $endpoint
     * @param array  $payload
     * @param string $method
     * @param array  $headers
     * @param array  $curlOptions
     *
     * @return array
     */
    public function fetchOAuth($endpoint, $payload = [], $method = 'GET', array $headers = [], array $curlOptions = [])
    {
        $endpoint = self::OAUTH_BASE . '/' . trim($endpoint, '/\\');

        return $this->_makeRequest($endpoint, $payload, $method, $headers, $curlOptions);
    }

    /**
     * Get debug info from the CURL request
     *
     * @return array
     */
    public function getDebugInfo()
    {
        return $this->debugInfo;
    }

    /**
     * Make a CURL request
     *
     * @param string $url
     * @param array  $payload
     * @param string $method
     * @param array  $headers
     * @param array  $curlOptions
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function _makeRequest($url, $payload = [], $method = 'GET', array $headers = [], array $curlOptions = [])
    {
        $ch = $this->_getCurlHandle();

        $options = [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ];

        if (!empty($payload)) {
            if ($options[CURLOPT_CUSTOMREQUEST] == self::HTTP_METHOD_POST || $options[CURLOPT_CUSTOMREQUEST] == self::HTTP_METHOD_PUT) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = is_array($payload) ? http_build_query($payload) : $payload;
                $headers[] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $options[CURLOPT_HTTPHEADER] = $headers;
            } else {
                $options[CURLOPT_URL] .= '&' . http_build_query($payload, '&');
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

        $response = json_decode($response); //CHANGED to object

        if (isset($response->status) && is_numeric($response->status) && ($response->status < 200 || $response->status > 300)) {
            throw new \RuntimeException(json_encode($response));
        }

        return $response;
    }

    /**
     * @return false|resource
     */
    protected function _getCurlHandle()
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
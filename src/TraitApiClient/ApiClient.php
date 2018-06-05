<?php

namespace BuronGtz\TraitApiClient;

use Illuminate\Contracts\Session\Session;
use \GuzzleHttp\Client;

trait ApiClient
{
    private $client = null;

    /**
     * Undocumented function
     *
     * @return void
     */
    private function apiCreateClient()
    {
        if (!isset($this->client)) {
            $this->client = new Client([
                'base_uri' => env('API_URL'),
                'http_errors' => false,
                'timeout' => 0 // TODO: Fix this timeout to 2.0 or same
            ]);
        }
    }

    /**
     * API request
     *
     * @param array $options
     * @return void
     */
    private function apiRequest($options = [])
    {
        $this->apiCreateClient();

        $options['method'] = isset($options['method']) ? $options['method'] : 'GET';
        $options['url'] = isset($options['url']) ? $options['url'] : '';
        $options['data'] = isset($options['data']) ? $options['data'] : [];

        return $this->client->request($options['method'], $options['url'], $options['data']);
    }

    /**
     * Request access token
     *
     * @return void
     */
    protected function apiRequestAccessToken($options = null)
    {
        session()->put('oauth_token', null);
        $formParams = [
            'grant_type' => isset($options['data']['form_params']['grant_type']) ? $options['data']['form_params']['grant_type'] : 'client_credentials',
            'client_id' => env('API_CLIENT_ID'),
            'client_secret' => env('API_CLIENT_SECRET'),
        ];
        if (isset($options['data']['form_params']['username'])) {
            $formParams['username'] = $options['data']['form_params']['username'];
        }
        if (isset($options['data']['form_params']['password'])) {
            $formParams['password'] = $options['data']['form_params']['password'];
        }
        $options = [
            'url' => isset($options['url']) ? $options['url'] :  env('API_URL_TOKEN'),
            'method' => 'POST',
            'data' => [
                'form_params' => $formParams
            ],
        ];

        try {
            $response = $this->apiRequest($options);
            if ($response->getStatusCode() == 200) {
                session()->put('oauth_token', (array) json_decode($response->getBody()->getContents()));
            }
        } catch (ClientException $e) {}
    }

    /**
     * Request access token with user and pass
     *
     * @param String $user
     * @param String $pass
     * @return void
     */
    protected function apiRequestAccessTokenAsUser(String $user = '', String $pass)
    {
        $this->apiRequestAccessToken([
            'data' => [
                'form_params' => [
                    'grant_type' => 'password',
                    'username' => $user,
                    'password' => $pass
                ]
            ]
        ]);
    }

    protected function apiRefreshAccessToken(String $refreshToken= '')
    {
        $this->apiRequestAccessToken([
            'data' => [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    "refresh_token" => $refreshToken
                ]
            ]
        ]);
    }

    /**
     * Undocumented function
     *
     * @param String $url
     * @param String $method
     * @param [type] $data
     * @return void
     */
    protected function apiRequestWithMethod(String $url = '', String $method =  'GET', $data = null)
    {
        $accessToken =  isset(session()->get('oauth_token')['access_token'])
            ? session()->get('oauth_token')['access_token'] 
            : '';
        $options = [
            'method'  =>$method,
            'url' => $url,
            'data' => [
                'headers' => [
                    'authorization' => 'Bearer ' . $accessToken,
                    'accept' => 'application/vnd.sacbe.v1+json',
                    'content-type' => 'application/json'
                ]
            ]
        ];
        if (isset($data)) {
            $options['data']['json'] = $data;
        }
        
        return $this->apiRequest($options);
    }

    private function getResponseAsJson($response = null)
    {
        $statusCode = $response->getStatusCode();
        $response = json_decode($response->getBody());
        $response->status_code = $statusCode;
        return $response;
    }

    /**
     * Undocumented function
     *
     * @param String $url
     * @return void
     */
    protected function apiGet(String $url = '')
    {
        return $this->getResponseAsJson(
            $this->apiRequestWithMethod($url)
        );
    }

    /**
     * Undocumented function
     *
     * @param String $url
     * @param array $data
     * @return void
     */
    protected function apiPut(String $url = '', array $data = [])
    {
        return $this->getResponseAsJson(
            $this->apiRequestWithMethod($url, 'PUT', $data)
        );
    }
}
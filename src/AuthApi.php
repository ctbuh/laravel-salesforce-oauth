<?php

namespace ctbuh\Salesforce\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

final class AuthApi
{
    protected $client_id;
    protected $client_secret;

    /** @var Client */
    protected $client;

    /** @var array */
    protected $config;

    public function __construct($config)
    {
        // TODO: merge defaults?
        $this->config = $config;

        $this->client_id = $config['consumer_key'];
        $this->client_secret = $config['consumer_secret'];

        /*
         * Instead of sending client credentials as parameters in the body of the refresh token POST request, you can use the HTTP Basic authentication scheme.
         * This scheme’s format requires the client_id and client_secret in the authorization header of the post as follows:
         * Authorization: Basic64Encode(client_id:secret)
         */
        $this->client = new Client([
            'base_uri' => sprintf('https://%s/', $config['oauth_domain']),
            'headers' => [
                'Authorization' => base64_encode($this->client_id . ':' . $this->client_secret)
            ]
        ]);
    }

    protected function json(ResponseInterface $response)
    {
        $body = (string)$response->getBody();
        return json_decode($body);
    }

    protected function formParams(array $params)
    {
        $params['client_id'] = $this->client_id;
        $params['client_secret'] = $this->client_secret;
        return $params;
    }

    public function introspect($token)
    {
        $response = $this->client->post("/services/oauth2/introspect", [
            'form_params' => $this->formParams([
                'token' => $token
            ])
        ]);

        return $this->json($response);
    }

    // about 300 ms
    public function userInfo(AccessToken $accessToken)
    {
        $response = $this->client->post("/services/oauth2/userinfo", [
            'form_params' => [
                'access_token' => $accessToken->access_token
            ]
        ]);

        return $this->json($response);
    }

    public function getAuthLoginUrl()
    {
        $domain = rtrim($this->config['oauth_domain'], '/');

        return 'https://' . $domain . '/services/oauth2/authorize?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $this->client_id,
                'redirect_uri' => $this->config['oauth_redirect_uri'],
                'scope' => 'openid api refresh_token'
            ]);
    }

    /**
     * @param $auth_code
     * @throws RequestException
     * @return AccessToken
     */
    public function exchangeAuthCodeForToken($auth_code)
    {
        $response = $this->client->post('/services/oauth2/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'code' => $auth_code,
                'redirect_uri' => $this->config['oauth_redirect_uri']
            ]
        ]);

        $array = json_decode((string)$response->getBody(), true);
        return new AccessToken($array);
    }

    public function getNewAccessToken($refresh_token)
    {
        $response = $this->client->post('/services/oauth2/token', [
            'form_params' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
            ]
        ]);

        $array = json_decode((string)$response->getBody(), true);
        return new AccessToken($array);
    }

    /**
     * @param AccessToken $token
     * @return ResponseInterface
     */
    public function revoke(AccessToken $token)
    {
        $response = $this->client->post("/services/oauth2/revoke", [
            'form_params' => [
                'token' => $token->access_token
            ]
        ]);

        return $response;
    }

    public function revokeQuietly(AccessToken $token)
    {
        try {
            $response = $this->revoke($token);
            return $response->getStatusCode() == 200;
        } catch (\Exception $exception) {
            // fail silently
            return false;
        }
    }
}
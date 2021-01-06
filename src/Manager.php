<?php

namespace ctbuh\Salesforce\OAuth;

use ctbuh\Salesforce\OAuth\Exception\BadTokenException;
use GuzzleHttp\Exception\RequestException;

class Manager
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;
    /**
     * @var AuthApi
     */
    private $api;

    public function __construct(TokenStorage $tokenStorage, AuthApi $api)
    {
        $this->tokenStorage = $tokenStorage;
        $this->api = $api;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->tokenStorage->getAccessToken();
    }

    public function getRefreshToken()
    {
        return $this->tokenStorage->getRefreshToken();
    }

    // A connected app can query the UserInfo endpoint for information about the user associated with the connected app’s access token.
    public function getUserInfo()
    {
        $token = $this->tokenStorage->getAccessToken();

        if ($token) {

            try {
                return $this->api->userInfo($token);
            } catch (RequestException $exception) {
                $message = $exception->getMessage();

                if (strpos($message, 'Missing_OAuth_Token') !== false) {
                    // throw new MissingTokenException();
                } else if (strpos($message, 'Bad_OAuth_Token') !== false) {
                    throw new BadTokenException();
                }
            }
        }

        return null;
    }

    public function refreshQuietly()
    {
        $refresh_token = $this->tokenStorage->getRefreshToken();

        if (!$refresh_token) {
            return false;
        }

        try {
            $token = $this->api->getNewAccessToken($refresh_token);

            // must be VALID if here
            $this->tokenStorage->save($token);
            return true;

        } catch (RequestException $exception) {

        }

        return false;
    }

    public function revokeQuietly()
    {
        $token = $this->tokenStorage->getAccessToken();

        try {

            if ($token) {
                $this->api->revoke($token);
            }

            $this->tokenStorage->forget();
            return true;
        } catch (RequestException $exception) {
            // do nothing
        } finally {
            $this->tokenStorage->forget();
        }

        return false;
    }
}
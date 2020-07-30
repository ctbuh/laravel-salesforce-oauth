<?php

namespace ctbuh\Salesforce\OAuth;

use Illuminate\Session\Store;

class TokenSessionStorage
{
    /** @var Store */
    protected $storage;

    // ACCESS_TOKEN_SESSION_KEY
    const ACCESS_TOKEN_KEY = 'sf_oauth_token';
    const REFRESH_TOKEN_KEY = 'sf_oauth_refresh_token';

    public function __construct(Store $storage)
    {
        $this->storage = $storage;
    }

    public function getId()
    {
        return $this->storage->getId();
    }

    public function save(AccessToken $token)
    {
        $this->storage->put(self::ACCESS_TOKEN_KEY, $token);

        // refresh_token is not present for new tokens generated via refresh
        if ($token->refresh_token) {
            $this->storage->put(self::REFRESH_TOKEN_KEY, $token->refresh_token);
        }
    }

    public function has()
    {
        return !empty($this->getAccessToken());
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->storage->get(self::ACCESS_TOKEN_KEY);
    }

    public function getRefreshToken()
    {
        return $this->storage->get(self::REFRESH_TOKEN_KEY);
    }

    public function forget()
    {
        $this->storage->forget(self::ACCESS_TOKEN_KEY);
        $this->storage->forget(self::REFRESH_TOKEN_KEY);
    }
}
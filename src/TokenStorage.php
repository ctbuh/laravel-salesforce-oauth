<?php

namespace ctbuh\Salesforce\OAuth;

use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;

class TokenStorage
{
    // ACCESS_TOKEN_SESSION_KEY
    const ACCESS_TOKEN_KEY = 'sf_at';
    const REFRESH_TOKEN_KEY = 'sf_rt';

    /** @var Request */
    private $request;

    /** @var CookieJar */
    private $cookieJar;

    // because cookies stored will only become available on next request
    private $access_token;
    private $refresh_token;

    public function __construct(Request $request, CookieJar $cookieJar)
    {
        $this->request = $request;
        $this->cookieJar = $cookieJar;
    }

    public function save(AccessToken $token)
    {
        $this->access_token = $token;

        $cookie = $this->cookieJar->make(self::ACCESS_TOKEN_KEY, $token);
        $this->cookieJar->queue($cookie);

        // refresh_token is not present for new tokens generated through refresh token
        if ($token->refresh_token) {
            $this->refresh_token = $token->refresh_token;

            $cookie = $this->cookieJar->forever(self::REFRESH_TOKEN_KEY, $token->refresh_token);
            $this->cookieJar->queue($cookie);
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
        $token = unserialize($this->request->cookie(self::ACCESS_TOKEN_KEY));

        if ($token instanceof AccessToken) {
            return $token;
        }

        return $this->access_token;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        $token = unserialize($this->request->cookie(self::REFRESH_TOKEN_KEY));
        return $token ? $token : $this->refresh_token;
    }

    public function forget()
    {
        $this->access_token = null;
        $this->refresh_token = null;

        $this->cookieJar->queue($this->cookieJar->forget(self::ACCESS_TOKEN_KEY));
        $this->cookieJar->queue($this->cookieJar->forget(self::REFRESH_TOKEN_KEY));
    }
}
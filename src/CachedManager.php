<?php

namespace ctbuh\Salesforce\OAuth;

use ctbuh\Salesforce\OAuth\Exception\BadTokenException;
use Illuminate\Cache\Repository as Cache;

class CachedManager
{
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var Manager
     */
    private $manager;

    // 6 hours
    const CACHE_TIMEOUT = 60 * 60 * 6;

    public function __construct(Manager $manager, Cache $cache)
    {
        $this->manager = $manager;
        $this->cache = $cache;
    }

    private function getCacheKey()
    {
        $token = $this->manager->getAccessToken();

        if ($token) {
            return sprintf('CachedManager:%s', md5($token->access_token));
        }

        return null;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->manager->getAccessToken();
    }

    public function flush()
    {
        $cache_key = $this->getCacheKey();

        if ($cache_key) {
            $this->cache->forget($cache_key);
        }
    }

    // The goal is to make sure that this only gets called once per accessToken
    public function getUserInfo()
    {
        $manager = $this->manager;

        // assume stored token is valid
        if ($manager->getAccessToken()) {

            return $this->cache->remember($this->getCacheKey(), self::CACHE_TIMEOUT, function () use ($manager) {

                $info = null;

                try {
                    $info = $manager->getUserInfo();
                } catch (BadTokenException $exception) {
                    $manager->refreshQuietly();
                    $info = $manager->getUserInfo();
                }

                return $info;
            });
        }

        return null;
    }
}
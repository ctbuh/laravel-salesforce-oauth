<?php

namespace ctbuh\Salesforce\OAuth\Http;

use ctbuh\Salesforce\OAuth\AccessToken;
use ctbuh\Salesforce\OAuth\AuthApi;
use ctbuh\Salesforce\OAuth\Exception\BadTokenException;
use ctbuh\Salesforce\OAuth\Manager;
use ctbuh\Salesforce\OAuth\TokenStorage;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

class OAuthController
{
    const RETURN_TO_SESSION_KEY = 'sf_oauth_return_to';

    public function status(Manager $manager, Repository $config)
    {
        try {
            $info = (array)$manager->getUserInfo();

            if (empty($info)) {
                return response()->json([
                    'error' => 'Not Logged In'
                ]);
            }

            // include token info too:
            if ($config->get('oauth_status_show_tokens')) {
                $info['at'] = $manager->getAccessToken();
                $info['rt'] = $manager->getRefreshToken();
            }

            return response()->header('Access-Control-Allow-Origin', '*')->json($info);
        } catch (BadTokenException $exception) {

            return response()->header('Access-Control-Allow-Origin', '*')->json([
                'error' => 'Token Expired'
            ]);
        }
    }

    public function loginUsingToken(Request $request, TokenStorage $tokenStorage)
    {
        $token = new AccessToken($request->all());
        $tokenStorage->save($token);

        // does not necessarily mean that these tokens will work for login
        return response()->json(['status' => 'success']);
    }

    public function login(Request $request, AuthApi $api, Store $sessionStore)
    {
        $return_to = $request->get('return_to');

        if (empty($return_to)) {
            $return_to = $request->headers->get('referer');
        }

        $sessionStore->put(self::RETURN_TO_SESSION_KEY, $return_to);

        return redirect($api->getAuthLoginUrl());
    }

    public function callback(Request $request, AuthApi $api, TokenStorage $storage, Store $sessionStore)
    {
        $code = $request->get('code');

        try {

            $token = $api->exchangeAuthCodeForToken($code);

            if ($token) {
                $storage->save($token);

                $return_to = $sessionStore->get(self::RETURN_TO_SESSION_KEY);
                return redirect()->to($return_to ?? '/');
            }

        } catch (RequestException $requestException) {
            return $requestException->getMessage();
        }

        return 'Something went wrong...';
    }

    public function logout(Manager $manager)
    {
        $manager->revokeQuietly();
        return redirect()->back();
    }
}

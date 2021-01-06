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

    public function status(Request $request, Manager $manager, Repository $config)
    {
        $callback = $request->get('callback');

        try {
            $info = (array)$manager->getUserInfo();

            if (empty($info)) {

                return response()->jsonp($callback, [
                    'error' => 'Not Logged In'
                ])->header('Access-Control-Allow-Origin', '*');
            }
            
            // include token info too:
            if ($config->get('salesforce.oauth_status_show_tokens')) {
                $token = $manager->getAccessToken();

                $info['tokens'] = [
                    'access_token' => $token ? $token->access_token : null,
                    'refresh_token' => $manager->getRefreshToken()
                ];
            }

            return response()->jsonp($callback, $info)->header('Access-Control-Allow-Origin', '*');
        } catch (BadTokenException $exception) {

            return response()->jsonp($callback, [
                'error' => 'Token Expired'
            ])->header('Access-Control-Allow-Origin', '*');
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

    public function logout(Manager $manager, TokenStorage $tokenStorage)
    {
        $manager->revokeQuietly();

        // just in case the quietly portion fails inside
        $tokenStorage->forget();

        return redirect()->back();
    }
}

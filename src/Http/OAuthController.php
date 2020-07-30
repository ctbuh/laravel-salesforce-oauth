<?php

namespace ctbuh\Salesforce\OAuth\Http;

use ctbuh\Salesforce\Exception\BadTokenException;
use ctbuh\Salesforce\OAuth\AuthApi;
use ctbuh\Salesforce\OAuth\Manager;
use ctbuh\Salesforce\OAuth\TokenSessionStorage;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class OAuthController
{
    public function status(Manager $manager)
    {
        try {
            $info = (array)$manager->getUserInfo();

            if (empty($info)) {
                return response()->json([
                    'error' => 'Not Logged In'
                ]);
            }

            return response()->json($info);
        } catch (BadTokenException $exception) {

            return response()->json([
                'error' => 'Token Expired'
            ]);
        }
    }

    public function login(Request $request, AuthApi $api)
    {
        $return_to = $request->get('return_to');

        if (empty($return_to)) {
            $return_to = $request->headers->get('referer');
        }

        // TODO: session.return_to = absolute url
        return redirect($api->getAuthLoginUrl());
    }

    public function callback(Request $request, AuthApi $api, TokenSessionStorage $storage)
    {
        $code = $request->get('code');

        try {

            $token = $api->exchangeAuthCodeForToken($code);

            if ($token) {
                $storage->save($token);
                return redirect()->to('/');
            }

        } catch (RequestException $requestException) {
            // do nothing
        }

        return 'Something went wrong...';
    }
}

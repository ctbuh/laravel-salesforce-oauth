<?php

return array(
    'consumer_key' => env('SALESFORCE_OAUTH_CONSUMER_KEY', ''),
    'consumer_secret' => env('SALESFORCE_OAUTH_CONSUMER_SECRET', ''),
    'oauth_domain' => env('SALESFORCE_OAUTH_DOMAIN', 'login.salesforce.com'),
    'oauth_redirect_uri' => 'https://httpbin.org/get'
);
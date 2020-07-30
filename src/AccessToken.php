<?php

namespace ctbuh\Salesforce\OAuth;

class AccessToken
{
    public $access_token;
    public $refresh_token;
    public $sfdc_community_url;
    public $sfdc_community_id;
    public $state;
    public $signature;
    public $scope;
    public $instance_url;
    public $id;
    public $id_token;
    public $token_type;
    public $issued_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }
}
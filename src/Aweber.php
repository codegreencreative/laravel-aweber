<?php

namespace CodeGreenCreative\Aweber;

use CodeGreenCreative\Aweber\Api\Lists;
use CodeGreenCreative\Aweber\Api\Subscribers;
use League\Oauth2\Client\Provider\GenericProvider;

class Aweber
{
    /**
     * [methodName description]
     * @return [type] [description]
     */
    public function __construct()
    {
    }

    public function lists($list_id = null)
    {
        try {
            return new Lists($list_id);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e->getMessage());
        }
    }

    public function subscribers($list_id = null, $subscriber_id = null)
    {
        try {
            return new Subscribers($list_id, $subscriber_id);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e->getMessage());
        }
    }
}

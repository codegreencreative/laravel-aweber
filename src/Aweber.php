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
        return new Lists($list_id);
    }

    public function subscribers($list_id = null, $subscriber_id = null)
    {
        return new Subscribers($list_id, $subscriber_id);
    }
}

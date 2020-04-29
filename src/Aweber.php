<?php

namespace CodeGreenCreative\Aweber;

use CodeGreenCreative\Aweber\Lists;
use CodeGreenCreative\Aweber\Subscribers;
use League\Oauth2\Client\Provider\GenericProvider;

class Aweber
{
    /**
     * [methodName description]
     * @return [type] [description]
     */
    public function __construct()
    {
        $store = config('aweber.cache');
        if (cache()->store($store)->has('aweber.token')) {
            # code...
        }
    }

    public function lists()
    {
        return new Lists;
    }

    public function subscribers()
    {
        return new Subscribers;
    }
}

<?php

namespace CodeGreenCreative\Aweber;

class Aweber
{
    public $classes = [
        'accounts' => '\CodeGreenCreative\Aweber\Api\Accounts',
        'broadcasts' => '\CodeGreenCreative\Aweber\Api\Broadcasts',
        'campaigns' => '\CodeGreenCreative\Aweber\Api\Campaigns',
        'customFields' => '\CodeGreenCreative\Aweber\Api\CustomFields',
        'lists' => '\CodeGreenCreative\Aweber\Api\Lists',
        'subscribers' => '\CodeGreenCreative\Aweber\Api\Subscribers',
    ];

    /**
     * [__call description]
     *
     * @param  [type] $name      [description]
     * @param  [type] $arguments [description]
     * @return [type]            [description]
     */
    public function __call($name, $arguments)
    {
        if (array_key_exists($name, $this->classes)) {
            return new $this->classes[$name]();
        }
    }
}

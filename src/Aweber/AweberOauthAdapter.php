<?php

namespace CodeGreenCreative\Aweber\Aweber;

interface AweberOauthAdapter
{
    public function request($method, $uri, $data = array());
    public function getRequestToken($callbackUrl = false);
}

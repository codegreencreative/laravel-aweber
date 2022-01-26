<?php

namespace CodeGreenCreative\Aweber\Api;

use Illuminate\Support\Str;
use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberException;

class Accounts extends AweberClient
{
    private $account;

    /**
     * Paginate through all accounts
     *
     * @param  integer $start
     * @param  integer $limit
     * @return array
     */
    public function paginate($start = 0, $limit = 100)
    {
        if ($limit > 100) {
            throw new AweberException('Limit on record sets is 100.');
        }

        return $this->request('GET', '/1.0/accounts', ['ws.start' => $start, 'ws.size' => $limit]);
    }

    /**
     * Find am account by ID
     *
     * @param  integer $account_id [description]
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($account_id)
    {
        $this->account = $this->request('GET', '/1.0/accounts/' . $account_id);
        return $this;
    }

    /**
     * [getListAttribute description]
     * @return [type] [description]
     */
    public function getAccountAttribute()
    {
        return $this->account;
    }

    /**
     * Get magic method formula
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name = sprintf('get%sAttribute', Str::studly($name));
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
    }
}

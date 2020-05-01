<?php

namespace CodeGreenCreative\Aweber\Api;

use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Contracts\AweberApiContract;
use CodeGreenCreative\Aweber\Exceptions\AweberException;

class Broadcasts extends AweberClient implements AweberApiContract
{
    private $list_id;
    private $broadcast;

    /**
     * Paginate through all broadcasts
     *
     * @param  integer $list_id
     * @param  string $status
     * @param  integer $start
     * @param  integer $limit
     * @return array
     */
    public function paginate($start = 0, $limit = 100)
    {
        if ($limit > 100) {
            throw new AweberException('Limit on record sets is 100.');
        }

        return $this->request('GET', 'lists/' . $this->list_id . '/broadcasts', array(
            'status' => $this->status,
            'ws.start' => $start,
            'ws.size' => $limit
        ));
    }

    /**
     * Find a broadcast by ID
     *
     * @param  integer $broadcast_id
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($broadcast_id)
    {
        $this->broadcast = $this->request('GET', 'lists/' . $this->list_id . '/broadcasts/' . $broadcast_id);
        return $this;
    }

    /**
     * [getListAttribute description]
     * @return [type] [description]
     */
    public function getBroadcastAttribute()
    {
        return $this->broadcast;
    }

    /**
     * Set the list to be used
     *
     * @param  integer $list_id
     * @return self
     */
    public function list($list_id)
    {
        $this->list_id = $list_id;
        return $this;
    }

    /**
     * Set the status of the broadcast
     *
     * @param  integer $status
     * @return self
     */
    public function status($status)
    {
        $this->status = $status;
        return $this;
    }
}

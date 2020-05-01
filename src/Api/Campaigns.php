<?php

namespace CodeGreenCreative\Aweber\Api;

use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Contracts\AweberApiContract;
use CodeGreenCreative\Aweber\Exceptions\AweberException;

class Campaigns extends AweberClient implements AweberApiContract
{
    private $type;
    private $list_id;

    /**
     * Paginate through all lists
     *
     * @param  integer $start [description]
     * @param  integer $limit [description]
     * @return array
     */
    public function paginate($start = 0, $limit = 100)
    {
        if ($limit > 100) {
            throw new AweberException('Limit on record sets is 100.');
        }

        return $this->request('GET', 'lists/' . $this->list_id . '/campaigns', array(
            'ws.start' => $start,
            'ws.size' => $limit
        ));
    }

    /**
     * Find a campaign by ID
     *
     * @param  integer $list_id
     * @param  integer $campaign_id
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($campaign_id)
    {
        $this->list = $this->request('GET', 'lists/' . $this->list_id . '/campaigns/' . $this->type . $campaign_id);
        return $this;
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
     * Set the type of campaign to be used
     * Types are "b" and "f"
     *
     * @param  integer $type
     * @return self
     */
    public function type($type)
    {
        $this->type = $type;
        return $this;
    }
}

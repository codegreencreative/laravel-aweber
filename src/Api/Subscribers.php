<?php

namespace CodeGreenCreative\Aweber\Api;

use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Contracts\AweberApiContract;
use CodeGreenCreative\Aweber\Exceptions\AweberException;

class Subscribers extends AweberClient implements AweberApiContract
{
    private $list_id;
    private $subscriber;

    /**
     * Paginate through subscribers on a list
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

        return $this->request('GET', 'lists/' . $this->list_id . '/subscribers', array(
            'ws.start' => $start,
            'ws.size' => $limit
        ));
    }

    /**
     * Find a subscriber by ID
     *
     * @param  integer $subscriber_id
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($subscriber_id)
    {
        $this->subscriber = $this->request('GET', 'lists/' . $this->list_id . '/subscribers/' . $subscriber_id);
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
     * Get subscriber
     *
     * @return array
     */
    public function getSubscriberAttribute()
    {
        return $this->subscriber;
    }

    /**
     * Add subscriber to a list
     *
     * Aweber API Reference
     * https://api.aweber.com/#tag/Subscribers/paths/~1accounts~1{accountId}~1lists~1{listId}~1subscribers/post
     *
     * @param integer $list_id
     * @param array $options
     */
    public function add($options)
    {
        $subscriber_id = $this->request('POST', 'lists/' . $this->list_id . '/subscribers', $options);
        return $this->load($subscriber_id);
    }

    /**
     * Move a subscriber from one list to another
     *
     * @param  integer $destination_list_id
     * @return array
     */
    public function move($destination_list_id)
    {
        $this->request('POST', 'lists/' . $this->list_id . '/subscribers/' . $this->subscriber->id, array(
            'ws.op' => 'move',
            'list_link' => $this->api_url . 'accounts/' . $this->account_id . '/lists/' . $destination_list_id
        ));
    }

    /**
     * Get magic method formula
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name = function_exists('studly_case') ? studly_case($name) : \Illuminate\Support\Str::studly($name);
        $name = sprintf('get%sAttribute', $name);
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
    }
}

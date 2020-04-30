<?php

namespace CodeGreenCreative\Aweber\Api;

use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Exceptions\AweberException;

class Subscribers extends AweberClient
{
    private $list_id;
    private $subscriber;

    /**
     * [__construct description]
     *
     * @param integer $list_id
     */
    public function __construct($list_id = null, $subscriber_id = null)
    {
        parent::__construct();

        $this->list_id = $list_id;
        if (! is_null($subscriber_id)) {
            return $this->find($subscriber_id);
        }
    }

    /**
     * Get all subscribers on a list
     *
     * @return array
     */
    public function all()
    {
        $response = $this->client->request('GET', 'lists/' . $this->list_id . '/subscribers');
        return json_decode($response->getBody(), true);
    }


    /**
     * Find a subscriber on a list
     *
     * @param  integer $subscriber_id
     * @return CodeGreenCreative\Aweber\Api\Subscriber
     */
    public function find($subscriber_id)
    {
        $response = $this->client->request('GET', 'lists/' . $this->list_id . '/subscribers/' . $subscriber_id);
        if ($response->getStatusCode() == 200) {
            // return json_decode($response->getBody(), true);
            $this->subscriber = json_decode($response->getBody(), true);
            return $this;
        }
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
        $response = $this->client->request('POST', 'lists/' . $this->list_id . '/subscribers', array(
            'json' => $options
        ));
        return json_decode($response->getBody(), true);
    }

    public function update()
    {
    }

    /**
     * Move a subscriber from one list to another
     *
     * @param  integer $destination_list_id
     * @return array
     */
    public function move($destination_list_id)
    {
        try {
            $response = $this->client->request('POST', 'lists/' . $this->list_id . '/subscribers/' . $this->subscriber['id'], array(
                'json' => array(
                    'ws.op' => 'move',
                    'list_link' => $this->api_url . 'accounts/' . $this->account_id . '/lists/' . $destination_list_id
                ),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                )
            ));
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = json_decode($e->getResponse()->getBody(), true);
            throw new AweberException($response['error']['message'], true);
        }
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

<?php

namespace CodeGreenCreative\Aweber\Api;

use CodeGreenCreative\Aweber\AweberClient;

class Lists extends AweberClient
{
    private $list;

    /**
     * If a list ID is supplied, find the list
     *
     * @param CodeGreenCreative\Aweber\Api\List | void
     */
    public function __construct($list_id = null)
    {
        parent::__construct();

        if (! is_null($list_id)) {
            return $this->find($list_id);
        }
    }

    /**
     * Get all lists in the account
     *
     * @return array
     */
    public function all()
    {
        $response = $this->client->request('get', 'lists');
        return json_decode($response->getBody(), true);
    }

    /**
     * Find a list by ID
     *
     * @param  integer $list_id [description]
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function find($list_id)
    {
        $response = $this->client->request('get', 'lists/' . $list_id);
        if ($response->getStatusCode() == 200) {
            // return json_decode($response->getBody(), true);
            $this->list = json_decode($response->getBody(), true);
            return $this;
        }
    }

    /**
     * Get tags for the list
     *
     * @param  integer $list_id
     * @return array
     */
    public function tags(int $list_id)
    {
        $response = $this->client->request('get', 'lists/' . $list_id . '/tags');
        return json_decode($response->getBody(), true);
    }

    /**
     * [getListAttribute description]
     * @return [type] [description]
     */
    public function getListAttribute()
    {
        return $this->list;
    }

    /**
     * Get campaigns for the list
     *
     * @return array
     */
    public function getCampaignsAttribute()
    {
        $response = $this->client->request('get', $this->list['campaigns_collection_link']);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get custom fields for the list
     *
     * @return array
     */
    public function getCustomFieldsAttribute()
    {
        $response = $this->client->request('get', $this->list['custom_fields_collection_link']);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get Subscribers to the list
     *
     * @return array
     */
    public function getSubscribersAttribute()
    {
        $response = $this->client->request('get', $this->list['subscribers_collection_link']);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get total subscribed subscribers to the list
     *
     * @return integer
     */
    public function getTotalSubscribedSubscribersAttribute()
    {
        return (int) $this->list['total_subscribed_subscribers'];
    }

    /**
     * Get total subscibers for the list
     *
     * @return integer
     */
    public function getTotalSubscribersAttribute()
    {
        return (int) $this->list['total_subscribers'];
    }

    /**
     * Get total subscribers subscribed today
     *
     * @return integer
     */
    public function getTotalSubscribersSubscribedTodayAttribute()
    {
        return (int) $this->list['total_subscribers_subscribed_today'];
    }

    /**
     * Get total subscribers subscribed yesterday
     *
     * @return array
     */
    public function getTotalSubscribersSubscribedYesterdayAttribute()
    {
        return (int) $this->list['total_subscribers_subscribed_yesterday'];
    }

    /**
     * Get total unconfirmed subscribers
     *
     * @return integer
     */
    public function getTotalUnconfirmedSubscribersAttribute()
    {
        return (int) $this->list['total_unconfirmed_subscribers'];
    }

    /**
     * Get total unsubscribed subscribers
     *
     * @return integer
     */
    public function getTotalUnsubscribedSubscribersAttribute()
    {
        return (int) $this->list['total_unsubscribed_subscribers'];
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

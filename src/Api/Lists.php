<?php

namespace CodeGreenCreative\Aweber\Api;

use Aweber;
use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Contracts\AweberApiContract;

class Lists extends AweberClient implements AweberApiContract
{
    private $list;

    /**
     * Paginate through all lists
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

        return $this->request('GET', 'lists', array('ws.start' => $start, 'ws.size' => $limit));
    }

    /**
     * Find a list by ID
     *
     * @param  integer $list_id
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($list_id)
    {
        $this->list = $this->request('GET', 'lists/' . $list_id);
        return $this;
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
     * Get tags for the list
     *
     * @return array
     */
    public function getTagsAttribute()
    {
        return $this->request('GET', 'lists/' . $this->list->id . '/tags');
    }

    /**
     * Get campaigns for the list
     *
     * NOT IMPLEMENTED
     *
     * @return array
     */
    // public function campaigns()
    // {
    // }

    /**
     * Get custom fields for the list
     *
     * NOT IMPLEMENTED
     *
     * @return array
     */
    // public function customFields()
    // {
    // }

    /**
     * Get Subscribers to the list
     *
     * NOT IMPLEMENTED
     *
     * @return array
     */
    // public function subscribers()
    // {
    // }

    /**
     * Get total subscribed subscribers to the list
     *
     * @return integer
     */
    public function getTotalSubscribedSubscribersAttribute()
    {
        return (int) $this->list->total_subscribed_subscribers;
    }

    /**
     * Get total subscibers for the list
     *
     * @return integer
     */
    public function getTotalSubscribersAttribute()
    {
        return (int) $this->list->total_subscribers;
    }

    /**
     * Get total subscribers subscribed today
     *
     * @return integer
     */
    public function getTotalSubscribersSubscribedTodayAttribute()
    {
        return (int) $this->list->total_subscribers_subscribed_today;
    }

    /**
     * Get total subscribers subscribed yesterday
     *
     * @return array
     */
    public function getTotalSubscribersSubscribedYesterdayAttribute()
    {
        return (int) $this->list->total_subscribers_subscribed_yesterday;
    }

    /**
     * Get total unconfirmed subscribers
     *
     * @return integer
     */
    public function getTotalUnconfirmedSubscribersAttribute()
    {
        return (int) $this->list->total_unconfirmed_subscribers;
    }

    /**
     * Get total unsubscribed subscribers
     *
     * @return integer
     */
    public function getTotalUnsubscribedSubscribersAttribute()
    {
        return (int) $this->list->total_unsubscribed_subscribers;
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

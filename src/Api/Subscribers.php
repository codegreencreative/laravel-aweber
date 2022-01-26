<?php

namespace CodeGreenCreative\Aweber\Api;

use Illuminate\Support\Str;
use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberException;

class Subscribers extends AweberClient
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

        return $this->request('GET', 'lists/' . $this->list_id . '/subscribers', [
            'ws.start' => $start,
            'ws.size' => $limit,
        ]);
    }

    /**
     * Find a subscriber by ID
     *
     * @param  integer $subscriber_id
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($subscriber_id)
    {
        $this->subscriber = (object) $this->request(
            'GET',
            'lists/' . $this->list_id . '/subscribers/' . $subscriber_id
        );
        return $this;
    }

    /**
     * Set the list to be used
     *
     * @param  integer $list_id
     * @return self
     */
    public function setList($list_id)
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
    public function add($data, $options = [])
    {
        $subscriber_id = $this->request('POST', 'lists/' . $this->list_id . '/subscribers', $data, $options);
        return $this->load($subscriber_id);
    }

    /**
     * Update a subscriber on a list
     *
     * Aweber API Reference
     * https://api.aweber.com/#tag/Subscribers/paths/~1accounts~1{accountId}~1lists~1{listId}~1subscribers/post
     *
     * @param integer $list_id
     * @param array $options
     */
    public function update($options)
    {
        $this->request('PATCH', 'lists/' . $this->list_id . '/subscribers/' . $this->subscriber->id, $options);
        // Reload the subscriber
        return $this->load($this->subscriber->id);
    }

    /**
     * Move a subscriber from one list to another
     *
     * @param  integer $destination_list_id
     * @return array
     */
    public function move($destination_list_id)
    {
        $this->request('POST', 'lists/' . $this->list_id . '/subscribers/' . $this->subscriber->id, [
            'ws.op' => 'move',
            'list_link' => $this->api_url . '/accounts/' . $this->account_id . '/lists/' . $destination_list_id,
        ]);
    }

    /**
     * Find a subscriber on a list
     *
     * @param  string $email
     * @return array
     */
    public function find($email)
    {
        $subscriber = $this->request('GET', 'lists/' . $this->list_id . '/subscribers', [
            'ws.op' => 'find',
            'email' => $email,
        ]);
        // dd($subscriber['entries']);
        if (!empty($subscriber['entries'])) {
            $this->subscriber = (object) $subscriber['entries'][0];
        }
        return $this->subscriber;
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

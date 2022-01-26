<?php

namespace CodeGreenCreative\Aweber\Api;

use Illuminate\Support\Str;
use CodeGreenCreative\Aweber\AweberClient;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberException;

class CustomFields extends AweberClient
{
    private $list_id;
    private $custom_field;

    /**
     * Paginate through all custom fields
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

        return $this->request('GET', 'lists/' . $this->list_id . '/custom_fields', [
            'ws.start' => $start,
            'ws.size' => $limit,
        ]);
    }

    /**
     * Find a custom field by ID
     *
     * @param  integer $custom_field_id
     * @return CodeGreenCreative\Aweber\Api\List
     */
    public function load($custom_field_id)
    {
        $this->custom_field = $this->request('GET', 'lists/' . $this->list_id . '/custom_fields/' . $custom_field_id);
        return $this;
    }

    /**
     * [getListAttribute description]
     * @return [type] [description]
     */
    public function getCustomFieldAttribute()
    {
        return $this->custom_field;
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

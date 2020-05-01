<?php

namespace CodeGreenCreative\Aweber\Contracts;

interface AweberApiContract
{
    /**
     * Paginate an Aweber API endpoint
     *
     * @param  integer $size  [description]
     * @param  integer $limit [description]
     * @return array
     */
    public function paginate($size, $limit);

    /**
     * Load an Aweber object by ID
     *
     * @param  integer $id
     * @return array
     */
    public function load($id);
}

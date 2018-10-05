<?php

namespace PHPCommons\Validator\Tests;

class ResultPair {

    public $item;
    public $isValid;

    /**
     * ResultPair constructor.
     *
     * @param $item
     * @param $valid
     */
    public function __construct($item, $valid) {
        $this->item = $item;
        $this->isValid = $valid;
    }

}
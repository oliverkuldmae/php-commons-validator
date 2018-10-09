<?php

namespace PHPCommons\Validator\Rules;

interface Rule {

    public function isValid($input = null) : bool;

}
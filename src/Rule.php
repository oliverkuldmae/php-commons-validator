<?php

namespace PHPCommons\Validator;

interface Rule {

    public function isValid($input) : bool;

}
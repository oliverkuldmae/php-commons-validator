# PHP Commons Validator &middot; [![GitHub license](https://img.shields.io/badge/license-Apache2-blue.svg)](https://github.com/oliverkuldmae/php-commons-validator/blob/0.x/LICENSE.txt) [![Build Status](https://travis-ci.org/oliverkuldmae/php-commons-validator.svg?branch=0.x)](https://travis-ci.org/oliverkuldmae/php-commons-validator)

PHP port of [Apache Commons Validator](https://commons.apache.org/proper/commons-validator/).

### Installation
Install the library with Composer:
 
 `composer require oliverkuldmae/php-commons-validator`

### Examples
```php
<?php

use \PHPCommons\Validator\Rule\Email;

$validator = Email::getInstance();
$validator->isValid('some.person@example.com'); // => true
$validator->isValid('some.person@example.fbr'); // => false
```

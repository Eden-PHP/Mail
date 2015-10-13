<?php //-->
/**
 * This file is part of the Eden PHP Library.
 * (c) 2014-2016 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
ob_start();
if(file_exists(__DIR__.'/../../../autoload.php')) {
    require_once __DIR__.'/../../../autoload.php';
} else {
    require_once __DIR__.'/../vendor/autoload.php';
}


if(file_exists(__DIR__.'/../../core/src/Control.php')) {
    require_once __DIR__.'/../../core/src/Control.php';
} else {
    require_once __DIR__.'/../vendor/eden/core/src/Control.php';
}


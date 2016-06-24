<?php
/**
 * Created by PhpStorm.
 * User: kino
 * Date: 2016/06/24
 * Time: 16:01
 */

function autoloader($class) {
	include 'libs/' . $class . '.php';
}

spl_autoload_register('autoloader');
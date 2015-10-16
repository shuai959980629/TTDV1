<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/hooks.html
|
 */


$hook['pre_system'] = array(
    'class' => 'Spl_autoloader',
    'function' => 'register',
    'filename' => 'Spl_autoloader.php',
    'filepath' => 'hooks',
    'params' => array(BASEPATH.'../com_party/libraries/', BASEPATH.'../com_party/events/')
);

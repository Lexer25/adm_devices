<?php defined('SYSPATH') or die('No direct script access.');

defined('DEVICES_VERSION') OR define('DEVICES_VERSION', '2.0.3');

Kohana::$config->load('adm')
    ->set('devices', array(
        'title' => 'Устройства',
        'url' => 'devices',
        'icon' => 'fa-cog',
        'order' => 3,
    ));

// Маршрут для устройств
Route::set('devices', 'devices(/<action>(/<id>))', array(
    'action' => '(index|add|edit|delete|table|tree|matrix)',
))
->defaults(array(
    'controller' => 'devices',
    'action'     => 'index',
));
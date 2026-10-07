<?php defined('SYSPATH') or die('No direct script access.');

defined('DEVICES_VERSION') OR define('DEVICES_VERSION', '2.2.0');

Kohana::$config->load('adm')
    ->set('devices', array(
        'title' => 'Устройства',
        'url' => 'devices',
        'icon' => 'fa-cog',
        'order' => 10,
    ));

// Маршрут для устройств
// P0. Обязательно ->defaults(array('controller' => ...)): без него
// Route::matches() вернёт параметры без ключа 'controller' и Request::execute()
// упадёт с «Undefined index: controller».

Route::set('devices', 'devices(/<action>(/<id>))', array(
    'action' => '(index|add|edit|delete|table|tree|matrix|groups)',
))
    ->defaults(array(
        'controller' => 'Devices',
        'action'     => 'index',
    ));



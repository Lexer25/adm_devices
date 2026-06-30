<?php defined('SYSPATH') or die('No direct script access.');

defined('DEVICES_VERSION') OR define('DEVICES_VERSION', '2.0.1');

Kohana::$config->load('adm')
    ->set('devices', array(
        'title' => 'Устройства',
        'url' => 'devices',
        'icon' => 'fa-cog',
        'order' => 3,
    ));

// ✅ Исправленный маршрут
Route::set('devices', 'devices(/<action>(/<id>))')
    ->defaults(array(
        'controller' => 'devices',  // ✅ Правильный контроллер
        'action'     => 'index',
    ));
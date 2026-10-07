<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Языковые строки модуля «Устройства».
 *
 * Ключ 'unlock_door_log_title' используется в Model_Device::unlock_door_arr()
 * (без перевода в журнал команды попадало бы само имя ключа).
 */
return array(
	'unlock_door_log_title'        => 'Выполнение команды управления точками прохода',

	'door_command_opendoor'        => 'Открыть 1 раз',
	'door_command_lockdoor'        => 'Закрыть навсегда',
	'door_command_opendooralways'  => 'Открыть навсегда',
	'door_command_unlockdoor'      => 'Разблокировать',

	'access_points_control_title'  => 'Управление группами точек прохода',
);

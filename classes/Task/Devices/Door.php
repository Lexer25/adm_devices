<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Управление точками прохода из командной строки — задача `devices:door`.
 *
 * Выполняет команду для набора точек прохода. Набор задаётся списком ID_DEV
 * и/или группами устройств (таблица DEVGROUP, включая вложенные группы) —
 * то же, что делает страница «Устройства → Группы точек прохода».
 *
 * Команды:
 *   opendoor        Открыть 1 раз
 *   lockdoor        Закрыть навсегда
 *   opendooralways  Открыть навсегда
 *   unlockdoor      Разблокировать
 *
 * Запуск:
 *   c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion ^
 *       --task=devices:door --command=opendoor --id_dev=483,484
 *
 *   ... --task=devices:door --command=lockdoor --group=2
 *   ... --task=devices:door --command=unlockdoor --group=2 --test=1
 *   ... --task=devices:door --list=1
 *
 * Внимание: список ID_DEV в командной строке нельзя разрывать пробелом —
 * «--id_dev=483, 484» это уже два аргумента. Задача отнесёт «484» к списку
 * ID_DEV и предупредит; надёжнее --id_dev=483,484 или --id_dev="483, 484".
 *
 * Опции:
 *   --command  КОД      команда управления (обязательна, кроме --list=1)
 *   --id_dev   СПИСОК   ID_DEV точек прохода: «483,484 508;512»
 *   --group    СПИСОК   ID или названия групп устройств: «2,3» / «зона входа»
 *   --test     1|0      только показать, что будет затронуто (по умолчанию 0)
 *   --list     1|0      показать группы устройств с числом точек прохода и выйти
 *   --force    1|0      отправить команду даже при недоступном ТС-сервере
 *   --encoding auto|utf8|cp866|cp1251
 *                       кодировка вывода в консоль (по умолчанию auto)
 *
 * Перед отправкой команды проверяется связь с транспортными серверами
 * (ip:порт из таблицы SERVER). Если ТС недоступен, команда не отправляется,
 * а в консоль выводится причина.
 *
 * Коды возврата: 0 — команда выполнена (или тестовый прогон),
 *                1 — ошибка входных данных или исключение.
 *
 * @version 1.0.0
 * @date    2026-10-07
 */
class Task_Devices_Door extends Minion_Task
{
	/**
	 * Пауза после команды, сек: контроллер должен успеть применить команду,
	 * после чего читается новое состояние точки прохода (как в веб-форме).
	 */
	const STATE_PAUSE = 2;

	/**
	 * Таймаут проверки связи с транспортным сервером, сек.
	 */
	const CONNECT_TIMEOUT = 3;

	protected $_options = array(
		'command'  => NULL,
		'id_dev'   => NULL,
		'group'    => NULL,
		'test'     => 0,
		'list'     => 0,
		'force'    => 0,
		'encoding' => 'auto',
	);

	/**
	 * Кодировка вывода в консоль (файл задачи — в UTF-8).
	 * @var string
	 */
	protected $_encoding = 'UTF-8';

	/**
	 * Кэш списка групп устройств.
	 * @var array|NULL
	 */
	protected $_groups = NULL;

	/**
	 * Выполнение задачи.
	 *
	 * @param array $params опции командной строки
	 * @return void
	 */
	protected function _execute(array $params)
	{
		// Файл задачи в UTF-8, консоль Windows обычно CP866/CP1251 —
		// весь вывод перекодируется в console_encoding().
		$this->_encoding = $this->console_encoding(Arr::get($params, 'encoding', 'auto'));

		$model = Model::factory('Devicem');

		// Режим «показать группы устройств и выйти».
		if ((int) Arr::get($params, 'list', 0) === 1)
		{
			$this->print_groups($model);
			return;
		}

		$commands = $this->commands();
		$command = strtolower(trim((string) Arr::get($params, 'command')));

		$this->write('=== Управление точками прохода (задача devices:door) ===');

		if ($command === '')
		{
			$this->fail('не задана команда. Укажите --command=КОД: '.implode(', ', array_keys($commands)).'.');
		}

		if ( ! isset($commands[$command]))
		{
			$this->fail('неизвестная команда "'.$command.'". Доступные команды: '.implode(', ', array_keys($commands)).'.');
		}

		// Набор точек прохода: список ID_DEV и/или группы устройств.
		// Аргументы без имени Minion складывает в позиционные ключи:
		// «--id_dev=483, 484» превращается в «--id_dev=483,» + позиционный «484».
		$positional = $this->positional_values($params);

		if ($positional !== '')
		{
			$this->write('Замечание: аргументы без имени ('.trim($positional).') отнесены к списку ID_DEV.');
			$this->write('           Пробел разрывает список — надёжнее --id_dev="483, 484"');
		}

		$id_dev = $this->parse_ids(Arr::get($params, 'id_dev').' '.$positional);
		$group_ids = $this->parse_groups(Arr::get($params, 'group'), $model);

		if (empty($id_dev) AND empty($group_ids))
		{
			$this->fail('не задан набор точек прохода. Укажите --id_dev=СПИСОК и/или --group=СПИСОК (список групп: --list=1).');
		}

		// Группы раскрываются в точки прохода вместе с вложенными группами.
		foreach ($model->get_group_access_point_ids($group_ids) as $id)
		{
			$id_dev[$id] = $id;
		}

		// Команда выполняется только для существующих точек прохода:
		// контроллеры, серверы и несуществующие ID_DEV отсекаются.
		$targets = $model->filter_access_points($id_dev);

		// Что из указанного не является точкой прохода (частая причина ошибок).
		$unknown = array_diff_key($id_dev, $targets);

		if (empty($targets))
		{
			if ( ! empty($unknown))
			{
				$this->write('Не найдены среди точек прохода: '.implode(', ', array_keys($unknown)));
			}

			$this->fail('среди выбранных устройств нет ни одной точки прохода. Проверьте ID_DEV (ID должны быть точками прохода) и группы, список групп: --list=1.');
		}

		$test_mode = ((int) Arr::get($params, 'test', 0) === 1);
		$force = ((int) Arr::get($params, 'force', 0) === 1);
		$points = $model->get_access_points_by_ids($targets);

		$this->write('Команда:       '.$commands[$command].' ('.$command.')');
		$this->write('Режим:         '.($test_mode
			? 'ТЕСТОВЫЙ ПРОГОН — команды контроллерам не отправляются'
			: 'ВЫПОЛНЕНИЕ'));

		if ( ! empty($group_ids))
		{
			$titles = array();

			foreach ($this->groups_by_id($model) as $group_id => $group)
			{
				if (isset($group_ids[$group_id]))
				{
					$titles[] = $group['path'].' (ID '.$group_id.')';
				}
			}

			$this->write('Группы:        '.implode(', ', $titles));
		}

		$this->write('Точек прохода: '.count($targets));

		if ( ! empty($unknown))
		{
			$this->write('Пропущены (не точки прохода): '.implode(', ', array_keys($unknown)));
		}

		$this->write('');
		$this->print_points($points);

		// Данные о контроллерах и транспортных серверах — один раз на точку
		// прохода (используются и для проверки связи, и для опроса состояния).
		$device = Model::factory('Device');
		$info_by_point = array();

		foreach ($targets as $id_dev)
		{
			$info_by_point[$id_dev] = $device->get_device_info($id_dev);
		}

		// Проверка связи с транспортными серверами до отправки команды:
		// sendCommand() при неудачном подключении делает HTTP::redirect(),
		// что в консоли выглядит как HTTP_Exception_302 без пояснений.
		$servers = $this->transport_servers($points, $info_by_point);
		$offline = $this->print_transport_servers($servers);

		if ( ! empty($offline) AND ! $force AND ! $test_mode)
		{
			$this->write('');
			$this->fail('команда не отправлена: нет связи с транспортным сервером ('
				.implode(', ', array_keys($offline)).'). Проверьте ТС-сервер и его доступность.'
				.' Отправить всё равно: --force=1.');
		}

		if ( ! empty($offline) AND $force)
		{
			$this->write('ВНИМАНИЕ: --force=1 — команда отправляется при недоступном ТС-сервере.');
		}

		if ($test_mode)
		{
			$this->write('');
			$this->write('Тестовый прогон завершён: команды не отправлялись.');
			return;
		}

		// Выполнение команды: Model_Device::unlock_door_arr() формирует для
		// каждой точки прохода команду вида «<команда> Door=<ID_READER>».
		$this->write('');
		$this->write('Отправляю команду контроллерам...');

		try
		{
			$log = $device->unlock_door_arr($targets, $command);
		}
		catch (Exception $e)
		{
			$this->write('');
			$this->write('ОШИБКА при отправке команды: '.get_class($e)
				.($e->getMessage() !== '' ? ' — '.$e->getMessage() : ''));
			$this->fail('команда не отправлена или отправлена частично: проверьте состояние точек прохода ('
				.'--task=devices:door --command=opendoor --id_dev=... --test=1 показывает список).');
		}

		$this->write($this->plain_log($log));

		// Пауза и опрос нового состояния — как на странице devices/groups.
		sleep(self::STATE_PAUSE);
		$controllers = $this->poll_controllers($info_by_point);

		Log::instance()->add(Log::NOTICE, 'Задача devices:door: команда='.$command
			.', точек прохода='.count($targets).', id_dev='.implode(',', $targets));

		$this->write('');
		$this->write('Готово. Точек прохода: '.count($targets)
			.', контроллеров опрошено: '.count($controllers).'.');
	}

	/**
	 * Справка по задаче: текст docblock выводится в кодировке консоли.
	 *
	 * @param array $params опции командной строки
	 * @return void
	 */
	protected function _help(array $params)
	{
		$this->_encoding = $this->console_encoding(Arr::get($params, 'encoding', 'auto'));

		ob_start();
		parent::_help($params);
		$help = ob_get_clean();

		echo $this->to_console($help);
	}

	/**
	 * Команды управления точкой прохода (те же коды, что и в веб-форме).
	 *
	 * @return array код => название
	 */
	protected function commands()
	{
		return array(
			'opendoor'       => __('door_command_opendoor'),
			'lockdoor'       => __('door_command_lockdoor'),
			'opendooralways' => __('door_command_opendooralways'),
			'unlockdoor'     => __('door_command_unlockdoor'),
		);
	}

	/**
	 * Разбор списка ID: «731,734 737;740» => array(731 => 731, ...).
	 *
	 * @param mixed $value значение опции
	 * @return array массив вида id => id
	 */
	protected function parse_ids($value)
	{
		$result = array();

		foreach (preg_split('/[^0-9]+/', (string) $value) as $id)
		{
			$id = (int) $id;

			if ($id > 0) $result[$id] = $id;
		}

		return $result;
	}

	/**
	 * Разбор опции --group: ID групп и/или их названия.
	 * Названия ищутся среди групп устройств (с учётом кодировки консоли).
	 *
	 * @param mixed $value значение опции
	 * @param Model_Devicem $model модель групп
	 * @return array массив вида id группы => id группы
	 */
	protected function parse_groups($value, $model)
	{
		$result = array();
		$names = array();

		// Названия групп могут содержать пробелы, поэтому разделители — «,» и «;».
		foreach (preg_split('/[,;]+/', (string) $value) as $item)
		{
			$item = trim($item);

			if ($item === '') continue;

			if (ctype_digit($item))
			{
				$result[(int) $item] = (int) $item;
			}
			else
			{
				$names[] = $item;
			}
		}

		$groups = $this->groups_by_id($model);

		if ( ! empty($names))
		{
			foreach ($names as $name)
			{
				$matched = array();

				foreach ($groups as $group)
				{
					// Название из консоли приходит в кодировке консоли —
					// сравниваем и как есть, и после перевода в UTF-8.
					if (strcasecmp($group['name'], $name) === 0
						OR strcasecmp($group['name'], $this->to_utf8($name)) === 0)
					{
						$matched[] = $group;
					}
				}

				if (empty($matched))
				{
					$this->fail('группа "'.$name.'" не найдена. Список групп: --list=1');
				}

				if (count($matched) > 1)
				{
					$variants = array();

					foreach ($matched as $group)
					{
						$variants[] = $group['id'].' ('.$group['path'].')';
					}

					$this->fail('название "'.$name.'" подходит нескольким группам: '
						.implode(', ', $variants).'. Укажите ID группы.');
				}

				$result[$matched[0]['id']] = $matched[0]['id'];
			}
		}

		// Проверка выбранных групп: неизвестные и пустые.
		foreach ($result as $group_id)
		{
			if ($group_id === Model_Devicem::DEVGROUP_ROOT)
			{
				$this->fail('группа '.$group_id.' — служебная («все устройства»). Укажите конкретные группы (список: --list=1).');
			}

			if ( ! isset($groups[$group_id]))
			{
				$this->fail('группа с ID '.$group_id.' не найдена. Список групп: --list=1');
			}

			if ((int) $groups[$group_id]['total'] === 0)
			{
				$this->write('Предупреждение: в группе "'.$groups[$group_id]['path']
					.'" (ID '.$group_id.') нет точек прохода.');
			}
		}

		return $result;
	}

	/**
	 * Группы устройств, проиндексированные по ID.
	 *
	 * @param Model_Devicem $model модель групп
	 * @return array id => array(id, name, path, direct, total, level)
	 */
	protected function groups_by_id($model)
	{
		if ($this->_groups === NULL)
		{
			$this->_groups = array();

			foreach ($model->get_device_groups() as $group)
			{
				$this->_groups[$group['id']] = $group;
			}
		}

		return $this->_groups;
	}

	/**
	 * Вывод списка групп устройств (режим --list=1).
	 *
	 * @param Model_Devicem $model модель групп
	 * @return void
	 */
	protected function print_groups($model)
	{
		$groups = $model->get_device_groups();

		$this->write('=== Группы устройств (DEVGROUP) ===');

		if (empty($groups))
		{
			$this->write('Группы устройств не найдены.');
			return;
		}

		$this->write($this->pad('ГРУППА', 40).$this->pad('ID', 8).'ТОЧЕК ПРОХОДА');

		foreach ($groups as $group)
		{
			$line = $this->pad(str_repeat('    ', $group['level']).$group['name'], 40)
				.$this->pad($group['id'], 8)
				.$group['total'];

			if ((int) $group['direct'] !== (int) $group['total'])
			{
				$line .= ' (в самой группе: '.$group['direct'].')';
			}

			$this->write($line);
		}

		$this->write('');
		$this->write('Выбрать группу: --group=ID (можно несколько через запятую) или --group="название"');
	}

	/**
	 * Вывод таблицы выбранных точек прохода.
	 *
	 * @param array $points строки из Model_Devicem::get_access_points_by_ids()
	 * @return void
	 */
	protected function print_points(array $points)
	{
		$this->write($this->pad('ID_DEV', 8).$this->pad('ЧТН', 5)
			.$this->pad('ТОЧКА ПРОХОДА', 45).$this->pad('КОНТРОЛЛЕР', 30).'ТС');

		foreach ($points as $point)
		{
			$this->write($this->pad($point['ID_DEV'], 8)
				.$this->pad($point['ID_READER'], 5)
				.$this->pad($this->point_name($point), 45)
				.$this->pad($this->controller_name($point), 30)
				.(string) $point['SERVER_NAME']);
		}
	}

	/**
	 * Название точки прохода (в БД может быть пустым или 'NULL').
	 *
	 * @param array $point строка точки прохода
	 * @return string
	 */
	protected function point_name(array $point)
	{
		$name = isset($point['NAME']) ? $point['NAME'] : '';

		if ($name === NULL OR $name === '' OR $name === 'NULL')
		{
			$name = 'Точка прохода '.$point['ID_DEV'];
		}

		return $name;
	}

	/**
	 * Название контроллера.
	 *
	 * @param array $point строка точки прохода
	 * @return string
	 */
	protected function controller_name(array $point)
	{
		$name = isset($point['CONTROLLER_NAME']) ? $point['CONTROLLER_NAME'] : '';

		if ($name === NULL OR $name === '' OR $name === 'NULL')
		{
			$name = 'Контроллер '.$point['CONTROLLER_ID'];
		}

		return $name;
	}

	/**
	 * Опрос нового состояния контроллеров, затронутых командой.
	 *
	 * @param array $info_by_point данные Model_Device::get_device_info() по точкам прохода
	 * @return array список опрошенных контроллеров (id => id)
	 */
	protected function poll_controllers(array $info_by_point)
	{
		$device = Model::factory('Device');
		$controllers = array();

		foreach ($info_by_point as $info)
		{
			$controller_id = Arr::get($info, 'device_id');

			if ($controller_id AND ! isset($controllers[$controller_id]))
			{
				$controllers[$controller_id] = $controller_id;
				$device->getStatForOneController($controller_id);
			}
		}

		return $controllers;
	}

	/**
	 * Аргументы командной строки без имени: Minion складывает их
	 * в позиционные (числовые) ключи массива опций.
	 *
	 * @param array $params опции командной строки
	 * @return string значения через пробел
	 */
	protected function positional_values(array $params)
	{
		$values = '';

		foreach ($params as $key => $value)
		{
			if (is_int($key) AND (is_string($value) OR is_numeric($value)))
			{
				$values .= ' '.$value;
			}
		}

		return $values;
	}

	/**
	 * Транспортные серверы (ip:порт), через которые работают точки прохода.
	 *
	 * @param array $points точки прохода (с названием ТС)
	 * @param array $info_by_point данные get_device_info() по точкам прохода
	 * @return array endpoint => array(name, ip, port, points)
	 */
	protected function transport_servers(array $points, array $info_by_point)
	{
		$names = array();

		foreach ($points as $point)
		{
			$names[$point['ID_DEV']] = (string) $point['SERVER_NAME'];
		}

		$servers = array();

		foreach ($info_by_point as $id_dev => $info)
		{
			$ip = (string) Arr::get($info, 'ip_server');
			$port = (string) Arr::get($info, 'port');
			$endpoint = $ip.':'.$port;

			if ( ! isset($servers[$endpoint]))
			{
				$servers[$endpoint] = array(
					'name'   => isset($names[$id_dev]) ? $names[$id_dev] : '',
					'ip'     => $ip,
					'port'   => $port,
					'points' => array(),
				);
			}

			$servers[$endpoint]['points'][] = $id_dev;
		}

		return $servers;
	}

	/**
	 * Проверка связи с транспортными серверами и вывод результата.
	 *
	 * @param array $servers список ТС из transport_servers()
	 * @return array недоступные ТС: endpoint => описание ошибки
	 */
	protected function print_transport_servers(array $servers)
	{
		$this->write('');
		$this->write('Транспортные серверы:');

		$offline = array();

		foreach ($servers as $endpoint => $server)
		{
			$check = $this->check_connection($server['ip'], $server['port']);

			$title = ($server['name'] !== '') ? $server['name'] : 'ТС';
			$common = $title.' — '.$endpoint.' (точек прохода: '.count($server['points']).')';

			if ($check['ok'])
			{
				$this->write('  '.$common.': связь есть');
			}
			else
			{
				$this->write('  '.$common.': НЕТ СВЯЗИ — '.$check['error']);
				$offline[$endpoint] = $check['error'];
			}
		}

		return $offline;
	}

	/**
	 * Проверка TCP-соединения с ТС-сервером (команды не отправляются).
	 *
	 * @param string $ip адрес ТС
	 * @param mixed $port порт ТС
	 * @return array array('ok' => bool, 'error' => string)
	 */
	protected function check_connection($ip, $port)
	{
		$result = array('ok' => FALSE, 'error' => '');

		if ($ip === '' OR $ip === '0.0.0.0')
		{
			$result['error'] = 'в настройках ТС не задан адрес (поле IP)';
			return $result;
		}

		if ( ! $port)
		{
			$result['error'] = 'в настройках ТС не задан порт (поле PORT)';
			return $result;
		}

		$errno = 0;
		$errstr = '';

		$socket = @fsockopen($ip, (int) $port, $errno, $errstr, self::CONNECT_TIMEOUT);

		if ($socket)
		{
			@fclose($socket);
			$result['ok'] = TRUE;
			return $result;
		}

		$result['error'] = $this->socket_error_text($errno, $errstr);

		return $result;
	}

	/**
	 * Текст ошибки соединения: коды Winsock + системное сообщение.
	 *
	 * @param int $errno код ошибки
	 * @param string $errstr системное сообщение (в кодировке Windows)
	 * @return string
	 */
	protected function socket_error_text($errno, $errstr)
	{
		switch ((int) $errno)
		{
			case 10060:
				return 'таймаут соединения ('.self::CONNECT_TIMEOUT.' с) — сервер не отвечает';
			case 10061:
				return 'соединение отклонено — порт закрыт';
			case 10065:
				return 'узел недоступен';
			case 10051:
				return 'сеть недоступна';
			case 11001:
				return 'не удалось разрешить адрес';
			case 0:
				return 'не удалось подключиться';
		}

		$message = trim((string) $errstr);

		if ($message !== '')
		{
			// Системное сообщение приходит в кодировке Windows (обычно CP1251).
			$message = @iconv('Windows-1251', 'UTF-8//IGNORE', $message);
		}

		return 'ошибка '.$errno.($message !== '' ? ': '.$message : '');
	}

	/**
	 * Журнал выполнения команды приводится к текстовому виду для консоли.
	 *
	 * @param string $log HTML-журнал из Model_Device::unlock_door_arr()
	 * @return string
	 */
	protected function plain_log($log)
	{
		$log = str_replace(array('<br>', '<br/>', '<br />'), "\n", (string) $log);

		return trim(strip_tags($log));
	}

	/**
	 * Выравнивание строки по ширине (в символах, а не в байтах).
	 * Последний пробел гарантируется — он разделяет колонки таблицы.
	 *
	 * @param string $text текст
	 * @param int $width ширина колонки
	 * @return string
	 */
	protected function pad($text, $width)
	{
		$text = (string) $text;
		$length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

		if ($length > $width)
		{
			$text = function_exists('mb_substr')
				? mb_substr($text, 0, $width - 3, 'UTF-8').'...'
				: substr($text, 0, $width - 3).'...';
			$length = $width;
		}

		return $text.str_repeat(' ', max(1, $width - $length));
	}

	/**
	 * Кодировка консоли для вывода.
	 *
	 * @param string $option значение опции --encoding
	 * @return string имя кодировки для iconv()
	 */
	protected function console_encoding($option)
	{
		switch (strtolower(trim((string) $option)))
		{
			case '':
			case 'auto':
				return $this->detect_console_encoding();
			case 'utf8':
			case 'utf-8':
			case '65001':
				return 'UTF-8';
			case 'cp866':
			case '866':
				return 'CP866';
			case 'cp1251':
			case '1251':
				return 'CP1251';
		}

		// Незнакомое значение — доверяем пользователю (например, CP1252).
		return strtoupper(trim((string) $option));
	}

	/**
	 * Определение кодовой страницы консоли Windows.
	 * При неудаче возвращается CP866 — кодовая страница консоли по умолчанию.
	 *
	 * @return string имя кодировки для iconv()
	 */
	protected function detect_console_encoding()
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
		{
			return 'UTF-8';
		}

		$codepage = 0;

		if (function_exists('shell_exec'))
		{
			// Цифры в ответе «chcp» — ASCII, поэтому разбор не зависит от кодировки.
			$output = @shell_exec('chcp 2>NUL');

			if (preg_match('/(\d{3,5})/', (string) $output, $matches))
			{
				$codepage = (int) $matches[1];
			}
		}

		switch ($codepage)
		{
			case 65001: return 'UTF-8';
			case 1251:  return 'CP1251';
			case 866:   return 'CP866';
		}

		return 'CP866';
	}

	/**
	 * Перевод строки из UTF-8 в кодировку консоли.
	 *
	 * @param string $text текст в UTF-8
	 * @return string
	 */
	protected function to_console($text)
	{
		$text = (string) $text;

		if ($text === '' OR $this->_encoding === 'UTF-8') return $text;

		$converted = @iconv('UTF-8', $this->_encoding.'//TRANSLIT', $text);

		if ($converted === FALSE)
		{
			$converted = @iconv('UTF-8', $this->_encoding.'//IGNORE', $text);
		}

		return ($converted === FALSE) ? $text : $converted;
	}

	/**
	 * Перевод строки из кодировки консоли в UTF-8.
	 *
	 * @param string $text текст в кодировке консоли
	 * @return string текст в UTF-8
	 */
	protected function to_utf8($text)
	{
		$text = (string) $text;

		if ($text === '' OR $this->_encoding === 'UTF-8') return $text;

		$converted = @iconv($this->_encoding, 'UTF-8//IGNORE', $text);

		return ($converted === FALSE) ? $text : $converted;
	}

	/**
	 * Вывод строки в консоль.
	 *
	 * @param string $text текст в UTF-8
	 * @return void
	 */
	protected function write($text = '')
	{
		Minion_CLI::write($this->to_console($text));
	}

	/**
	 * Вывод ошибки и завершение с кодом 1.
	 *
	 * @param string $message сообщение об ошибке
	 * @return void
	 */
	protected function fail($message)
	{
		$this->write('ОШИБКА: '.$message);

		exit(1);
	}
}

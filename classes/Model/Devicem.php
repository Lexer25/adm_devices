<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Devicem extends Model {
    
    /**
     * Преобразование строк из win1251 в utf-8 (для чтения)
     */
    private function win1251_to_utf8($string)
    {
        if (empty($string) || $string === 'NULL' || $string === null) {
            return $string;
        }
        return iconv('Windows-1251', 'UTF-8//IGNORE', $string);
    }
    
    /**
     * Преобразование строк из utf-8 в win1251 (для записи)
     */
    private function utf8_to_win1251($string)
    {
        if (empty($string) || $string === 'NULL' || $string === null) {
            return $string;
        }
        return iconv('UTF-8', 'Windows-1251//IGNORE', $string);
    }
    
    /**
     * Экранирование строки для безопасной вставки в SQL
     */
    private function quote($value)
    {
        if ($value === null || $value === '') {
            return 'NULL';
        }
        return "'" . str_replace("'", "''", $value) . "'";
    }
    
    /**
     * Рекурсивное преобразование всех строковых значений в массиве (win1251 -> utf-8)
     */
    private function convert_array_encoding($array)
    {
        if (!is_array($array)) {
            return $this->win1251_to_utf8($array);
        }
        
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->convert_array_encoding($value);
            } elseif (is_string($value) && !empty($value) && $value !== 'NULL') {
                $result[$key] = $this->win1251_to_utf8($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    
    public function get_controllers_with_doors()
    {
        $sql = "
            SELECT 
                d1.ID_DEV as CONTROLLER_ID,
                d1.NAME as CONTROLLER_NAME,
                d1.NETADDR as CONTROLLER_NETADDR,
                d1.ID_CTRL,
                d2.ID_DEV as DOOR_ID,
                d2.NAME as DOOR_NAME,
                d2.NETADDR as DOOR_NETADDR,
                d2.ID_READER,
                s.NAME as SERVER_NAME,
                dt.NAME as DEVTYPE_NAME
            FROM DEVICE d1
            LEFT JOIN DEVICE d2 ON d1.ID_CTRL = d2.ID_CTRL 
                AND d2.ID_READER IN (0, 1)
            LEFT JOIN SERVER s ON d1.ID_SERVER = s.ID_SERVER
            LEFT JOIN DEVTYPE dt ON d1.ID_DEVTYPE = dt.ID_DEVTYPE
            WHERE d1.ID_READER IS NULL
            ORDER BY d1.ID_CTRL ASC, d2.ID_READER ASC
        ";
        
        $result = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return $this->convert_array_encoding($result);
    }
    
    public function get_controllers_grouped()
    {
        $results = $this->get_controllers_with_doors();
        $grouped = array();
        
        foreach ($results as $row) {
            $ctrl_id = $row['ID_CTRL'];
            
            if (!isset($grouped[$ctrl_id])) {
                $grouped[$ctrl_id] = array(
                    'controller' => array(
                        'ID_DEV' => $row['CONTROLLER_ID'],
                        'NAME' => $row['CONTROLLER_NAME'] ?: 'Без названия',
                        'NETADDR' => $row['CONTROLLER_NETADDR'] ?: '—',
                        'ID_CTRL' => $row['ID_CTRL'],
                        'server_name' => $row['SERVER_NAME'] ?: '—',
                        'devtype_name' => $row['DEVTYPE_NAME'] ?: 'По умолчанию'
                    ),
                    'doors' => array()
                );
            }
            
            if ($row['DOOR_ID'] !== NULL && in_array($row['ID_READER'], array(0, 1))) {
                $grouped[$ctrl_id]['doors'][] = array(
                    'ID_DEV' => $row['DOOR_ID'],
                    'NAME' => $row['DOOR_NAME'] ?: 'Дверь ' . $row['DOOR_ID'],
                    'NETADDR' => $row['DOOR_NETADDR'] ?: '—',
                    'ID_READER' => $row['ID_READER']
                );
            }
        }
        
        return $grouped;
    }
    
    /**
     * Получить список серверов
     */
    public function get_servers()
    {
        $sql = "SELECT ID_SERVER, NAME FROM SERVER ORDER BY NAME";
        $result = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return $this->convert_array_encoding($result);
    }
    
    /**
     * Получить список типов устройств
     */
    public function get_devtypes()
    {
        $sql = "SELECT ID_DEVTYPE, NAME FROM DEVTYPE ORDER BY NAME";
        $result = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return $this->convert_array_encoding($result);
    }
    
    /**
     * Получить следующий свободный ID_CTRL
     */
    private function get_next_id_ctrl()
    {
        $sql = "SELECT MAX(ID_CTRL) as max_id FROM DEVICE";
        $result = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->get('MAX_ID');
   
        return $result+1;
    }
    
    /**
     * Получить следующий свободный ID_DEV
     */
    private function get_next_id_dev()
    {
      		
		 $genResult = DB::query(Database::SELECT, 'SELECT GEN_ID(GEN_DEV_ID, 1) as gen FROM RDB$DATABASE')->execute(Database::instance('fb'));
                    $newId = 0;
                    foreach ($genResult as $row) {
                        $newId = $row['GEN'];
                        break;
                    }
					
					
        return $newId;
    }
    
    /**
     * Сохранить контроллер
     */
    public function save_controller($data)
    {
        // Получаем следующий ID_DEV
        $new_id_dev = $this->get_next_id_dev();
        
        // Получаем следующий ID_CTRL
        $new_id_ctrl = $this->get_next_id_ctrl();
		
        
        // Преобразуем NAME из UTF-8 в Windows-1251
        $name_win1251 = $this->utf8_to_win1251($data['NAME']);
        
        // Экранируем значения
        $name_escaped = $this->quote($name_win1251);
        $netaddr = !empty($data['NETADDR']) ? $this->quote($data['NETADDR']) : 'NULL';
        $id_server = !empty($data['ID_SERVER']) ? (int)$data['ID_SERVER'] : 'NULL';
        $id_devtype = !empty($data['ID_DEVTYPE']) ? (int)$data['ID_DEVTYPE'] : 'NULL';
        
        $sql = "
            INSERT INTO DEVICE (
                ID_DEV,
                NAME,
                NETADDR,
                ID_CTRL,
                ID_SERVER,
                ID_DEVTYPE,
                ID_READER
            ) VALUES (
                {$new_id_dev},
                {$name_escaped},
                {$netaddr},
                {$new_id_ctrl},
                {$id_server},
                {$id_devtype},
                NULL
            )
        ";
		
   // Kohana::$log->add(Log::DEBUG, '221 '.$sql);         
        DB::query(Database::INSERT, $sql)
            ->execute(Database::instance('fb'));
        
        return array(
            'id_dev' => $new_id_dev,
            'id_ctrl' => $new_id_ctrl,
            'id_devtype' => $id_devtype
        );
    }
    
    /**
     * Сохранить точку прохода (дверь)
     * 
     * @param int $id_ctrl ID_CTRL контроллера
     * @param string $name Название точки прохода
     * @param int $id_reader ID_READER (0 или 1)
     * @param string $netaddr NetAddr (опционально)
     * @return int ID_DEV созданной точки прохода
     */
    public function save_accesspoint($id_ctrl, $name, $id_reader, $id_devtype)
    {
        // Получаем следующий ID_DEV
        $new_id_dev = $this->get_next_id_dev();
		
		//echo Debug::vars('247', $id_ctrl, $name, $id_reader, $id_devtype, $new_id_dev);exit;
        
        // Преобразуем NAME из UTF-8 в Windows-1251
        $name_win1251 = $this->utf8_to_win1251($name);
        
        // Экранируем значения
        $name_escaped = $this->quote($name_win1251);
        $netaddr_escaped = !empty($netaddr) ? $this->quote($netaddr) : 'NULL';
        
        $sql = "
            INSERT INTO DEVICE (
                ID_DEV,
                NAME,
                ID_CTRL,
                ID_SERVER,
                ID_DEVTYPE,
                ID_READER
            ) VALUES (
                {$new_id_dev},
                {$name_escaped},
                {$id_ctrl},
                NULL,
                {$id_devtype},
                {$id_reader}
            )
        ";
  Kohana::$log->add(Log::ERROR, '271 '.$sql);       
        DB::query(Database::INSERT, $sql)
            ->execute(Database::instance('fb'));
        
        return $new_id_dev;
    }
    
    /**
     * Сохранить новый контроллер с двумя точками прохода
     */
    public function save_controller_with_doors($data)
    {
        try {
            // 1. Сохраняем контроллер
            $controller = $this->save_controller($data);
			
			//echo Debug::vars('289', $controller);exit;
            $id_ctrl = $controller['id_ctrl'];
            $id_dev = $controller['id_dev'];
            $id_devtype = $controller['id_devtype'];
            
            // 2. Создаем точку прохода 1 (Reader 0)
            $door0_name = !empty($data['door0_name']) 
                ? $data['door0_name'] 
                : 'Дверь ' . ($id_dev + 1) . ' (Reader 0)';
            
            $door0_id = $this->save_accesspoint(
                $id_ctrl,
                $door0_name,
                0,  // ID_READER = 0
                $id_devtype // id_devtype
            );
     
            // 3. Создаем точку прохода 2 (Reader 1)
            $door1_name = !empty($data['door1_name']) 
                ? $data['door1_name'] 
                : 'Дверь ' . ($id_dev + 2) . ' (Reader 1)';
            
            $door1_id = $this->save_accesspoint(
                $id_ctrl,
                $door1_name,
                1,  // ID_READER = 1
                $id_devtype // id_devtype
            );
            
            return array(
                'success' => true,
                'id_dev' => $id_dev,
                'id_ctrl' => $id_ctrl,
                'door0_id' => $door0_id,
                'door1_id' => $door1_id
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
	
	/**
 * Получить контроллер по ID_DEV
 */
public function get_controller($id_dev)
{
    $sql = "
        SELECT 
            d.ID_DEV,
            d.NAME,
            d.NETADDR,
            d.ID_CTRL,
            d.ID_SERVER,
            d.ID_DEVTYPE,
            s.NAME as SERVER_NAME,
            dt.NAME as DEVTYPE_NAME
        FROM DEVICE d
        LEFT JOIN SERVER s ON d.ID_SERVER = s.ID_SERVER
        LEFT JOIN DEVTYPE dt ON d.ID_DEVTYPE = dt.ID_DEVTYPE
        WHERE d.ID_DEV = $id_dev AND d.ID_READER IS NULL
    ";
    
    $result = DB::query(Database::SELECT, $sql)
       
        ->execute(Database::instance('fb'))
        ->as_array();
    
    $result = $this->convert_array_encoding($result);
    return isset($result[0]) ? $result[0] : null;
}

/**
 * Получить двери по ID_CTRL
 */
public function get_doors_by_ctrl($id_ctrl)
{
    $sql = "
        SELECT 
            ID_DEV,
            NAME,
            NETADDR,
            ID_READER
        FROM DEVICE
        WHERE ID_CTRL = $id_ctrl AND ID_READER IN (0, 1)
        ORDER BY ID_READER
    ";
    
    $result = DB::query(Database::SELECT, $sql)
   //     ->param(':id_ctrl', $id_ctrl)
        ->execute(Database::instance('fb'))
        ->as_array();
    
    return $this->convert_array_encoding($result);
}

/**
 * Обновить контроллер
 */
public function update_controller($data)
{
    $id_dev = (int)$data['ID_DEV'];
    $name_win1251 = $this->utf8_to_win1251($data['NAME']);
    $netaddr = !empty($data['NETADDR']) ? $this->quote($data['NETADDR']) : 'NULL';
    $id_server = !empty($data['ID_SERVER']) ? (int)$data['ID_SERVER'] : 'NULL';
    $id_devtype = !empty($data['ID_DEVTYPE']) ? (int)$data['ID_DEVTYPE'] : 'NULL';
    
    $sql = "
        UPDATE DEVICE
        SET 
            NAME = {$this->quote($name_win1251)},
            NETADDR = {$netaddr},
            ID_SERVER = {$id_server},
            ID_DEVTYPE = {$id_devtype}
        WHERE ID_DEV = {$id_dev}
    ";
    
    DB::query(Database::UPDATE, $sql)
        ->execute(Database::instance('fb'));
    
    return true;
}

/**
 * Обновить точку прохода (дверь)
 */
public function update_accesspoint($id_dev, $name, $netaddr = null)
{
    $name_win1251 = $this->utf8_to_win1251($name);
    $netaddr_escaped = !empty($netaddr) ? $this->quote($netaddr) : 'NULL';
    
    $sql = "
        UPDATE DEVICE
        SET 
            NAME = {$this->quote($name_win1251)},
            NETADDR = {$netaddr_escaped}
        WHERE ID_DEV = {$id_dev}
    ";
    
    DB::query(Database::UPDATE, $sql)
        ->execute(Database::instance('fb'));
    
    return true;
}

/**
 * Обновить контроллер с двумя точками прохода
 */
public function update_controller_with_doors($data)
{
    try {
        // 1. Обновляем контроллер
        $this->update_controller($data);
        
        // 2. Обновляем точку прохода 1 (Reader 0)
        if (!empty($data['door0_id']) && !empty($data['door0_name'])) {
            $this->update_accesspoint(
                $data['door0_id'],
                $data['door0_name']
            );
        }
        
        // 3. Обновляем точку прохода 2 (Reader 1)
        if (!empty($data['door1_id']) && !empty($data['door1_name'])) {
            $this->update_accesspoint(
                $data['door1_id'],
                $data['door1_name']
            );
        }
        
        return array('success' => true);
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Удалить контроллер и связанные с ним двери
 * 
 * @param int $id_dev ID_DEV контроллера
 * @return array Результат операции
 */
public function delete_controller_with_doors($id_dev)
{
    try {
        // Начинаем транзакцию
        $db = Database::instance('fb');
        $db->begin();
        
        // 1. Получаем ID_CTRL контроллера
        $sql = "SELECT ID_CTRL FROM DEVICE WHERE ID_DEV = {$id_dev} AND ID_READER IS NULL";
        $result = DB::query(Database::SELECT, $sql)
            ->execute($db)
            ->as_array();
        
        if (empty($result)) {
            throw new Exception('Контроллер не найден');
        }
        
        $id_ctrl = $result[0]['ID_CTRL'];
        
        // 2. Удаляем двери (точки прохода) связанные с контроллером
        $sql = "DELETE FROM DEVICE WHERE ID_CTRL = {$id_ctrl} AND ID_READER IN (0, 1)";
        DB::query(Database::DELETE, $sql)
            ->execute($db);
        
        // 3. Удаляем сам контроллер
        $sql = "DELETE FROM DEVICE WHERE ID_DEV = {$id_dev} AND ID_READER IS NULL";
        DB::query(Database::DELETE, $sql)
            ->execute($db);
        
        // Фиксируем транзакцию
        $db->commit();
        
        return array(
            'success' => true,
            'message' => 'Контроллер и связанные двери успешно удалены'
        );
        
    } catch (Exception $e) {
        // Откатываем транзакцию в случае ошибки
        if (isset($db)) {
            $db->rollback();
        }
        
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

		/**
		 * Проверить, можно ли удалить контроллер
		 * (проверка на наличие зависимостей)
		 * 
		 * @param int $id_dev ID_DEV контроллера
		 * @return array Результат проверки
		 */
		public function can_delete_controller($id_dev)
		{
			// Проверяем, есть ли связанные записи в других таблицах
			// Например, если есть связи с расписаниями, правами доступа и т.д.
			
			$dependencies = array();
			
			// Пример проверки (замените на свои таблицы)
			/*
			// Проверка в таблице ACCESS_RIGHTS
			$sql = "SELECT COUNT(*) as count FROM ACCESS_RIGHTS WHERE ID_DEV = {$id_dev}";
			$result = DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->as_array();
			
			if ($result[0]['count'] > 0) {
				$dependencies[] = 'Есть связанные права доступа';
			}
			
			// Проверка в таблице SCHEDULE
			$sql = "SELECT COUNT(*) as count FROM SCHEDULE WHERE ID_DEV = {$id_dev}";
			$result = DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->as_array();
			
			if ($result[0]['count'] > 0) {
				$dependencies[] = 'Есть связанные расписания';
			}
			*/
			
			return array(
				'can_delete' => empty($dependencies),
				'dependencies' => $dependencies
			);
		}

	/* ------------------------------------------------------------------
	 * Группы точек прохода (DEVGROUP) и команды управления точками прохода
	 * ------------------------------------------------------------------ */

	/**
	 * Корневая группа устройств («все устройства»).
	 * Служебная запись таблицы DEVGROUP, в списках групп не показывается.
	 */
	const DEVGROUP_ROOT = 1;

	/**
	 * Оставляет из переданного списка только положительные целые числа.
	 * Ключи результата равны значениям: так список удобно объединять и
	 * подставлять в SQL через implode().
	 *
	 * @param mixed $ids значение из POST (массив, строка или null)
	 * @return array массив вида id => id
	 */
	private function sanitize_ids($ids)
	{
		$result = array();

		foreach ((array) $ids as $id) {
			$id = (int) $id;
			if ($id > 0) $result[$id] = $id;
		}

		return $result;
	}

	/**
	 * Список всех точек прохода (устройств со считывателем) с данными
	 * контроллера и транспортного сервера.
	 *
	 * @return array
	 */
	public function get_access_points()
	{
		$sql = "
			SELECT
				d.ID_DEV,
				d.NAME,
				d.ID_READER,
				c.ID_DEV AS CONTROLLER_ID,
				c.NAME   AS CONTROLLER_NAME,
				s.NAME   AS SERVER_NAME
			FROM DEVICE d
			JOIN DEVICE c ON c.ID_CTRL = d.ID_CTRL AND c.ID_READER IS NULL
			LEFT JOIN SERVER s ON s.ID_SERVER = c.ID_SERVER
			WHERE d.ID_READER IS NOT NULL
			ORDER BY c.NAME, d.ID_READER, d.NAME
		";

		$result = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();

		return $this->convert_array_encoding($result);
	}

	/**
	 * Данные точек прохода по списку ID_DEV.
	 *
	 * @param mixed $ids список ID_DEV (массив или строка)
	 * @return array
	 */
	public function get_access_points_by_ids($ids)
	{
		$ids = $this->sanitize_ids($ids);

		if (empty($ids)) return array();

		$sql = "
			SELECT
				d.ID_DEV,
				d.NAME,
				d.ID_READER,
				c.ID_DEV AS CONTROLLER_ID,
				c.NAME   AS CONTROLLER_NAME,
				s.NAME   AS SERVER_NAME
			FROM DEVICE d
			JOIN DEVICE c ON c.ID_CTRL = d.ID_CTRL AND c.ID_READER IS NULL
			LEFT JOIN SERVER s ON s.ID_SERVER = c.ID_SERVER
			WHERE d.ID_READER IS NOT NULL
				AND d.ID_DEV IN (" . implode(',', $ids) . ")
			ORDER BY c.NAME, d.ID_READER, d.NAME
		";

		$result = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();

		return $this->convert_array_encoding($result);
	}

	/**
	 * Оставляет из списка только существующие точки прохода.
	 * Защита от подстановки в команду управления произвольных ID_DEV
	 * (контроллеров, серверов, несуществующих устройств).
	 *
	 * @param mixed $ids список ID_DEV
	 * @return array массив вида id_dev => id_dev
	 */
	public function filter_access_points($ids)
	{
		$ids = $this->sanitize_ids($ids);
		$result = array();

		if (empty($ids)) return $result;

		$sql = "
			SELECT ID_DEV
			FROM DEVICE
			WHERE ID_READER IS NOT NULL
				AND ID_DEV IN (" . implode(',', $ids) . ")
		";

		$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();

		foreach ($query as $row) {
			$id_dev = (int) Arr::get($row, 'ID_DEV');
			if ($id_dev > 0) $result[$id_dev] = $id_dev;
		}

		return $result;
	}

	/**
	 * Читает из БД структуру групп устройств:
	 *   - $defs     — описания групп (ID_DEVGROUP, NAME, ID_PARENT), кроме корневой;
	 *   - $children — дочерние группы: id_parent => array(id_devgroup);
	 *   - $direct   — точки прохода, входящие в группу напрямую: id_parent => array(id_dev).
	 *
	 * В таблице DEVGROUP строки с ID_DEV IS NULL описывают саму группу,
	 * строки с ID_DEV IS NOT NULL — принадлежность устройства к группе
	 * (при этом ID_PARENT равен идентификатору группы).
	 *
	 * @param array $defs
	 * @param array $children
	 * @param array $direct
	 * @return void
	 */
	private function load_devgroup_tree(&$defs, &$children, &$direct)
	{
		$defs = array();
		$children = array();
		$direct = array();

		$sql = "SELECT ID_DEVGROUP, NAME, ID_PARENT FROM DEVGROUP WHERE ID_DEV IS NULL";
		$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();

		foreach ($query as $row) {
			$id = (int) Arr::get($row, 'ID_DEVGROUP');

			// Корневая группа служебная: её точки прохода — это все точки прохода системы.
			if ($id <= 0 OR $id === self::DEVGROUP_ROOT) continue;

			$defs[$id] = array(
				'id'        => $id,
				'name'      => $this->win1251_to_utf8(Arr::get($row, 'NAME')),
				'id_parent' => (int) Arr::get($row, 'ID_PARENT'),
			);
		}

		$sql = "
			SELECT dg.ID_PARENT AS ID_DEVGROUP, d.ID_DEV
			FROM DEVGROUP dg
			JOIN DEVICE d ON d.ID_DEV = dg.ID_DEV
			WHERE dg.ID_DEV IS NOT NULL
				AND d.ID_READER IS NOT NULL
		";
		$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();

		foreach ($query as $row) {
			$group_id = (int) Arr::get($row, 'ID_DEVGROUP');
			$id_dev = (int) Arr::get($row, 'ID_DEV');

			if ($id_dev <= 0 OR ! isset($defs[$group_id])) continue;

			$direct[$group_id][$id_dev] = $id_dev;
		}

		foreach ($defs as $id => $def) {
			$parent = $def['id_parent'];

			if ($parent === $id) continue;

			// Родитель не является группой (корень или потерянная запись) —
			// показываем группу на верхнем уровне, чтобы она не исчезла из списка.
			if ( ! isset($defs[$parent])) {
				$children[self::DEVGROUP_ROOT][$id] = $id;
				continue;
			}

			$children[$parent][$id] = $id;
		}
	}

	/**
	 * Рекурсивно собирает точки прохода группы с учётом вложенных групп.
	 *
	 * @param int   $group_id идентификатор группы
	 * @param array $children дочерние группы
	 * @param array $direct   точки прохода группы
	 * @param array $memo     кэш результатов: id группы => array(id_dev => id_dev)
	 * @param array $visited  группы в текущей ветке обхода (защита от зацикливания)
	 * @return array массив вида id_dev => id_dev
	 */
	private function resolve_group_access_points($group_id, &$children, &$direct, &$memo, &$visited)
	{
		if (isset($memo[$group_id])) return $memo[$group_id];

		// Иерархия групп может содержать цикл: второй раз в ту же ветку не заходим.
		if (isset($visited[$group_id])) return array();

		$visited[$group_id] = TRUE;

		$result = array();

		if (isset($direct[$group_id])) {
			foreach ($direct[$group_id] as $id_dev) {
				$result[$id_dev] = $id_dev;
			}
		}

		if (isset($children[$group_id])) {
			foreach ($children[$group_id] as $child_id) {
				foreach ($this->resolve_group_access_points($child_id, $children, $direct, $memo, $visited) as $id_dev) {
					$result[$id_dev] = $id_dev;
				}
			}
		}

		unset($visited[$group_id]);

		$memo[$group_id] = $result;

		return $result;
	}

	/**
	 * Точки прохода выбранных групп (включая вложенные группы).
	 * Неизвестные и служебные группы игнорируются.
	 *
	 * @param mixed $group_ids список ID_DEVGROUP
	 * @return array массив вида id_dev => id_dev
	 */
	public function get_group_access_point_ids($group_ids)
	{
		$defs = $children = $direct = array();
		$this->load_devgroup_tree($defs, $children, $direct);

		$memo = array();
		$result = array();

		foreach ($this->sanitize_ids($group_ids) as $group_id) {
			if ( ! isset($defs[$group_id])) continue;

			$visited = array();

			foreach ($this->resolve_group_access_points($group_id, $children, $direct, $memo, $visited) as $id_dev) {
				$result[$id_dev] = $id_dev;
			}
		}

		return $result;
	}

	/**
	 * Дерево групп устройств — все группы, кроме служебной корневой,
	 * в том же составе, что и в разделе «Группы устройств» (devgroup).
	 * Возвращается «плоским» списком в порядке обхода дерева; уровень
	 * вложенности — в поле level, полный путь — в поле path.
	 *
	 * Группы без точек прохода тоже попадают в список: у них total = 0
	 * (в представлении такие группы показываются, но выбрать их нельзя).
	 *
	 * @return array
	 */
	public function get_device_groups()
	{
		$defs = $children = $direct = array();
		$this->load_devgroup_tree($defs, $children, $direct);

		$memo = array();
		$visited = array();
		$groups = array();

		$this->walk_device_groups(self::DEVGROUP_ROOT, 0, '', $defs, $children, $direct, $memo, $visited, $groups);

		return $groups;
	}

	/**
	 * Рекурсивный обход дерева групп для get_device_groups().
	 *
	 * @param int    $parent_id   группа, чьи дочерние элементы обходим
	 * @param int    $level       уровень вложенности
	 * @param string $parent_path путь родителя
	 * @param array  $defs        описания групп
	 * @param array  $children    дочерние группы
	 * @param array  $direct      точки прохода групп
	 * @param array  $memo        кэш resolve_group_access_points()
	 * @param array  $visited     уже выведенные группы
	 * @param array  $result      результат обхода
	 * @return void
	 */
	private function walk_device_groups($parent_id, $level, $parent_path, &$defs, &$children, &$direct, &$memo, &$visited, &$result)
	{
		if ( ! isset($children[$parent_id])) return;

		$ids = array_values($children[$parent_id]);

		// Порядок как в списке групп устройства: по названию.
		usort($ids, function ($a, $b) use ($defs) {
			return strcasecmp($defs[$a]['name'], $defs[$b]['name']);
		});

		foreach ($ids as $id) {
			if (isset($visited[$id])) continue;

			$visited[$id] = TRUE;

			// отдельный $visited: resolve() помечает ветку обхода, а не дерево целиком
			$branch = array();
			$points = $this->resolve_group_access_points($id, $children, $direct, $memo, $branch);

			$path = ($parent_path === '') ? $defs[$id]['name'] : $parent_path . ' / ' . $defs[$id]['name'];

			$result[] = array(
				'id'           => $id,
				'name'         => $defs[$id]['name'],
				'id_parent'    => $defs[$id]['id_parent'],
				'level'        => $level,
				'path'         => $path,
				'direct'       => isset($direct[$id]) ? count($direct[$id]) : 0,
				'total'        => count($points),
				'has_children' => isset($children[$id]),
			);

			$this->walk_device_groups($id, $level + 1, $path, $defs, $children, $direct, $memo, $visited, $result);
		}
	}

}

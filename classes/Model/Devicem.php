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
		
    Kohana::$log->add(Log::DEBUG, '221 '.$sql);         
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
}

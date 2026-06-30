<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Devicem extends Model {
    
    /**
     * Преобразование строк из win1251 в utf-8
     */
    private function win1251_to_utf8($string)
    {
        if (empty($string) || $string === 'NULL' || $string === null) {
            return $string;
        }
        return iconv('Windows-1251', 'UTF-8//IGNORE', $string);
    }
    
    /**
     * Рекурсивное преобразование всех строковых значений в массиве
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
        
        // 🔥 Преобразуем кодировку всех строковых полей
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
}
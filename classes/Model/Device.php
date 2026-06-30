<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Device extends Model_Database {
    
    /**
     * Получить все контроллеры (у которых ID_READER is null)
     * с привязанными к ним дверями
     */
    public function get_controllers_with_doors()
    {
        $query = DB::select(
            'd1.ID_DEV as controller_id',
            'd1.NAME as controller_name',
            'd1.NETADDR as controller_netaddr',
            'd1.ID_CTRL',
            'd2.ID_DEV as door_id',
            'd2.NAME as door_name',
            'd2.NETADDR as door_netaddr',
            'd2.ID_READER',
            's.NAME as server_name',
            'dt.NAME as devtype_name'
        )
        ->from(array('DEVICE', 'd1'))
        ->join(array('DEVICE', 'd2'), 'LEFT')
        ->on('d1.ID_CTRL', '=', 'd2.ID_CTRL')
        ->join(array('SERVER', 's'), 'LEFT')
        ->on('d1.ID_SERVER', '=', 's.ID_SERVER')
        ->join(array('DEVTYPE', 'dt'), 'LEFT')
        ->on('d1.ID_DEVTYPE', '=', 'dt.ID_DEVTYPE')
        ->where('d1.ID_READER', 'IS', NULL)
        ->and_where('d2.ID_READER', 'IN', array(0, 1))
        ->order_by('d1.ID_CTRL', 'ASC')
        ->order_by('d2.ID_READER', 'ASC');
        
        return $query->execute()->as_array();
    }
    
    /**
     * Получить контроллеры с группировкой по ID_CTRL
     */
    public function get_controllers_grouped()
    {
        $results = $this->get_controllers_with_doors();
        $grouped = array();
        
        foreach ($results as $row) {
            $ctrl_id = $row['ID_CTRL'];
            
            if (!isset($grouped[$ctrl_id])) {
                $grouped[$ctrl_id] = array(
                    'controller' => array(
                        'ID_DEV' => $row['controller_id'],
                        'NAME' => $row['controller_name'],
                        'NETADDR' => $row['controller_netaddr'],
                        'ID_CTRL' => $row['ID_CTRL'],
                        'server_name' => $row['server_name'],
                        'devtype_name' => $row['devtype_name']
                    ),
                    'doors' => array()
                );
            }
            
            if ($row['door_id'] !== NULL) {
                $grouped[$ctrl_id]['doors'][] = array(
                    'ID_DEV' => $row['door_id'],
                    'NAME' => $row['door_name'],
                    'NETADDR' => $row['door_netaddr'],
                    'ID_READER' => $row['ID_READER']
                );
            }
        }
        
        return $grouped;
    }
}
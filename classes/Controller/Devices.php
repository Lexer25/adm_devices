<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Devices extends Controller_Template {
    
    public $template = 'template';
    
    public function before()
    {
        parent::before();
    }
    
    public function action_index()
    {
    
	   $view_type = $this->request->query('view');
    
        // Валидация view_type
        $allowed_views = array('table', 'tree', 'matrix');
        if (!in_array($view_type, $allowed_views)) {
            $view_type = 'table';
        }


  
        $model = Model::factory('Devicem');
        $controllers = $model->get_controllers_grouped();
     
        // Подготовка данных для матрицы
        $all_doors = array();
        $all_controllers = array();
        if ($view_type == 'matrix') {
            foreach ($controllers as $ctrl_id => $data) {
                $all_controllers[] = array(
                    'id' => $ctrl_id,
                    'name' => $data['controller']['NAME'],
                    'dev_id' => $data['controller']['ID_DEV']
                );
                foreach ($data['doors'] as $door) {
                    $all_doors[] = array(
                        'id' => $door['ID_DEV'],
                        'name' => $door['NAME'],
                        'ctrl_id' => $ctrl_id,
                        'reader' => $door['ID_READER']
                    );
                }
            }
        }
        
        // ✅ ВСЕГДА загружаем index.php (в нем есть кнопки и подключение нужного представления)
        $view_file = 'device/index';
        
        $content = View::factory($view_file, array(
            'controllers' => $controllers,
            'all_doors' => $all_doors,
            'all_controllers' => $all_controllers,
            'view_type' => $view_type,
            'title' => 'Список контроллеров и дверей'
        ));
        
        $this->template->content = $content;
        $this->template->title = 'Устройства';
    }
}
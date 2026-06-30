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
        
        $allowed_views = array('table', 'tree', 'matrix');
        if (!in_array($view_type, $allowed_views)) {
            $view_type = 'table';
        }
        
        $model = Model::factory('Devicem');
        $controllers = $model->get_controllers_grouped();
        
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
    
    /**
     * Добавление нового контроллера с двумя точками прохода
     */
    public function action_add()
    {
        $model = Model::factory('Devicem');
        $errors = array();
        $success = false;
        
        $servers = $model->get_servers();
        $devtypes = $model->get_devtypes();
        
        if ($this->request->method() == 'POST') {
            $data = array(
                'NAME' => $this->request->post('name'),
                'NETADDR' => $this->request->post('netaddr'),
                'ID_SERVER' => $this->request->post('id_server'),
                'ID_DEVTYPE' => $this->request->post('id_devtype'),
                'door0_name' => $this->request->post('door0_name'),
                'door1_name' => $this->request->post('door1_name'),
            );
            
            // Валидация
            if (empty($data['NAME'])) {
                $errors['name'] = 'Название контроллера обязательно';
            }
            
            if (empty($errors)) {
                try {
                    $result = $model->save_controller_with_doors($data);
                    
                    if ($result['success']) {
                        $success = true;
                        $this->redirect('devices?success=added');
                    } else {
                        $errors['general'] = $result['error'];
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Ошибка: ' . $e->getMessage();
                }
            }
        }
        
        $content = View::factory('device/add', array(
            'servers' => $servers,
            'devtypes' => $devtypes,
            'errors' => $errors,
            'success' => $success,
            'post_data' => $this->request->post(),
            'title' => 'Добавление контроллера'
        ));
        
        $this->template->content = $content;
        $this->template->title = 'Добавление контроллера';
    }
}

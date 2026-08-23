<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Devices extends Controller_Template {
    
    public $template = 'template';
    
    public function before()
    {
        parent::before();
    }
    
    public function action_index()
    {
     //определяю результат обновления   
		$success_message = '';
    if ($this->request->query('success') == 'updated') {
        $success_message = 'Контроллер успешно обновлен!';
    } elseif ($this->request->query('success') == 'added') {
        $success_message = 'Контроллер успешно добавлен!';
    } elseif ($this->request->query('success') == 'deleted') {
        $success_message = 'Контроллер успешно удален!';
    }
	
// Добавляем обработку ошибок
    $error_message = '';
    if ($this->request->query('error')) {
        $error_message = 'Ошибка: ' . htmlspecialchars($this->request->query('error'));
    }

	
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
                    'dev_id' => $data['controller']['ID_DEV'],
                    'server_name' => $data['controller']['server_name']
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
        
      //  $view_file = 'device/index';
        
        $content = View::factory('device/index', array(
            'controllers' => $controllers,
            'all_doors' => $all_doors,
            'all_controllers' => $all_controllers,
            'view_type' => $view_type,
            'title' => 'Список контроллеров и дверей',
			'success_message' => $success_message // Добавляем эту переменную
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
	
	/**
	 * Редактирование контроллера
	 */
	public function action_edit()
	{
		$id = (int) $this->request->param('id', 0);
		
		if ($id <= 0) {
			$this->redirect('devices');
		}
		
		$model = Model::factory('Devicem');
		$errors = array();
		$success = false;
		
		// Получаем данные контроллера
		$controller = $model->get_controller($id);
		
		if (!$controller) {
			$this->redirect('devices');
		}
		
		// Получаем двери для этого контроллера
		$doors = $model->get_doors_by_ctrl($controller['ID_CTRL']);
		
		$servers = $model->get_servers();
		$devtypes = $model->get_devtypes();
		
		if ($this->request->method() == 'POST') {
			$data = array(
				'ID_DEV' => $id,
				'NAME' => $this->request->post('name'),
				'NETADDR' => $this->request->post('netaddr'),
				'ID_SERVER' => $this->request->post('id_server'),
				'ID_DEVTYPE' => $this->request->post('id_devtype'),
				'door0_name' => $this->request->post('door0_name'),
				'door1_name' => $this->request->post('door1_name'),
				'door0_id' => $this->request->post('door0_id'),
				'door1_id' => $this->request->post('door1_id'),
			);
			
			// Валидация
			if (empty($data['NAME'])) {
				$errors['name'] = 'Название контроллера обязательно';
			}
			//echo Debug::vars('159', $model->update_controller_with_doors($data));exit;
			//echo Debug::vars('159', $model->update_controller_with_doors($data));exit;
			if (empty($errors)) {
				try {
					$result = $model->update_controller_with_doors($data);
					
					if ($result['success']) {
						$success = true;
					//	HTTP::redirect('devices?success=updated');
					//	$this->redirect('devices?success=updated');
					} else {
						$errors['general'] = $result['error'];
					}
				} catch (Exception $e) {
					$errors['general'] = 'Ошибка: ' . $e->getMessage();
				}
			}
		}
		
		if ($success) $this->redirect('devices?success=updated');
		
		// echo Debug::vars('179', $errors);//exit;
		// echo Debug::vars('179', $errors);//exit;
		// echo Debug::vars('179', $errors);//exit;
		// echo Debug::vars('180', $success);//exit;
		$content = View::factory('device/edit', array(
			'controller' => $controller,
			'doors' => $doors,
			'servers' => $servers,
			'devtypes' => $devtypes,
			'errors' => $errors,
			'success' => $success,
			'post_data' => $this->request->post(),
			'title' => 'Редактирование контроллера'
		));
		
		$this->template->content = $content;
		$this->template->title = 'Редактирование контроллера';
	}

	/**
	 * Удаление контроллера и связанных с ним дверей
	 */
	public function action_delete()
	{
		
		// Проверка прав администратора
		if (!$this->is_admin) {
			// Если не admin - показываем ошибку и редирект
			$this->redirect('devices');
		}
		
		$id = (int) $this->request->param('id', 0);
		
		if ($id <= 0) {
			$this->redirect('devices');
		}
		
		$model = Model::factory('Devicem');
		
		// Получаем данные контроллера для сообщения
		$controller = $model->get_controller($id);
		
		if (!$controller) {
			$this->redirect('devices');
		}
		
		try {
			// Удаляем контроллер и связанные двери
			$result = $model->delete_controller_with_doors($id);
			
			if ($result['success']) {
				// Успешное удаление
				$this->redirect('devices?success=deleted');
			} else {
				// Ошибка при удалении
				$this->redirect('devices?error=' . urlencode($result['error']));
			}
		} catch (Exception $e) {
			// Исключение при удалении
			$this->redirect('devices?error=' . urlencode($e->getMessage()));
		}
	}


}

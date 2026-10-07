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
	 * Команды управления точкой прохода.
	 * Ключ — значение, которое передаётся в Model_Device::unlock_door_arr().
	 */
	protected function door_commands()
	{
		return array(
			'opendoor'       => __('door_command_opendoor'),
			'lockdoor'       => __('door_command_lockdoor'),
			'opendooralways' => __('door_command_opendooralways'),
			'unlockdoor'     => __('door_command_unlockdoor'),
		);
	}

	/**
	 * P0. Приведение списка точек прохода, полученного из POST, к массиву
	 * уникальных положительных целых чисел. Ключи массива равны ID_DEV:
	 * так их ожидают Model_Device::unlock_door_arr() и модель Devicem.
	 *
	 * @param mixed $value значение из POST (массив, строка или null)
	 * @return array массив вида id_dev => id_dev
	 */
	protected function sanitize_id_dev($value)
	{
		if ( ! is_array($value)) {
			$value = ($value === NULL OR $value === '') ? array() : array($value);
		}

		$result = array();

		foreach ($value as $id) {
			$id = (int) $id;
			if ($id > 0) $result[$id] = $id;
		}

		return $result;
	}

	/**
	 * Разбор набора точек прохода, заданного строкой: «12, 15 17;18».
	 *
	 * @param string $text значение поля id_dev_text
	 * @return array массив вида id_dev => id_dev
	 */
	protected function parse_id_dev_text($text)
	{
		$result = array();

		foreach (preg_split('/[^0-9]+/', (string) $text) as $id) {
			$id = (int) $id;
			if ($id > 0) $result[$id] = $id;
		}

		return $result;
	}

	/**
	 * Управление группами точек прохода.
	 *
	 * GET  — форма: команда, группы устройств и набор точек прохода (ID_DEV).
	 * POST — выполнение команды для выбранных точек прохода.
	 *
	 * Команды: открыть 1 раз, закрыть навсегда, открыть навсегда, разблокировать.
	 */
	public function action_groups()
	{
		// P0. Управление точками прохода доступно только администратору.
		if ( ! $this->is_admin) {
			$user = (class_exists('Auth') AND Auth::instance()->logged_in()) ? Auth::instance()->get_user() : 'guest';

			Log::instance()->add(Log::NOTICE, 'Отказ в управлении группами точек прохода: недостаточно прав, user='.$user);

			$this->redirect('errorpage?err='.urlencode(__('Управление точками прохода доступно только администраторам')));
		}

		$model = Model::factory('Devicem');
		$commands = $this->door_commands();
		$groups = $model->get_device_groups();

		$errors = array();
		$selected_doors = array();
		$selected_groups = array();
		$selected_command = 'opendoor';
		$id_dev_text = '';

		if ($this->request->method() == 'POST') {
			$post = $this->request->post();

			$selected_command = (string) Arr::get($post, 'command');
			$id_dev_text = (string) Arr::get($post, 'id_dev_text');
			$selected_groups = $this->sanitize_id_dev(Arr::get($post, 'group_ids'));
			$selected_doors = $this->sanitize_id_dev(Arr::get($post, 'id_dev'));

			// Набор точек прохода можно задать строкой (например, «12, 15, 17»).
			foreach ($this->parse_id_dev_text($id_dev_text) as $id_dev) {
				$selected_doors[$id_dev] = $id_dev;
			}

			// P0. Защита от CSRF: скрытое поле csrf_token выводится в форме.
			if ( ! Security::check((string) Arr::get($post, 'csrf_token'))) {
				Log::instance()->add(Log::NOTICE, 'Отказ в управлении группами точек прохода: неверный CSRF-токен');
				$errors[] = __('Неверный или устаревший CSRF-токен. Обновите страницу и повторите действие.');
			}

			if ( ! isset($commands[$selected_command])) {
				$errors[] = __('Неизвестная команда управления точкой прохода.');
			}

			if (empty($selected_doors) AND empty($selected_groups)) {
				$errors[] = __('Не выбрано ни одной точки прохода: отметьте точки прохода, группы устройств или укажите набор ID_DEV.');
			}

			if (empty($errors)) {
				// Группы раскрываются в точки прохода с учётом вложенных групп.
				foreach ($model->get_group_access_point_ids($selected_groups) as $id_dev) {
					$selected_doors[$id_dev] = $id_dev;
				}

				// Выполнять команду можно только для существующих точек прохода:
				// отсекаем контроллеры, серверы и несуществующие ID_DEV.
				$id_dev_list = $model->filter_access_points($selected_doors);

				if (empty($id_dev_list)) {
					$errors[] = __('Среди выбранных устройств нет ни одной точки прохода.');
				} else {
					$log = Model::factory('Device')->unlock_door_arr($id_dev_list, $selected_command);

					// Пауза, чтобы контроллеры успели применить команду, и опрос
					// нового состояния точки прохода (как в разделе «Контроллеры»).
					sleep(2);

					$controllers = array();

					foreach ($id_dev_list as $id_dev) {
						$controller_id = Arr::get(Model::factory('Device')->get_device_info($id_dev), 'device_id');

						if ($controller_id AND ! isset($controllers[$controller_id])) {
							$controllers[$controller_id] = $controller_id;
							Model::factory('Device')->getStatForOneController($controller_id);
						}
					}

					$user = (class_exists('Auth') AND Auth::instance()->logged_in()) ? Auth::instance()->get_user() : 'guest';

					Log::instance()->add(Log::NOTICE, 'Команда управления точками прохода: команда='.$selected_command.', пользователь='.$user.', id_dev='.implode(',', $id_dev_list));

					$group_names = array();

					foreach ($groups as $group) {
						if (isset($selected_groups[$group['id']])) {
							$group_names[] = $group['path'];
						}
					}

					// Результат показываем после редиректа (PRG), чтобы обновление
					// страницы не выполнило команду повторно.
					Session::instance()->set('devices_group_result', array(
						'command'       => $selected_command,
						'command_label' => $commands[$selected_command],
						'log'           => $log,
						'doors'         => $model->get_access_points_by_ids($id_dev_list),
						'groups'        => $group_names,
						'time'          => date('d.m.Y H:i:s'),
					));

					$this->redirect('devices/groups');
				}
			}
		}

		$result = Session::instance()->get_once('devices_group_result');

		$content = View::factory('device/groups', array(
			'title'            => 'Управление группами точек прохода',
			'commands'         => $commands,
			'groups'           => $groups,
			'access_points'    => $model->get_access_points(),
			'errors'           => $errors,
			'result'           => $result,
			'selected_doors'   => $selected_doors,
			'selected_groups'  => $selected_groups,
			'selected_command' => $selected_command,
			'id_dev_text'      => $id_dev_text,
			'csrf_token'       => Security::token(),
		));

		$this->template->content = $content;
		$this->template->title = 'Управление группами точек прохода';
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

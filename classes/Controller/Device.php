<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Device extends Controller_Template {
    
    public $template = 'device/index';
	
		public function before()
	{
			
			parent::before();
			$session = Session::instance();
			
	}
	
	
    
    public function action_index()
    {
        $model = new Model_Device();
        $controllers = $model->get_controllers_grouped();
        
     
		
		
		$content = View::factory('event', array(
			'controllers' => $controllers
			
			));
        $this->template->content = $content;
		
		
    }
}

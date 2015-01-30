<?php

class Forum extends CI_Controller {
	function __construct()
	{
		parent::__construct();
		$this->load->helper('cookie');
		$this->load->model('user_model', 'user', TRUE);

	}

	function index()
	{
		if ($this->input->cookie('valid_user'))
		{
			$this->email = $this->input->cookie('valid_user', false);
			$user_image = $this->user->get_user_image($this->email);
			$this->load->view('templates/header', array('title' => 'JoulePerSecond Forum - '.$this->config->item('site_name'), 'user_image' => $user_image));
		}else{
			$this->load->view('templates/header', array('title' => 'JoulePerSecond Forum - '.$this->config->item('site_name')));
		}
		
		

		$this->load->view('forum');

		$this->load->view('templates/footer');		
	}

}
?>
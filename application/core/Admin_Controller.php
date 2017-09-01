<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Controller extends CI_Controller {

	protected $pk;

	protected $table;

	public function __construct()
	{
		parent::__construct();
		
		$this->auth->restrict();

		if (!in_array($this->uri->segment(1), $this->session->userdata('user_privileges'))) {
			redirect(base_url());
		}
	}

	public function delete()
	{
		$response = [];
		$response['action'] = 'delete';
		$response['type'] = 'warning';
		$response['message'] = 'not_selected';
		$ids = explode(',', $this->input->post($this->pk));

		if (count($ids) > 0) {
			if ($this->model->delete($ids, $this->table)) {
				$response = [
					'type' => 'success',
					'message' => 'deleted',
					'id' => $ids
				];
			} else {
				$response = [
					'type' => 'error',
					'message' => 'not_deleted'
				];
			}
		}

		$this->output
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($response, JSON_PRETTY_PRINT))
			->_display();
		exit;
	}

	public function restore()
	{
		$response = [];
		$response['action'] = 'restore';
		$response['type'] = 'warning';
		$response['message'] = 'not_selected';
		$ids = explode(',', $this->input->post($this->pk));

		if (count($ids) > 0) {
			if ($this->model->restore($ids, $this->table)) {
				$response = [
					'action' => 'restore',
					'type' => 'success',
					'message' => 'restored',
					'id' => $ids
				];
			} else {
				$response = [
					'action' => 'restore',
					'type' => 'error',
					'message' => 'not_deleted'
				];
			}
		}

		$this->output
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($response, JSON_PRETTY_PRINT))
			->_display();
		exit;
	}

	public function email_check($str, $id)
	{
		$exist = false;
		if ($this->model->is_email_exist('email', $str, 'students', $id)) {
			$exist = true;
		}
		if ($this->model->is_email_exist('email', $str, 'employees', $id)) {
			$exist = true;
		}
		if ($this->model->is_email_exist('user_email', $str, 'users', $id)) {
			$exist = true;
		}
		if ($exist) {
			$this->form_validation->set_message('email_check', 'Email sudah digunakan. Silahkan gunakan email lain');
			return false;
		}
		return true;
	}

	protected function post_image_upload_handler($id)
	{
		$response = [];
		$config['upload_path'] = './media/images/';
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['max_size']  = 0;
		$config['encrypt_name'] = true;
		
		$this->load->library('upload', $config);
		
		if ( ! $this->upload->do_upload('post_image')){
			$response['type'] = 'error';
			$response['message'] = $this->upload->display_errors();
		} else {
			$file = $this->upload->data();
			@chmod(FCPATH.'media/images'.$file['file_name'], 0777);
			$this->post_image_resize_handler(FCPATH.'media/images/', $file['file_name']);
			$response['type'] = 'success';
			$response['file_name'] = $file['file_name'];

			if ($id > 0) {
				$query = $this->model->RowObject($this->table, $this->pk, $id);

				@chmod(FCPATH.'media/posts/thumbnail/'.$query->post_image, 0777);
				@chmod(FCPATH.'media/posts/medium/'.$query->post_image, 0777);
				@chmod(FCPATH.'media/posts/large/'.$query->post_image, 0777);

				@unlink(FCPATH.'media/posts/thumbnail/'.$query->post_image);
				@unlink(FCPATH.'media/posts/medium/'.$query->post_image);
				@unlink(FCPATH.'media/posts/large/'.$query->post_image);
			}
		}

		return $response;
	}

	private function post_image_resize_handler($source, $file_name)
	{
		$this->load->library('image_lib');

		$thumbnail['image_library'] = 'gd2';
		$thumbnail['source_image'] = $source .'/'. $file_name;
		$thumbnail['new_image'] = './media/posts/thumbnail/'.$file_name;
		$thumbnail['maintain_ratio'] = false;
		$thumbnail['width'] = (int) $this->session->userdata('post_image_thumbnail_width');
		$thumbnail['height'] = (int) $this->session->userdata('post_image_thumbnail_height');
		$this->image_lib->initialize($thumbnail);
		$this->image_lib->resize();
		$this->image_lib->clear();

		$medium['image_library'] = 'gd2';
		$medium['source_image'] = $source .'/'. $file_name;
		$medium['new_image'] = './media/posts/medium/'. $file_name;
		$medium['maintain_ratio'] = false;
		$medium['width'] = (int) $this->session->userdata('post_image_medium_width');
		$medium['height'] = (int) $this->session->userdata('post_image_medium_height');
		$this->image_lib->initialize($medium);
		$this->image_lib->resize();
		$this->image_lib->clear();

		$large['image_library'] = 'gd2';
		$large['source_image'] = $source .'/'. $file_name;
		$large['new_image'] = './media/posts/large/'. $file_name;
		$large['maintain_ratio'] = false;
		$large['width'] = (int) $this->session->userdata('post_image_large_width');
		$large['height'] = (int) $this->session->userdata('post_image_large_height');
		$this->image_lib->initialize($large);
		$this->image_lib->resize();
		$this->image_lib->clear();

		@unlink($source .'/'. $file_name);
	}

	public function tinymce_upload_handler() 
	{
		$config['upload_path'] = './media/posts/';
		$config['allowed_types'] = 'jpg|png|jpeg';
		$config['max_size'] = 0;
		$this->load->library('upload', $config);
		if ( ! $this->upload->do_upload('file')) {
			$this->output->set_header('HTTP/1.0 500 Server Error');
			exit;
		} else {
			$file = $this->upload->data();
			$this->output
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode(['location' => base_url().'media/posts/'.$file['file_name']]))
				->_display();
			exit;
		}
	}
}

/* End of file Admin_Controller.php */
/* Location: ./application/core/Admin_Controller.php */
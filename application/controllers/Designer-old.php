<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designer extends GH_Controller {

	private $data;


	public function __construct() {
		parent::__construct();

		$this->data['menu_class'] = "designer_menu";
		$this->data['title'] = "Designer";
		$this->data['sidebar_file'] = "designer/designer_sidebar";
	}

	function _remap($method,$param){

		if (method_exists($this,$method)) {
			if (!empty($param)) {
				$this->$method($param[0]);
			}else{
				$this->$method();
			}
			
		} else {
			$this->index($method);
		}


	}

	/*STATUS
	* 0: Not activated
	* 1: Activated
	* 2: Waiting for Approval of location manager work
	* 3: Approved Location Manager work
	* 4: Waiting for approval of designer work
	* 5: Approved Designer work
	* 6: Uncompleted for location Manager
	* 7: Uncompleted for designer
	*/

	public function index($status)
	{
		$where_in = array();

		if ($status == 5) {
			
			$where_in['a.status'] = array(6,5,3,2);

		}else{

			$where_in['a.status'] = array(1,4,7);
		}
		$where['a.hologram_type'] = 2;
		$join['locations l'] = "a.location_id=l.location_id";

		$this->data['tasks'] = $this->crud_model->get_data('advertisements a',$where,'',$join,'','','',$where_in);

		$this->data['status'] = $status;
		$this->load->view('common/header',$this->data);
		$this->load->view('common/sidebar',$this->data);
		$this->load->view('designer/designer');
		$this->load->view('common/footer');
	}

	public function tasks_assigned($advert_id){
		
		$where['a.advert_id'] = $advert_id;
		$join['locations l'] = "a.location_id=l.location_id";

		$this->data['task'] = $this->crud_model->get_data('advertisements a',$where,true,$join);

		$this->data['delivery_file'] = $this->crud_model->get_data('proofs a',$where,true);

		$this->load->view('common/header',$this->data);
		$this->load->view('common/sidebar',$this->data);
		$this->load->view('designer/tasks_assigned');
		$this->load->view('common/footer');
	}

	public function deliver_task($advert_id){

		$form_data = $this->input->post();

		if (!empty($form_data)) {

			if ($_FILES['proof_file']['error'] == 4)
			{

				$this->form_validation->set_rules('proof_file', '*File', 'required', array(
					'required' => '%s Missing'
				));

				$result = $this->form_validation->run();

			}else{

				$result = true;
			}


			if ($result) {

				$proof_file = $this->crud_model->upload_file($_FILES['proof_file'],'proof_file',PROOF_UPLOAD_PATH);

				if ($proof_file) {

					$where_get_proof['advert_id'] = $form_data['advert_id'];
					$where_get_proof['proof_type'] = 'designer';

					$proofs = $this->crud_model->get_data("proofs",$where_get_proof,true);

					if (!empty($proofs)) {
						
						$form_data['proof_file'] = $proof_file;

						$where_update_proof['proof_id'] = $proofs->proof_id;

						$proof = $this->crud_model->update_data("proofs",$where_update_proof,$form_data);
					}else{

						$form_data['proof_file'] = $proof_file;
						$form_data['user_id'] = get_user_id();
						$form_data['proof_type'] = "designer";

						$proof = $this->crud_model->add_data("proofs",$form_data);
					}
					

					if ($proof) {
						$where['advert_id'] = $form_data['advert_id'];
						$advert = $this->crud_model->update_data('advertisements',$where,array("status"=>4));

						if ($advert) {
							$this->session->set_flashdata("success_msg","Task Delivered");

						}else{
							$this->session->set_flashdata("error_msg","Task Not Delivered");
						}

						redirect($_SERVER['HTTP_REFERER']);
					}
				}
			}
		}
		

		$this->data['advert_id'] = $advert_id;


		$this->load->view('common/header',$this->data);
		$this->load->view('common/sidebar',$this->data);
		$this->load->view('location_manager/deliver_task');
		$this->load->view('common/footer');
	}

	public function view_deliveries(){


		$join['advertisements a'] = "a.advert_id=p.advert_id";
		$join['locations l'] = "a.location_id=l.location_id";

		$where['p.proof_type'] = "designer";

		$this->data['proofs'] = $this->crud_model->get_data("proofs p",$where,'',$join,'','*,p.status as proof_status');


		$this->load->view('common/header',$this->data);
		$this->load->view('common/sidebar',$this->data);
		$this->load->view('designer/view_deliveries');
		$this->load->view('common/footer');
	}



}


?>
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
    
    private $module = 'home';
    
    public function __construct() {
        parent::__construct();
        $this->load->model('administration/user_model');
    }//end of function

    //load login screen
    public function index() {
        $this->load->view('home/login_view');
    }

    public function login() {
        //var_dump($_POST);
        
        $user_id    = trim($this->input->post('txt_user_id'));
        $password   = trim($this->input->post('txt_password'));

        $fields   = array('nic',"CONCAT(first_name,'-', user_role) AS user_name",'password','user_role','user_status');
        $criteria = array('nic' => $user_id);
        $user	  = $this->user_model->get_user($fields,$criteria);

        if($user && crypt($password,$user['password']) == $user['password'] && $user['user_status']) { //valid user

            $last_login = get_cur_date_time();
            $last_login_12hour = (new DateTime($last_login))->format('Y-m-d g:i:sa');
            
            //update user login time
            $criteria = array('nic' => $user_id);
            $data_set = array('last_login' => $last_login);
            $this->user_model->update_user($data_set,$criteria);
            
            $this->session->set_userdata('is_login',TRUE);
            $this->session->set_userdata('user_id',$user['nic']);
            $this->session->set_userdata('user_name',$user['user_name']);
            $this->session->set_userdata('user_role',$user['user_role']);
            $this->session->set_userdata('user_status',$user['user_status']);
            $this->session->set_userdata('last_login',$last_login_12hour);
            
            //var_dump($user);
            //redirecting acording to the user role
            /*if($user['user_role'] == 'super-admin') {
                redirect('administration'); 
            } elseif($user['user_role'] == 'admin') {
                redirect('administration'); 
            } elseif($user['user_role'] == 'manager') {
                redirect('stock');
            } else {
                redirect('sales');
            }*/

            redirect('dashboard');

        } else { //invalid user
            $this->session->set_flashdata('login_error','Invalid User Name or Password');
            redirect('home');
        }

    }//end of function

    public function logout() {
        //destroy the current session
        $this->session->sess_destroy();
        //redirct
        redirect('home');
    }//end of function	

    //load user profile 
    public function profile($user_id) {
        $this->application->level4();
        $data = $this->application->set_data();
        $data['module'] = $this->module;

        //set notification if any
        if($this->session->flashdata('is_notification')) {
            $data['is_notification']            = $this->session->flashdata('is_notification');
            $data['notification_type']          = $this->session->flashdata('notification_type');
            $data['notification_description']   = $this->session->flashdata('notification_description');
        }

        //set data
        $fields = array(
            'nic AS user_id',
            "CONCAT_WS(' ',first_name, last_name) AS user_name",
            'user_role', 'designation', 'email'
        );
        $criteria     = array("nic" => $user_id);
        $data['user'] = $this->user_model->get_user($fields, $criteria);

        //var_dump($data);
        $this->load->view('home/edit_profile_view',$data);

    }//end of function

//  //actions
    public function edit_profile($user_id) {
        $this->application->level4();
        //var_dump($_POST);
        
        //server side form validation
        $server_validation = $this->validate_edit_profile($user_id);
        
        if($server_validation) {

            //update password
            $criteria = array("nic" => $user_id);
            $data_set['password'] = crypt($this->input->post('psw_new_password'));
            $query_status = $this->user_model->update_user($data_set,$criteria);

            if($query_status) {
                //write system log
                $this->application->write_log('user','Password changed.');
                
                //sending mail
                $this->load->library('mail');
                $fields = array('email', 'first_name', 'last_name');
                $user   = $this->user_model->get_user($fields,$criteria); 
                $data_set_mail = array(
                    'recipient_address' => $user['email'],
                    'recipient_name'    => $user['first_name'] . ' ' . $user['last_name'],
                    'subject'	    => 'Password Changing',
                    'body'		    => 'Hello ' . $user['first_name'] . ',<br/><p>You have chnaged your password. Please contact the administrator if it seems an unauthorized activity.</p>Best Regards,<br/>Administration,<br/>Wisdom Books'
                );
                $this->mail->send($data_set_mail);


                //prepare notifications
                $notification = array(
                    'is_notification'           => TRUE,
                    'notification_type'         => 'success',
                    'notification_description'  => 'The password is changed successfully.'
                );
            } else {
                $notification = array(
                    'is_notification'           => TRUE,
                    'notification_type'         => 'error',
                    'notification_description'  => 'Error terminated changing the password.'
                );	
            }
            $this->session->set_flashdata($notification);
            //redirect
            redirect('home/profile/' . $user_id); 
        
        } else { //invalid
            
            $data = $this->application->set_data();
            $data['module'] = $this->module;

            //set data
            $fields = array(
                'nic AS user_id',
                "CONCAT_WS(' ',first_name, last_name) AS user_name",
                'user_role', 'designation', 'email'
            );
            $criteria     = array("nic" => $user_id);
            $data['user'] = $this->user_model->get_user($fields, $criteria);
            $this->load->view('home/edit_profile_view',$data);
        }
       
    }//end of function
    
    //supportive functions
    private function validate_edit_profile($user_id) {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('psw_current_password', 'current password', "trim|required|callback_validate_current_password[$user_id]");
        $this->form_validation->set_rules('psw_new_password','','');
        return $this->form_validation->run();
    }//end of function
    
    //callback function for validate_edit_user
    public function validate_current_password($str,$user_id) {
	$password = $this->input->post('psw_current_password');
        $fields   = array('password');
        $criteria = array('nic' => $user_id);
        $user	  = $this->user_model->get_user($fields,$criteria);
        
        if(crypt($password,$user['password']) != $user['password']) { //valid
           $this->form_validation->set_message("validate_current_password", "The %s is invaild.");
           return FALSE;
           
        } else { //invalid
            return TRUE;
        }
        
    }//end of function
	
}//end of class

//end of file
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Administration extends CI_Controller {
    
    private $module = 'administration';
    
    public function __construct() {
        parent::__construct();
        $this->load->model('administration/user_model');
        $this->load->model('administration/log_model');
        $this->load->library('mail');
        $this->application->level1();
    }
//----------------- Users Section -------------------
    //views
    public function index($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	

        //set criteria if any
        if($this->session->userdata('users_is_search')) {
            $key   = $this->session->userdata('users_search_key');
            $value = $this->session->userdata('users_search_value');

            if($key == 'first_name' || $key == 'last_name') { //user name				
                $key   = "$key RLIKE";
                $value = "^$value";
            } else if($key == 'user_role') { //user role
                switch ($value){
                    case '1' : $value = 'operator' ; break; 
                    case '2' : $value = 'manager' ; break; 
                    case '3' : $value = 'admin' ; break;
                    default  : $value = FALSE;
                }
            }
            $criteria = array($key => $value);
        }
        $criteria['user_role <>'] = 'super-admin';

        //set notification if any
        if($this->session->flashdata('is_notification')) {
            $data['is_notification']		= $this->session->flashdata('is_notification');
            $data['notification_type']		= $this->session->flashdata('notification_type');
            $data['notification_description']   = $this->session->flashdata('notification_description');
        }

        //set pagination
        $total_rows = $this->user_model->get_total_rows($criteria) ;
        $this->application->set_pagination($total_rows,site_url('administration/index'));

        //set data
        $fields = array(
            "nic AS user_id","user_role","user_status","designation", "email",
            "CONCAT_WS(' ',first_name,last_name) AS full_name",
            "IF( user_status,'active', 'inactive' ) AS status",
            "IFNULL(DATE_FORMAT(last_login,'%Y-%m-%d %r'),'0000-00-00 00:00:00') AS last_login",
            "IF('{$data['user_role']}' = 'admin' AND user_role = 'admin', FALSE, TRUE) AS is_editable"
        );
        $limit      = $this->config->item('rows_per_page');
        $order_by   = "nic ASC";
        $data['users'] = $this->user_model->get_users($fields,$criteria,$page,$limit,$order_by);

        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('administration/users_view',$data);

    }//end of function

    public function add() {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        $this->load->view('administration/add_user_view',$data);

    }//end of function
    
    public function edit($user_id) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //retrieve user data
        $criteria   = array('nic' => $user_id);
        $fields     = array(
            'nic AS user_id','user_role','designation','email',
            "CONCAT_WS(' ',first_name,last_name) AS user_name"
        );
        $data['user'] = $this->user_model->get_user($fields,$criteria);
        //set editable values
        $data['user']['new_email']       = $data['user']['email'];
        $data['user']['new_designation'] = $data['user']['designation'];
        $data['user']['new_user_role']   = $data['user']['user_role'];
        
        //var_dump($data);
        $this->load->view('administration/edit_user_view',$data);

    }//end of function
    
    //actions
    public function add_user() {
        //var_dump($_POST);
        
        //server side form validation
        $server_validation = $this->validate_add_user();

        if($server_validation) {
            
            //create default password
            $password = '';
            $psw_text = '';
            if($this->input->post("lst_user_role") == 'admin') {
                $psw_text = $this->config->item('psw_admin');
                $password = crypt($this->config->item('psw_admin'));
            } else {
               $psw_text = $this->config->item('psw_common');  
               $password = crypt($this->config->item('psw_common')); 
            }

            //prepare user data
            $first_name = ucwords($this->input->post('txt_first_name'));
            $last_name  = ucwords($this->input->post('txt_last_name'));
            $email      = strtolower($this->input->post('txt_email'));
                
            $data_set = array(
                "nic"        => strtoupper($this->input->post('txt_nic')),
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'email'      => $email,
                'designation'=> ucwords($this->input->post('txt_designation')),
                'password'   => $password,
                'user_role'  => $this->input->post('lst_user_role')
            );

            //insert user get user id of new user   
            $query_status = $this->user_model->add_user($data_set);
            //var_dump($query_status);
            if($query_status) {

                //insert system log entry
                $description = "Add user: {$this->input->post('txt_nic')}/{$this->input->post('txt_designation')}/{$this->input->post('lst_user_role')}.";
                $this->application->write_log('admin', $description);
                
                
                //sending mail
                $data_set_mail = array(
                    'recipient_address' => $email,
                    'recipient_name'    => $first_name. ' ' . $last_name,
                    'subject'		=> 'New Account Details',
                    'body'		=> 'Hello ' . $first_name . ',<br/><p>Your Account has been created. Your login details are,<br/><b>User Name: Your NIC <br/> Password:' . $psw_text. '</b></p><p>This is a default password, so you are strongly advised to <b>replace it with your own immediately after the first login.</b></p>Best Regards,<br/>Administration,<br/>Wisdom Books'
                );
                $this->mail->send($data_set_mail);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => 'The new user is added successfully.'
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new user.'
                );
            }

            $this->session->set_flashdata($notification);
            redirect('administration');
            
        } else {
            $data 		= $this->application->set_data();
            $data['module']     = $this->module;
            $this->load->view('administration/add_user_view',$data);
        }
        
    }//end of function

    public function edit_user($user_id) {
        //var_dump($_POST);
        
        //server side form validation
        $server_validation = $this->validate_edit_user($user_id);

        if($server_validation) {
            
            //prepare the data & update
            $data_set = array(
                'email'       => strtolower($this->input->post('txt_email')),
                'designation' => ucwords($this->input->post('txt_designation')),
                'user_role'   => $this->input->post('lst_user_role')
            );
            $criteria     = array('nic' => $user_id);
            $query_status = $this->user_model->update_user($data_set,$criteria);

            //prepare notifications and redirect
            if($query_status) {
                //write system log
                $description = "Edit user: {$user_id}/{$this->input->post('txt_designation')}/{$this->input->post('lst_user_role')}.";
                $this->application->write_log('admin', $description);

                //sending mail
                $fields = array('email', 'first_name', 'last_name');
                $user   = $this->user_model->get_user($fields,$criteria); 
                $data_set_mail = array(
                    'recipient_address' => $user['email'],
                    'recipient_name'    => $user['first_name'] . ' ' . $user['last_name'],
                    'subject'	        => 'Account Settings',
                    'body'		=> 'Hello ' . $user['first_name'] . ',<br/><p>Some of your account settings seem to be changed by the administrator. Please check your account settings and contact the administrator if any issue.</p>Best Regards,<br/>Administration,<br/>Wisdom Books'
                );
                $this->mail->send($data_set_mail);

                $notification = array(
                    'is_notification'           => TRUE,
                    'notification_type'         => 'success',
                    'notification_description'  => 'The user is edited successfully'
                );
            } else {
                $notification = array(
                    'is_notification'          => TRUE,
                    'notification_type'        => 'error',
                    'notification_description' => 'Error terminate editing the user.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('administration');
        
        } else { //invalid
            $data = $this->application->set_data();
            $data['module'] = $this->module;

            //retrieve user data
            $criteria   = array('nic' => $user_id);
            $fields     = array(
                'nic AS user_id','user_role','designation','email',
                "CONCAT_WS(' ',first_name,last_name) AS user_name"
            );
            $data['user'] = $this->user_model->get_user($fields,$criteria);
            //set editable values
            $data['user']['new_email']       = $this->input->post('txt_email');
            $data['user']['new_designation'] = $this->input->post('txt_designation');
            $data['user']['new_user_role']   = $this->input->post('lst_user_role');

            //var_dump($data);
            $this->load->view('administration/edit_user_view',$data);
        }
    
    }//end of function
    
    public function deactivate_user($user_id) {

        //update user status as past
        $criteria = array('nic' => $user_id);
        $data_set = array('user_status'	=> FALSE);
        $query_status = $this->user_model->update_user($data_set,$criteria);

        if($query_status) {

            //write system log
            $description = "Deactivate user: {$user_id}.";
            $this->application->write_log('admin', $description);
            
            //sending mail
            $fields = array('email', 'first_name', 'last_name');
            $user   = $this->user_model->get_user($fields,$criteria); 
            $data_set_mail = array(
                'recipient_address' => $user['email'],
                'recipient_name'    => $user['first_name'] . ' ' . $user['last_name'],
                'subject'	    => 'Account Deactivation',
                'body'		    => 'Hello ' . $user['first_name'] . ',<br/><p>This is to inform that your account has been deactivated. so you are no longer able to access the system. Please contact the administrator if it seems a mistake.</p>Best Regards,<br/>Administration,<br/>Wisdom Books'
            );
            $this->mail->send($data_set_mail);


            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The user is deactivated successfully.'
            );
        } else { 
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated deactivating the user.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('administration');

    }//end of function

    public function reactivate_user($user_id) {

        //update user status as 'curent'
        $criteria = array('nic' => $user_id);
        $data_set = array('user_status' => TRUE);
        $query_status = $this->user_model->update_user($data_set,$criteria);

        if($query_status) {

            //write system log
            $description = "Reactivate user: {$user_id}.";
            $this->application->write_log('admin', $description);
            
            //sending mail
            $fields = array('email', 'first_name', 'last_name');
            $user   = $this->user_model->get_user($fields,$criteria); 
            $data_set_mail = array(
                'recipient_address' => $user['email'],
                'recipient_name'    => $user['first_name'] . ' ' . $user['last_name'],
                'subject'	    => 'Account Reactivation',
                'body'		    => 'Hello ' . $user['first_name'] . ',<br/><p>This is to inform that your account has been reactivated successfully. you password will be same as your last one. Please contact the administrator if any issue.</p>Best Regards,<br/>Administration,<br/>Wisdom Books'
            );
            $this->mail->send($data_set_mail);

            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The user is reactivated successfully.'
            );
        } else {
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated reactivating the user.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('administration');

    }//end of function

    public function reset_password($user_role,$user_id) {
        
        //create default password
        $password = '';
        $psw_text = '';
        if($user_role == 'admin') {
            $password = crypt($this->config->item('psw_admin'));
            $psw_text = $this->config->item('psw_admin');
        } else {
           $password = crypt($this->config->item('psw_common')); 
           $psw_text = $this->config->item('psw_common'); 
        }
        $criteria = array('nic' => $user_id);
        $data_set = array('password' => $password);
        $query_status = $this->user_model->update_user($data_set,$criteria);

        if($query_status) {

            //write system log
            $description = "Reset password: {$user_id}.";
            $this->application->write_log('admin', $description);
            
            //sending mail
            $fields = array('email', 'first_name', 'last_name');
            $user   = $this->user_model->get_user($fields,$criteria); 
            $data_set_mail = array(
                'recipient_address' => $user['email'],
                'recipient_name'    => $user['first_name'] . ' ' . $user['last_name'],
                'subject'	    => 'Password Resetting',
                'body'		    => 'Hello ' . $user['first_name'] . ',<br/><p>This is to inform that your password has been reset to <b>' . $psw_text .'</b> by the administrator. This is a default password, so you are strongly advised to <b>replace it with your own immediately after the next login.</b> Please conatact the administrator if any issue.</p>Best Regards,<br/>Administration,<br/>Wisdom Books'
            );
            $this->mail->send($data_set_mail);

            
            //prepare notifications
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'success',
                'notification_description'  => 'The password is reset successfully.'
            );
        } else {
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated resetting the password.'
            );
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('administration');
        
    }//end of function

    public function user_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('users_is_search',true);
        $this->session->set_userdata('users_search_key',$key);
        $this->session->set_userdata('users_search_value',$val);
        redirect('administration');

    }//end of function

    public function user_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('users_is_search');
        $this->session->unset_userdata('users_search_key');
        $this->session->unset_userdata('users_search_value');
        redirect('administration');

    }//end of function
    
    // supportive functions
    private function validate_add_user() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('txt_nic', 'NIC No.', 'trim|required|is_unique[tbl01_user.nic]');
        $this->form_validation->set_rules('txt_first_name', '', '');
        $this->form_validation->set_rules('txt_last_name', '', '');
        $this->form_validation->set_rules('txt_email', 'email', 'trim|required|is_unique[tbl01_user.email]');
        $this->form_validation->set_rules('txt_designation', '', '');
        $this->form_validation->set_rules('lst_user_role', '', '');
        return $this->form_validation->run();
    }//end of function
    
    private function validate_edit_user($user_id) {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('txt_email', 'new email', "trim|required|callback_validate_email[$user_id]");
        return $this->form_validation->run();
    }//end of function
    
    //callback function for validate_edit_user
    public function validate_email($str,$user_id) {
	$criteria = array(
            'nic != '	=> $user_id,
            'email'     => $this->input->post('txt_email')
        );
        $rows = $this->user_model->get_total_rows($criteria);
        if($rows > 0) { //email address is existing for other user
            $this->form_validation->set_message("validate_email", "The %s field must contian a unique value.");
            return FALSE;
        }
        return TRUE;
    }//end of function
	
    
//----------------- System Log Section ---------------

    //views
    public function maintenance($page = 0) {
        
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	

        //set criteria if any
        if($this->session->userdata('log_is_search')) {

            $key   = $this->session->userdata('log_search_key');
            $value = $this->session->userdata('log_search_value');
            
            if( $key == 'category' ){ //category
                switch ($value){
                    case '1' : $value = 'sales' ; break; 
                    case '2' : $value = 'purchase' ; break; 
                    case '3' : $value = 'stock' ; break; 
                    case '4' : $value = 'reports' ; break; 
                    case '5' : $value = 'financial' ; break; 
                    case '6' : $value = 'admin' ; break; 
                    default  : $value = FALSE;
                }
            } else if( $key == 'DATE(date_added)' ) { //date
                if(!(bool)preg_match( '/^\d{4}\-\d{1,2}\-\d{1,2}+$/',$value)) {
                    $value = FALSE;
                }
            } else if($key == 'MONTH(date_added)') { //month
                if(!((bool)preg_match( '/^\d+$/',$value) && $value >= 1 && $value <= 12)) {
                    $value = FALSE;
                }
            }
            $criteria = array($key => $value);
        }
        
        //set pagination
        $total_rows = $this->log_model->get_total_rows($criteria) ;
        $this->application->set_pagination($total_rows,site_url('administration/maintenance'));

        //set data
        $fields = array(
                    "category","description","user_id",
                    "CONCAT(first_name,' - ',user_role) AS user_name", "user_status",
                    "DATE_FORMAT(date_added,'%Y-%m-%d %r') AS date_added"
                  );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array('tbl01_user', 'tbl02_log.user_id = tbl01_user.nic');
        $order_by = 'tbl02_log.id DESC';

        $data['entries'] = $this->log_model->get_entries($fields,$criteria,$page,$limit,$order_by,$join1);
        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view("administration/maintenance_view",$data);
        
    }//end of function
    
    //actions
    public function log_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('log_is_search',TRUE);
        $this->session->set_userdata('log_search_key',$key);
        $this->session->set_userdata('log_search_value',$val);

        redirect('administration/maintenance');

    }//end of function

    public function log_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('log_is_search');
        $this->session->unset_userdata('log_search_key');
        $this->session->unset_userdata('log_search_value');

        redirect('administration/maintenance');

    }//end of function
    
    public function backup() {
       
        //write system log
        $this->application->write_log('admin', 'Generate data backup.');
    
        // Load the DB utility class and helpers
        $this->load->dbutil();
        $this->load->helper('file');
        $this->load->helper('download');

        date_default_timezone_set('Asia/Colombo');
        $sql_file_name = 'backup_' . date('Y_m_d_H_i_s'). '.sql' ;
        $zip_file_name = 'backup_' . date('Y_m_d_H_i_s'). '.zip' ;

        //set preferences
        $prefs = array(
                    'format'      => 'zip',             									
                    'filename'    => $sql_file_name,		
                    'add_drop'    => TRUE,              							
                    'add_insert'  => TRUE,              						
                    'newline'     => "\n"               					
                 );
        $backup = $this->dbutil->backup($prefs);		
        force_download($zip_file_name, $backup); 
           
    }//end of function
    
} //end of class
//end of file
<?php if ( ! defined('BASEPATH')) {exit('No direct script access allowed');}

/*
+--------------------------------------------------------------------------
|	Web-based Bookstore Management for Wisdom Bookshop 
|	File: 		Application.php
|	Content:	Application-wide functions definitions
+--------------------------------------------------------------------------
*/

class Application {

    private $CI ;

    public function __construct() {
        $this->CI =& get_instance();
    }

    //user levels
    public function level1($logout = TRUE) { // super-admin or admin 
        if($this->CI->session->userdata('is_login') && ($this->CI->session->userdata('user_role') == 'super-admin' || $this->CI->session->userdata('user_role') == 'admin')){
            return TRUE;
        }else{
            if($logout){ redirect('home'); }
            else{ return FALSE; }
        }
    }//end of function
    
    public function level2($logout = TRUE) { //super-admin or manager
        if($this->CI->session->userdata("is_login") && ($this->CI->session->userdata('user_role') == 'super-admin' || $this->CI->session->userdata('user_role') == 'manager')){
            return TRUE;
        }else{
            if($logout){ redirect('home'); }
            else{ return FALSE; }
        }
    }//end of function

    public function level3($logout = TRUE) { //super-admin, manager or operator
        if($this->CI->session->userdata("is_login") && ($this->CI->session->userdata('user_role') == 'super-admin' || $this->CI->session->userdata('user_role') == 'manager' || $this->CI->session->userdata('user_role') == 'operator')){
            return TRUE;
        }else{
            if($logout){ redirect('home'); }
            else{ return FALSE; }
        }
    }//end of function
    
    public function level4($logout = TRUE) { //admin, manager or operator
        if($this->CI->session->userdata("is_login") && ($this->CI->session->userdata('user_role') == 'admin' || $this->CI->session->userdata('user_role') == 'manager' || $this->CI->session->userdata('user_role') == 'operator')){
            return TRUE;
        }else{
            if($logout){ redirect('home'); }
            else{ return FALSE; }
        }
    }//end of function

    //sets common data set needed for views
    public function set_data() {
        $data = array();

        //set default values
        $data['user_status']     = $this->CI->session->userdata('is_login'); 	
        $data['user_id']	 = $this->CI->session->userdata('user_id'); 	
        $data['user_name']	 = $this->CI->session->userdata('user_name'); 	
        $data['user_role']	 = $this->CI->session->userdata('user_role');
        $data['last_login']	 = $this->CI->session->userdata('last_login');
        $data['is_notification'] = FALSE;						
                                                                                       
        return $data;

    }//end of function

    //sets the pagination
    public function set_pagination($num_rows,$url) {

        $this->CI->load->library('pagination');

        $config['base_url']   	  = $url;
        $config['total_rows'] 	  = $num_rows;
        $config['per_page']   	  = $this->CI->config->item('rows_per_page');
        $config['first_link'] 	  = FALSE;
        $config['last_link']  	  = FALSE;
        $config['num_links']  	  = 10;
        $config['num_tag_open']   = '<li>';
        $config['num_tag_close']  = '</li>';
        $config['cur_tag_open']   = "<li class='active' ><a>";	
        $config['cur_tag_close']  = "</a></li>";
        $config['next_link'] 	  = "<span class='glyphicon glyphicon-chevron-right' />";
        $config['next_tag_open']  = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] 	  = "<span class='glyphicon glyphicon-chevron-left' />";
        $config['prev_tag_open']  = '<li>';
        $config['prev_tag_close'] = '</li>';

        $this->CI->pagination->initialize($config);

    }//end of function
    
    //insert log entry
    public function write_log($category,$description) {
        
        $this->CI->load->model("administration/log_model");
        $data_set = array(
            'category'    => $category,
            'description' => $description,
            'user_id'     => $this->CI->session->userdata("user_id"),
            'date_added'  => get_cur_date_time()
        );
        return $this->CI->log_model->add_entry($data_set);
    }
    
    //this function resets array index in to value of key. the key value must be unique.
    public function array_set_index($ary, $key) {
        $temp = array();
        foreach($ary as $row) {
            $index = $row[$key];
            $temp[$index] = $row;
        }
        return $temp;
    }
    
    //this function extract the values from associative array 
    //according to the key specified and returned them as common array
    public function array_remove_assoc($ary, $key){
        $temp = array();
        foreach($ary as $row) {
            array_push($temp, $row[$key]);
        }
        return $temp;
    }

    // this function chrcu is user logge in
    public function is_logged_in($logout = TRUE)
     {
         if($this->CI->session->userdata("is_login")){
            return TRUE;
        }else{
            if($logout){ redirect('home'); }
            else{ return FALSE; }
        }
     } 
  
}//end of class
// end of file 
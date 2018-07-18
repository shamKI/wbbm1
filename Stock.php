<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock extends CI_Controller {
    
    private $module = 'stock';
    
    public function __construct() {
        parent::__construct();
        $this->application->level2();
        $this->load->model('stock/category_model');
        $this->load->model('stock/agent_model');
        $this->load->model('stock/dealer_model');
        $this->load->model('stock/book_model');
        
    }
//----------------- Book Section -------------------
    //views
    
    public function index($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	
        $criteria_in	= '';	

        //set criteria if any
        if($this->session->userdata('stock_books_is_search')) { 
            $key   = $this->session->userdata('stock_books_search_key');
            $value = $this->session->userdata('stock_books_search_value'); 

            if($key == 'book_title' || $key == 'author' || $key == 'publisher') { 				
                $key   = "$key RLIKE";
                $value = "^$value";
            } else if($key == 'sku' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid id 				
                $value = FALSE;
            } else if($key == 'book_status') {
                switch ($value){
                    case '1' : $value = TRUE ; break; 
                    case '0' : $value = FALSE ; break; 
                    default  : $key = 'sku';$value = FALSE;
                }
            } else if($key == "category") {
                $criteria1 = array("category_name RLIKE" => "^$value" );
                $fields1   = array("id");
                $categories= $this->category_model->get_categories($fields1,$criteria1);
                $key       = "tbl06_book.category_id";
                if($categories) {
                    $temp = array();
                    foreach($categories as $category) {
                        array_push($temp, $category['id']);
                    }
                    $criteria_in[0] = $key;
                    $criteria_in[1] = $temp;
                }else{
                    $value = FALSE;
                }
            }else if($key == "agent") {
                $criteria1 = array("agent_name RLIKE" => "^$value" );
                $fields1   = array("id");
                $agents    = $this->agent_model->get_agents($fields1,$criteria1);
                $key       = "tbl06_book.agent_id";
                if($agents) {
                    $temp = array();
                    foreach($agents as $agent) {
                        array_push($temp, $agent['id']);
                    }
                    $criteria_in[0] = $key;
                    $criteria_in[1] = $temp;
                } else {
                    $value = FALSE;
                }
            } 
            if($criteria_in == '') {
                $criteria = array($key => $value);
            }
        }
        
        //set notification if any
        if($this->session->flashdata('is_notification')) {
            $data['is_notification']		= $this->session->flashdata('is_notification');
            $data['notification_type']		= $this->session->flashdata('notification_type');
            $data['notification_description']   = $this->session->flashdata('notification_description');
        }

        //set pagination
        $total_rows = $this->book_model->get_total_rows($criteria,$criteria_in) ;
        $this->application->set_pagination($total_rows,site_url('stock/index'));

        //set data
        $fields = array(
            "sku",
            "CONCAT_WS('<br/>',book_title,isbn,author,publisher) AS description",
            "category_name AS category","category_status", "agent_name AS agent", "agent_status",
            "purchase_price", "list_price", "discount",
            "min_stock", "max_stock", "cur_stock","IF(min_stock >= cur_stock AND book_status, TRUE, FALSE) AS is_low",
            "IFNULL(last_sold,'-') AS last_sold", 
            "IF(is_ordered,'Yes','No') AS is_ordered", 
            "IF(book_status, 'continue', 'discontinued')AS status", "book_status",
            "IFNULL(date_added,'-') AS date_added", "IFNULL(date_discontinued,'-') AS date_discontinued"
        );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array("tbl03_category", "tbl06_book.category_id = tbl03_category.id");
        $join2	  = array("tbl04_agent", "tbl06_book.agent_id = tbl04_agent.id");
        $data['books'] = $this->book_model->get_books($fields,$criteria,$page,$limit,'',$join1,'',$join2,'',$criteria_in);
        
        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('stock/books_view',$data);

    }//end of function

    public function add() {
        $data = $this->get_common_data_for_books();
        
        //var_dump($data);
        $this->load->view('stock/add_book_view',$data);

    }//end of function
    
    public function edit($sku) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        $fields = array(
            "sku","book_title", "isbn", "author", "publisher",
            "category_name AS category","category_id", "category_status" ,"agent_name AS agent", "agent_status",
            "purchase_price", "list_price", "discount",
            "min_stock", "max_stock", "cur_stock",
            "IFNULL(last_sold,'-') AS last_sold", 
            "IF(is_ordered,'Yes','No') AS is_ordered", 
            "IFNULL(date_added,'-') AS date_added"
        );
        $criteria = array('sku' => $sku);
        $join1	  = array("tbl03_category", "tbl06_book.category_id = tbl03_category.id");
        $join2	  = array("tbl04_agent", "tbl06_book.agent_id = tbl04_agent.id");
        $data['book'] = $this->book_model->get_book($fields,$criteria,$join1,$join2);
        
        //get current categories
        $fields   = array('id', 'category_name');
        $criteria = array('category_status' => TRUE);
        $data['categories'] = $this->category_model->get_categories($fields,$criteria);
        
        //var_dump($data);
        $this->load->view('stock/edit_book_view',$data);

    }//end of function
    
    public function stock_history($sku) {
        $data = $this->application->set_data();
        $data['module'] = $this->module;
        $this->load->model('sales/sales_book_model');
        $this->load->model('purchase/purchase_book_model');
        $cur_year  = get_cur_year();
        $cur_month = get_cur_month();
        
        $fields = array(
            'sku', "CONCAT(book_title,' - ',isbn) AS description", 'book_status',
            'date_added', "IFNULL(date_discontinued,'-')date_discontinued",
            "IF(book_status,'Continue','Discontinued') as status",
            "agent_name AS agent", "agent_status",
            'YEAR(date_added) AS year_start', 'MONTH(date_added) AS month_start',
            "IFNULL(YEAR(date_discontinued),'{$cur_year}') AS year_end",
            "IFNULL(MONTH(date_discontinued),'{$cur_month}') AS month_end",
        );
        $criteria = array('sku' => $sku);
        $join1	  = array("tbl04_agent", "tbl06_book.agent_id = tbl04_agent.id");
        $book     = $this->book_model->get_book($fields,$criteria,$join1);
        
        //get sales
        $fields = array(
            "YEAR(date_sold) AS year_sold", "MONTH(date_sold) AS month_sold",
            "CONCAT(YEAR(date_sold),MONTH(date_sold)) AS id",
            "SUM(quantity_sold) AS quantity"
        );
        $criteria = array('sales_sku' => $sku);
        $join1    = array('tbl07_sales', 'tbl08_sales_book.sales_id = tbl07_sales.id');
        $group_by = array('YEAR(date_sold)','MONTH(date_sold)');
        $order_by = "YEAR(date_sold) ASC, MONTH(date_sold) ASC";
        $sales    = $this->sales_book_model->get_sales_books($fields,$criteria,'','',$order_by,$join1,$group_by);
        if($sales) {
            $sales = $this->application->array_set_index($sales,'id');
        }
        //var_dump($sales);
        
        //get purchases
        $fields = array(
            "YEAR(date_com_can) AS year_purchased", "MONTH(date_com_can) AS month_purchased",
            "CONCAT(YEAR(date_com_can),MONTH(date_com_can)) AS id",
            "SUM(quantity_purchased) AS quantity"
        );
        $criteria = array('purchase_sku' => $sku, 'purchase_status' => 'completed' );
        $join1    = array('tbl11_purchase', "tbl12_purchase_book.purchase_id = tbl11_purchase.id");
        $group_by = array('YEAR(date_com_can)','MONTH(date_com_can)');
        $order_by = "YEAR(date_com_can) ASC, MONTH(date_com_can) ASC";
        $purchases= $this->purchase_book_model->get_purchase_books($fields,$criteria,'','',$order_by,$join1,$group_by);
        if($purchases) {
            $purchases = $this->application->array_set_index($purchases,'id');
        }
        //var_dump($purchases);
        
        $remain_stock = 0;
        $graph = array();
        for($i = $book['year_start']; $i<= $book['year_end']; $i++) {
            $month_start = 1;
            $month_end   = 12;
            if($book['year_start'] == $i) { //sarting point
                $month_start = $book['month_start'];
            }
            if($book['year_end'] == $i) { //ending point
                $month_end = $book['month_end'];
            }
            for($j = $month_start; $j <= $month_end; $j++) {
                $id = $i . $j ;
                $quantity_purchased = 0;
                $quantity_sold      = 0;
                if($purchases && array_key_exists($id, $purchases)) {
                    $quantity_purchased = $purchases[$id]['quantity'];
                }
                if($sales && array_key_exists($id, $sales)) {
                    $quantity_sold = $sales[$id]['quantity'];
                }
                $remain_stock = $remain_stock + $quantity_purchased - $quantity_sold;
                array_push($graph,array(
                    'year_month' => $i . "-". get_month_name($j),
                    'quantity_purchased' => $quantity_purchased,
                    'quantity_sold'      => $quantity_sold,
                    'remaining_stock'    => $remain_stock
                ));
            }//inner-for
        }//outer-for
        //var_dump($graph);
        
        $data['book']  = $book;
        $data['graph'] = $graph;
        
        //var_dump($data);
        $this->load->view('stock/stock_history_view',$data);

    }//end of function
    
    //actions
    
    public function add_book() {
        //var_dump($_POST);
        
        //server side form validation
        $server_validation = $this->validate_add_book();

        if($server_validation) {
            
            //insert data
            $data_set = array(
                "isbn"          => $this->input->post('txt_isbn'),
                'book_title'    => ucwords(strtolower($this->input->post('txt_title'))),
                'author'        => ucwords(strtolower($this->input->post('txt_author'))),
                'publisher'     => ucwords(strtolower($this->input->post('txt_publisher'))),
                'category_id'   => $this->input->post('lst_category'),
                'agent_id'      => $this->input->post('lst_agent'),
                'list_price'    => $this->input->post('txt_list_price'),
                'purchase_price'=> $this->input->post('txt_purchase_price'),
                'min_stock'     => $this->input->post('txt_min_stock'),
                'max_stock'     => $this->input->post('txt_max_stock'),
                'discount'      => $this->input->post('txt_discount'),
                'date_added'    => get_cur_date()
            );

            $query_status = $this->book_model->add_book($data_set);
            
            if($query_status) {

                //insert system log entry
                $description = "Add book: {$query_status}.";
                $this->application->write_log('stock', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => 'The new book is added successfully.'
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new book.'
                );
            }

            $this->session->set_flashdata($notification);
            redirect('stock');
            
        } else {
            $data = $this->get_common_data_for_books();
            $this->load->view('stock/add_book_view',$data);
        }
       
    }//end of function

    public function edit_book($sku) {
        //var_dump($_POST);
    
        //prepare the data & update
        $data_set = array(
            'category_id'   => $this->input->post('lst_category'),
            'list_price'    => $this->input->post('txt_list_price'),
            'purchase_price'=> $this->input->post('txt_purchase_price'),
            'min_stock'     => $this->input->post('txt_min_stock'),
            'max_stock'     => $this->input->post('txt_max_stock'),
            'discount'      => $this->input->post('txt_discount')
        );
        $criteria     = array('sku' => $sku);
        $query_status = $this->book_model->update_book($data_set,$criteria);

        //prepare notifications and redirect
        if($query_status) {
            //write system log
            $description = "Edit book: {$sku}";
            $this->application->write_log('stock', $description);

            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'success',
                'notification_description'  => 'The book is edited successfully'
            );
        } else {
            $notification = array(
                'is_notification'          => TRUE,
                'notification_type'        => 'error',
                'notification_description' => 'Error terminate editing the book.'
            );
        }
        $this->session->set_flashdata($notification);
        redirect('stock');

    }//end of function
    
    public function discontinue_book($sku) {

        //update book status as discontinued
        $criteria = array('sku' => $sku);
        $data_set = array(
            'book_status'	=> FALSE,
            'date_discontinued' => get_cur_date()
        );
        $query_status = $this->book_model->update_book($data_set,$criteria);

        if($query_status) {

            //write system log
            $description = "Discontinue book: {$sku}.";
            $this->application->write_log('stock', $description);

            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The book is discontinued successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated discontinuing the book.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock');

    }//end of function

    public function stock_book_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('stock_books_is_search',true);
        $this->session->set_userdata('stock_books_search_key',$key);
        $this->session->set_userdata('stock_books_search_value',$val);
        redirect('stock');

    }//end of function

    public function stock_book_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('stock_books_is_search');
        $this->session->unset_userdata('stock_books_search_key');
        $this->session->unset_userdata('stock_books_search_value');
        redirect('stock');

    }//end of function
    
    // supportive functions
    private function validate_add_book() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('txt_isbn', 'ISBN', 'trim|required|callback_is_existing');
        $this->form_validation->set_rules('txt_title', '', '');
        $this->form_validation->set_rules('txt_author', '', '');
        $this->form_validation->set_rules('txt_publisher', '', '');
        $this->form_validation->set_rules('lst_category', '', '');
        $this->form_validation->set_rules('lst_agent', '', '');
        $this->form_validation->set_rules('txt_min_stock', '', '');
        $this->form_validation->set_rules('txt_max_stock', '', '');
        $this->form_validation->set_rules('txt_purchase_price', '', '');
        $this->form_validation->set_rules('txt_list_price', '', '');
        $this->form_validation->set_rules('txt_discount', '', '');
        return $this->form_validation->run();
    }//end of function
    
    //call back function for validate_add_book
    public function is_existing($str) {
        $criteria   = array(
            'isbn' => $str,
            'agent_id'   => $this->input->post('lst_agent')
        );
        $rows = $this->book_model->get_total_rows($criteria);

        if($rows > 0) {  // exist 
            $this->form_validation->set_message('is_existing', 'The isbn is already existed for specified agent.');
            return FALSE ;
        }
        return TRUE;
    }//end of function
    
    private function get_common_data_for_books() {
        $data                = $this->application->set_data();
        $data['module']      = $this->module;
        
        //get current categories
        $fields = array('id', 'category_name');
        $criteria = array('category_status' => TRUE);
        $data['categories']  = $this->category_model->get_categories($fields,$criteria);
        //get current agents
        $fields = array('id', 'agent_name');
        $criteria = array('agent_status' => TRUE);
        $data['agents'] = $this->agent_model->get_agents($fields,$criteria);
        
        return $data;
        
    }//end of function   
    
//----------------- Categories, Agents & Dealers ---------------

    //views
    public function categories_agents_dealers() {
        $data = $this->get_common_data();
        
        //set notification if any
        if($this->session->flashdata('is_notification')) {
            $data['is_notification']		= $this->session->flashdata('is_notification');
            $data['notification_type']		= $this->session->flashdata('notification_type');
            $data['notification_description']   = $this->session->flashdata('notification_description');
        }
        
        //var_dump($data);
        $this->load->view('stock/categories_agents_dealers_view',$data);
    }//end of funtcion
    
    //supportives
    private function get_common_data() {
        $data                = $this->application->set_data();
        $data['module']      = $this->module;
        
        //get all categories
        $fields = array('id', 'category_name', 'category_status',"IF(category_status,'Active','Canceled') AS status");
        $data['categories']  = $this->category_model->get_categories($fields);
        //get all agents
        $fields = array(
            'id', 'agent_name','contact','credit_amount',
            'agent_status',"IF(agent_status,'Current','Past') AS status");
        $data['agents'] = $this->agent_model->get_agents($fields);
        //get all dealers
        $fields = array(
            'id', 'dealer_name','contact', 'debit_amount',
            'dealer_status',"IF(dealer_status,'Current','Past') AS status");
        $data['dealers'] = $this->dealer_model->get_dealers($fields);
    
        return $data;
        
    }//end of function
  
//----------------- Categories Section ---------------

    //actions
    public function add_category() {
        //var_dump($_POST);
        
        //server side form validation
        $server_validation = $this->validate_add_category();
        if($server_validation) {
            $data_set = array(
                'category_name' => strtolower($this->input->post('txt_category_name'))
            );
            $query_status = $this->category_model->add_category($data_set);
            if($query_status) {

                //insert system log entry
                $description = "Add category: {$this->input->post('txt_category_name')}.";
                $this->application->write_log('stock', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => 'The new category is added successfully.'
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new category.'
                );
            }

            $this->session->set_flashdata($notification);
            redirect('stock/categories_agents_dealers');
            
        } else {
            $data = $this->get_common_data();
            $this->load->view('stock/categories_agents_dealers_view',$data);
        }
    }//end of function
    
    public function cancel_category($category_id) {

        //update category status as incative
        $criteria = array('id' => $category_id);
        $data_set = array('category_status' => FALSE);
        $query_status = $this->category_model->update_category($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The category is canceled successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the category.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');

    }//end of function

    public function reactivate_category($category_id) {

        //update category status as 'active'
        $criteria = array('id' => $category_id);
        $data_set = array('category_status' => TRUE);
        $query_status = $this->category_model->update_category($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The category is reactivated successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated reactivating the category.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');

    }//end of function
    //suppotives
    private function validate_add_category() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('txt_category_name', 'category name', 'trim|required|is_unique[tbl03_category.category_name]');
        return $this->form_validation->run();
    }//end of function

    
//----------------- Agents Section ---------------
    //views
    public function agent_edit($agent_id) {
        $data                = $this->application->set_data();
        $data['module']      = $this->module;
        
        $fields = array('id', 'agent_name','contact',"IF(agent_status,'Current','Past') AS status");
        $criteria = array('id' => $agent_id);
        $data['agent'] = $this->agent_model->get_agent($fields, $criteria);
        //var_dump($data);
        $this->load->view('stock/edit_agent_view', $data);
        
    }//end of function
    
    //actions
    public function add_agent() {
        //server side form validation
        $server_validation = $this->validate_add_agent();
        if($server_validation) {
            $data_set = array(
                'agent_name' => ucwords(strtolower($this->input->post('txt_agent_name'))),
                'contact'    => $this->input->post('txt_agent_contact')
            );
            $query_status = $this->agent_model->add_agent($data_set);
            if($query_status) {
                //insert system log entry
                $description = "Add agent: {$this->input->post('txt_agent_name')}.";
                $this->application->write_log('stock', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => 'The new agent is added successfully.'
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new agent.'
                );
            }

            $this->session->set_flashdata($notification);
            redirect('stock/categories_agents_dealers');
            
        } else {
            $data = $this->get_common_data();
            $this->load->view('stock/categories_agents_dealers_view',$data);
        }
    }//end of function
    
    public function edit_agent($agent_id) {
        //update agent contact
        $criteria = array('id' => $agent_id);
        $data_set = array('contact' => $this->input->post('txt_contact'));
        $query_status = $this->agent_model->update_agent($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The agent is edited successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated editing the agent.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');
    }//end of function
    
    public function cancel_agent($agent_id) {

        //update agent status as past
        $criteria = array('id' => $agent_id);
        $data_set = array('agent_status' => FALSE);
        $query_status = $this->agent_model->update_agent($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The agent is canceled successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the agent.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');

    }//end of function

    public function reactivate_agent($agent_id) {

        //update agent status as 'current'
        $criteria = array('id' => $agent_id);
        $data_set = array('agent_status' => TRUE);
        $query_status = $this->agent_model->update_agent($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The agent is reactivated successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated reactivating the agent.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');

    }//end of function
    
    //supportive
    private function validate_add_agent() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('txt_agent_name', 'agent name', 'trim|required|is_unique[tbl04_agent.agent_name]');
        $this->form_validation->set_rules('txt_agent_contact', '', '');
        return $this->form_validation->run();
    }//end of function
    
//----------------- Dealers Section ---------------
    //views
    public function dealer_edit($dealer_id) {
        $data                = $this->application->set_data();
        $data['module']      = $this->module;
        
        $fields = array('id', 'dealer_name','contact',"IF(dealer_status,'Current','Past') AS status");
        $criteria = array('id' => $dealer_id);
        $data['dealer'] = $this->dealer_model->get_dealer($fields, $criteria);
        //var_dump($data);
        $this->load->view('stock/edit_dealer_view', $data);
        
    }//end of function
    
    //actions
    public function add_dealer() {
        //server side form validation
        $server_validation = $this->validate_add_dealer();
        if($server_validation) {
            $data_set = array(
                'dealer_name' => ucwords(strtolower($this->input->post('txt_dealer_name'))),
                'contact'     => $this->input->post('txt_dealer_contact')
            );
            $query_status = $this->dealer_model->add_dealer($data_set);
            if($query_status) {
                //insert system log entry
                $description = "Add dealer: {$this->input->post('txt_dealer_name')}.";
                $this->application->write_log('stock', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => 'The new dealer is added successfully.'
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new dealer.'
                );
            }

            $this->session->set_flashdata($notification);
            redirect('stock/categories_agents_dealers');
            
        } else {
            $data = $this->get_common_data();
            $this->load->view('stock/categories_agents_dealers_view',$data);
        }
    }//end of function
    
    public function edit_dealer($dealer_id) {
        //update dealer contact
        $criteria = array('id' => $dealer_id);
        $data_set = array('contact' => $this->input->post('txt_contact'));
        $query_status = $this->dealer_model->update_dealer($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The dealer is edited successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated editing the dealer.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');
    }//end of function
    
    public function cancel_dealer($dealer_id) {

        //update dealer status as past
        $criteria = array('id' => $dealer_id);
        $data_set = array('dealer_status' => FALSE);
        $query_status = $this->dealer_model->update_dealer($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The dealer is canceled successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the dealer.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');

    }//end of function

    public function reactivate_dealer($dealer_id) {

        //update dealer status as 'current'
        $criteria = array('id' => $dealer_id);
        $data_set = array('dealer_status' => TRUE);
        $query_status = $this->dealer_model->update_dealer($data_set,$criteria);

        if($query_status) {
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The dealer is reactivated successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated reactivating the dealer.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('stock/categories_agents_dealers');

    }//end of function
    
    //supportive
    private function validate_add_dealer() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('txt_dealer_name', 'dealer name', 'trim|required|is_unique[tbl05_dealer.dealer_name]');
        $this->form_validation->set_rules('txt_dealer_contact', '', '');
        return $this->form_validation->run();
    }//end of function

    
} //end of class
//end of file
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sales extends CI_Controller {

    private $module = 'sales';
    
    public function __construct() {
        parent::__construct();
        $this->application->level3();
        $this->load->model('sales/sales_model');
        $this->load->model('sales/sales_book_model');
        $this->load->model('sales/sales_return_model');
        $this->load->model('stock/dealer_model');
        $this->load->model('stock/agent_model');
        $this->load->model('stock/book_model');
        $this->load->model('financial/financial_model');
    }
    
//----------------- sales Section -------------------
    //views
    
    public function index($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	
        $criteria_in	= '';	

        //set criteria if any
        if($this->session->userdata('sales_is_search')) { 
            $key   = $this->session->userdata('sales_search_key');
            $value = $this->session->userdata('sales_search_value'); 

            if($key == 'tbl07_sales.id' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid id 				
                $value = FALSE;
            } else if($key == "dealer") {
                $criteria1 = array("dealer_name RLIKE" => "^$value" );
                $fields1   = array("id");
                $dealers   = $this->dealer_model->get_dealers($fields1,$criteria1);
                $key       = "tbl07_sales.dealer_id";
                if($dealers) {
                    $temp = array();
                    foreach($dealers as $dealer) {
                        array_push($temp, $dealer['id']);
                    }
                    $criteria_in[0] = $key;
                    $criteria_in[1] = $temp;
                } else {
                    $value = FALSE;
                }
            } else if($key == 'DATE(date_sold)') { 
                if(!(bool)preg_match( '/^\d{4}\-\d{1,2}\-\d{1,2}+$/',$value)) {
                    $value = FALSE;
                }
            } else if($key == 'MONTH(date_sold)') { 
                if(!((bool)preg_match( '/^\d+$/',$value) && $value >= 1 && $value <= 12)) {
                    $value = FALSE;
                }
            } else if($key == 'sales_type') {
                $key = 'dealer_id';
                switch($value) {
                    case '1': $value = NULL; break;
                    case '2': $value = $key = 'dealer_id != '; $value= NULL; break;
                    default : $key = 'tbl07_sales.id'; $value = FALSE;
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
        $total_rows = $this->sales_model->get_total_rows($criteria,$criteria_in) ;
        $this->application->set_pagination($total_rows,site_url('sales/index'));

        //set data
        $fields = array(
            "tbl07_sales.id AS sales_id",
            "IFNULL(dealer_name,'-') AS dealer", 
            "IF(ISNULL(dealer_name),'0',IF(dealer_status,'1','2')) AS dealer_status",
            "IF(ISNULL(dealer_name),'retail','wholesale') AS sales_type",
            "total_paid", "tbl07_sales.discount AS discount",
            "ROUND(total_amount-(total_amount * tbl07_sales.discount / 100),2) AS net_amount",
            "ROUND((total_amount-(total_amount * tbl07_sales.discount / 100)) - total_paid,2) AS debits",
            "date_sold",
            "IF(DATE_ADD(date_sold, INTERVAL 1 MONTH) < CURDATE() AND (((total_amount-(total_amount * tbl07_sales.discount / 100)) - total_paid) > 0),'error','none') AS notification",
            "IF((ISNULL(dealer_id) AND (DATE_ADD(date_sold, INTERVAL 10 DAY) >= CURDATE())) OR (!(ISNULL(dealer_id)) AND (DATE_ADD(date_sold, INTERVAL 3 MONTH) >= CURDATE())),TRUE,FALSE) AS is_returnable"
        );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array("tbl05_dealer", "tbl07_sales.dealer_id = tbl05_dealer.id");
        $data['sales'] = $this->sales_model->get_sales($fields,$criteria,$page,$limit,'',$join1,'','','',$criteria_in);
        
        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('sales/sales_view',$data);

    }//end of function
    
    public function add($is_retail = 1) {
        $data = $this->application->set_data();
        $data['module']    = $this->module;
        $data['is_retail'] = $is_retail;

        $key   = $this->session->userdata('sales_search_key');
        $value = $this->session->userdata('sales_search_value'); 

        if(!$is_retail) {
            //get current dealers
            $fields   = array('id', 'dealer_name');
            $criteria = array('dealer_status' => TRUE);
            $data['dealers'] = $this->dealer_model->get_dealers($fields,$criteria);
        }
        if($key == 'tbl07_sales.id' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid id 				
            $value = FALSE;
        } else if($key == "amount") {
            $amount= 5000;
            $fields1   = array("id");
            $amount    = $this->agent_model->get_agents($fields1);
            $key       = "tbl07_sales_id";
            if($amount>=10000) {
                $fields1   = array("id");
                $tax    = $this->agent_model->get_agents($fields1);
            }
            $value = FALSE;                         
        } else {

            $fields2   = array("id");
            $tax    = $this->agent_model->get_agents($fields2);
        }
        //var_dump($data);
        $this->load->view('sales/add_sales_view',$data);

    }//end of function
    
    public function view($sales_id) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get sales details
        $fields = array(
            "tbl07_sales.id AS sales_id",
            "dealer_name AS dealer", "dealer_status",
            "IF(ISNULL(dealer_id),'retail','wholesale') AS sales_type",
            "total_amount", "total_paid","ROUND(total_amount-(total_amount * tbl07_sales.discount / 100) - total_paid,2) AS debits",
            "tbl07_sales.discount","ROUND(total_amount-(total_amount * tbl07_sales.discount / 100),2) AS net_amount",
            "tbl07_sales.tax","ROUND(total_amount-(total_amount * tbl07_sales.tax / 100),2) AS net_amount",
            "date_sold", "CONCAT(first_name,' ',last_name,', ',designation)AS user_sold", "user_status",
        );
        $criteria = array('tbl07_sales.id' => $sales_id);
        $join1	  = array("tbl05_dealer", "tbl07_sales.dealer_id = tbl05_dealer.id");
        $join2	  = array("tbl01_user", "tbl07_sales.user_sold = tbl01_user.nic");
        $data['sales'] = $this->sales_model->get_sale($fields,$criteria,$join1,$join2);
        
        //get sales books details
        $fields = array(
            "sales_sku AS sku",
            "CONCAT(book_title,', ',isbn,'<br/>',author,', ',publisher) AS description", "book_status",
            "quantity_sold", "tbl08_sales_book.list_price","ROUND(quantity_sold * tbl08_sales_book.list_price,2) AS list_amount",
            "tbl08_sales_book.discount AS discount","ROUND(quantity_sold * (tbl08_sales_book.list_price - (tbl08_sales_book.list_price * tbl08_sales_book.discount / 100)),2) AS amount_sold",
        );
        $criteria = array('sales_id' => $sales_id);
        $order_by = 'sales_sku ASC';
        $join1	  = array("tbl06_book", "sales_sku = sku");
        $data['sales_books'] = $this->sales_book_model->get_sales_books($fields,$criteria,'','',$order_by,$join1);
        
        //get grand totals
        $fields = array(
            "SUM(quantity_sold) AS total_quantity_sold",
            "ROUND(SUM(quantity_sold * list_price),2) AS total_list_amount",
            "ROUND(SUM(quantity_sold * (list_price - (list_price * discount / 100))),2) AS total_amount_sold",
        );
        $criteria = array('sales_id' => $sales_id);
        $data['grand_totals'] = $this->sales_book_model->get_sales_book($fields,$criteria);
        
        //get payments
        $this->load->model('financial/payment_model');
        $fields = array(
            'DATE(date_made) AS date_made', 'tbl16_payment.amount AS amount',
            "IFNULL(cheque_no,'-') AS cheque_no",'trans_status'
        );
        $criteria = array(
            'is_purchase'       => FALSE,
            'sales_purchase_id' => $sales_id
        );
        $order_by = 'tbl16_payment.id ASC';
        $join1 = array('tbl15_financial','tbl16_payment.trans_id = tbl15_financial.id');
        $data['payments'] = $this->payment_model->get_payments($fields,$criteria,'','',$order_by,$join1);
        //var_dump($data);
        $this->load->view('sales/view_sale_view',$data);

    }//end of function
    
    //searching books
    public function search($page = 0) {
        $this->load->model('stock/category_model');
        $data = $this->application->set_data();
        $data['module']    = $this->module;
        $criteria	= '';	
        $criteria_in	= '';	

        //set criteria if any
        if($this->session->userdata('sales_books_is_search')) { 
            $key   = $this->session->userdata('sales_books_search_key');
            $value = $this->session->userdata('sales_books_search_value'); 

            if($key == 'book_title' || $key == 'author' || $key == 'publisher') { 				
                $key   = "$key RLIKE";
                $value = "^$value";
            } else if($key == 'sku' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid sku				
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
            } 
            if($criteria_in == '') {
                $criteria = array($key => $value);
            }
        }
        
        //set pagination
        $total_rows = $this->book_model->get_total_rows($criteria,$criteria_in) ;
        $this->application->set_pagination($total_rows,site_url('sales/search'));

        //set data
        $fields = array(
            "sku",
            "CONCAT_WS('<br/>',book_title,isbn,author,publisher) AS description",
            "category_name AS category","category_status", "agent_name AS agent", "agent_status",
            "list_price", "discount",
            "cur_stock",
            "IF(is_ordered,'Yes','No') AS is_ordered", 
            "IF(book_status, 'continue', 'discontinued')AS status", "book_status",
        );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array("tbl03_category", "tbl06_book.category_id = tbl03_category.id");
        $join2	  = array("tbl04_agent", "tbl06_book.agent_id = tbl04_agent.id");
        $data['books'] = $this->book_model->get_books($fields,$criteria,$page,$limit,'',$join1,'',$join2,'',$criteria_in);
        
        $data['pagination_html'] = $this->pagination->create_links();

        
        //var_dump($data);
        $this->load->view('sales/books_view',$data);
    }//end of function

    //actions
    
    public function add_sales() {
        //var_dump($_POST); 
        $is_retail = $this->input->post('hddn_is_retail');
        $total_amount = $this->input->post('txt_total_amount');
        $discount     = $this->input->post('txt_discount');
        $net_amount   = $total_amount - ($total_amount * $discount / 100);
        $data_set_skus          = array();
        $data_set_books         = array();
        $data_set_sales_books   = array();
        $data_set_sales         = array(
            'total_amount' => $total_amount,
            'discount'     => $discount,
            'total_paid'   => $net_amount, //will change later if whole sale
            'date_sold'    => get_cur_date(),
            'user_sold'    => $this->session->userdata('user_id')
        );
        if(!$is_retail) {
            $data_set_sales['dealer_id'] = $this->input->post('lst_dealer_id');
            $data_set_sales['total_paid']= 0.00;
        }
        
        foreach($_POST as $key => $val) {
            if((boolean)preg_match("/hddn_sales_sku_/", $key)) {  //book is selected
                //set variables
                $book_id    = $val;
                
                //add entries to arrays
                array_push($data_set_skus,$book_id);
                array_push($data_set_books,array(
                    'sku'       => $book_id,
                    'last_sold' => get_cur_date(),
                    'cur_stock' => $this->input->post("hddn_quantity_sold_{$book_id}") //the exisitng stock will be added
                ));
                array_push($data_set_sales_books,array(
                    'sales_id'      => NULL,          //will be set after adding the sales
                    'sales_sku'     => $book_id,
                    'discount'      => $this->input->post("hddn_discount_{$book_id}"),
                    'list_price'    => $this->input->post("hddn_list_price_{$book_id}"),
                    'quantity_sold' => $this->input->post("hddn_quantity_sold_{$book_id}")
                ));

            }//if
        }//for
        //var_dump($data_set_sales);
        //var_dump($data_set_sales_books);
        //var_dump($data_set_books);
        
        $this->db->trans_start();

            //get current stocks
        $fields = array('sku','cur_stock');
        $criteria_in = array('sku',$data_set_skus);
        $books = $this->book_model->get_books($fields,'','','','','','','','',$criteria_in);
        $books = $this->application->array_set_index($books,'sku');
            //var_dump($books);

            //update current stock
        for($i = 0; $i < count($data_set_books); $i++) {
            $data_set_books[$i]['cur_stock'] = $books[$data_set_books[$i]['sku']]['cur_stock'] - $data_set_books[$i]['cur_stock'];
        }
            //var_dump($data_set_books);
        
            //insert sales
        $sales_id = $this->sales_model->add_sale($data_set_sales);

            //insert sales id in to sales books
        for($i = 0; $i < count($data_set_sales_books); $i++) {
            $data_set_sales_books[$i]['sales_id'] = $sales_id ;
        }

            //insert sales books
        $this->sales_book_model->add_sales_books($data_set_sales_books);

            //update books/stock
        $criteria = 'sku';
        $this->book_model->update_books($data_set_books,$criteria);

        if($is_retail) {
                //insert financial
            $data_set_financial = array(
                'trans_category' => 'sales',
                'description' => "Add retail sales: {$sales_id}",
                'amount' => $net_amount,
                'date_made' => get_cur_date_time(),
                'user_made' => $this->session->userdata('user_id')
            );
            $this->financial_model->add_transaction($data_set_financial);
            } else { //wholesale
                //update dealers debit
                $fields   = array('debit_amount');
                $criteria = array('id' => $this->input->post('lst_dealer_id'));
                $dealer   = $this->dealer_model->get_dealer($fields,$criteria);
                $data_set_dealer = array(
                    'debit_amount' => $dealer['debit_amount'] + $net_amount
                );
                $this->dealer_model->update_dealer($data_set_dealer,$criteria);
            }
            
            $this->db->trans_complete();

            $query_status = $this->db->trans_status();
            if($query_status) {

                //insert system log entry
                $description = "Add sales: {$sales_id}.";
                $this->application->write_log('sales', $description);
                
                //prepare notifications
                $url = site_url('reports/sales_invoice/'. $sales_id);
                if($is_retail) {
                    $url .= '/1/' . $this->input->post('txt_payment');
                } else {
                    $url .= '/0';
                }
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The new sales is added successfully.&nbsp;&nbsp;<a href='{$url}' target='_blank' ><button type='button' class='btn btn-success btn-xs'>Print Sales Invoice</button></a>"
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new sales.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('sales');

    }//end of function

    public function sales_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('sales_is_search',true);
        $this->session->set_userdata('sales_search_key',$key);
        $this->session->set_userdata('sales_search_value',$val);
        redirect('sales');

    }//end of function

    public function sales_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('sales_is_search');
        $this->session->unset_userdata('sales_search_key');
        $this->session->unset_userdata('sales_search_value');
        redirect('sales');

    }//end of function
    
    public function book_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('sales_books_is_search',true);
        $this->session->set_userdata('sales_books_search_key',$key);
        $this->session->set_userdata('sales_books_search_value',$val);
        redirect('sales/search');

    }//end of function

    public function book_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('sales_books_is_search');
        $this->session->unset_userdata('sales_books_search_key');
        $this->session->unset_userdata('sales_books_search_value');
        redirect('sales/search');

    }//end of function
    
    // supportive functions
    
    //get book details via AJAX | View: add_sales_view(retail and whole)
    public function get_book($sku) {
        $fields   = array(
            'sku', "CONCAT(book_title,', ',author,', ',publisher, ' [',isbn,']') AS description","book_title AS title", "book_status",
            "cur_stock","list_price","discount","ROUND(list_price - (list_price * discount /100),2) AS price_sold",
        );
        $criteria = array('sku' => $sku);
        $data = $this->book_model->get_book($fields,$criteria);
        //var_dump($data);
        if(!$data) {
            $data = array('not-exist');
        }
        echo json_encode($data);

    }//end of function
    
    //get discount via AJAX | View: add_sales_view(wholesales)
    public function get_discount($dealer_id) {
        $fields   = array('discount');
        $criteria = array('id' => $dealer_id);
        $data = $this->dealer_model->get_dealer($fields,$criteria);
        //var_dump($data);
        echo json_encode($data);

    }//end of function
    
    
//-----------------Sales Returns section ---------------

    //views
    public function sales_returns($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	
        $criteria_in	= '';	

        //set criteria if any
        if($this->session->userdata('sales_returns_is_search')) { 
            $key   = $this->session->userdata('sales_returns_search_key');
            $value = $this->session->userdata('sales_returns_search_value'); 

            if($key == 'sales_id' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid id 				
                $value = FALSE;
            } else if($key == "agent") {
                $criteria1 = array("agent_name RLIKE" => "^$value" );
                $fields1   = array("id");
                $agents    = $this->agent_model->get_agents($fields1,$criteria1);
                $key       = "tbl09_sales_return.agent_id";
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
            } else if($key == "book_title") {
                $criteria1 = array("book_title RLIKE" => "^$value" );
                $fields1   = array("sku");
                $books     = $this->book_model->get_books($fields1,$criteria1);
                $key       = "tbl09_sales_return.return_sku";
                if($books) {
                    $temp = array();
                    foreach($books as $book) {
                        array_push($temp, $book['sku']);
                    }
                    $criteria_in[0] = $key;
                    $criteria_in[1] = $temp;
                } else {
                    $value = FALSE;
                }
            } else if($key == 'DATE(tbl09_sales_return.date_added)') { 
                if(!(bool)preg_match( '/^\d{4}\-\d{1,2}\-\d{1,2}+$/',$value)) {
                    $value = FALSE;
                }
            } else if($key == 'MONTH(tbl09_sales_return.date_added)') { 
                if(!((bool)preg_match( '/^\d+$/',$value) && $value >= 1 && $value <= 12)) {
                    $value = FALSE;
                }
            } else if($key == 'return_status') {
                switch($value) {
                    case '1': $value = 'pending'; break;
                    case '2': $value = 'ordered'; break;
                    case '3': $value = 'received'; break;
                    case '4': $value = 'completed'; break;
                    case '5': $value = 'canceled'; break;
                    default : $key = 'sales_id'; $value = FALSE;
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
        $total_rows = $this->sales_return_model->get_total_rows($criteria,$criteria_in) ;
        $this->application->set_pagination($total_rows,site_url('sales/sales_returns'));

        //set data
        $fields = array(
            'tbl09_sales_return.id AS return_id',
            'sales_id', 'agent_name AS agent',"agent_status",
            'return_sku AS sku', "CONCAT(book_title,' - ',isbn) AS description","book_status",
            "quantity_returned", "quantity_given",
            "CONCAT(first_name,', ',designation) AS user_added", "tbl09_sales_return.date_added AS date_added", "IFNULL(date_com_can,'-') AS date_com_can",
            "return_status", "user_status",
            "(IF(DATE_ADD(tbl09_sales_return.date_added, INTERVAL 3 MONTH) < CURDATE() AND (return_status = 'pending' OR return_status = 'ordered'),'warning',IF(return_status = 'received','success','none'))) AS notification"
        );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array("tbl04_agent", "tbl09_sales_return.agent_id = tbl04_agent.id");
        $join2	  = array("tbl06_book", "tbl09_sales_return.return_sku = tbl06_book.sku");
        $join3	  = array("tbl01_user", "tbl09_sales_return.user_added = tbl01_user.nic");
        $data['sales_returns'] = $this->sales_return_model->get_sales_returns($fields,$criteria,$page,$limit,'',$join1,'',$join2,$join3,$criteria_in);
        
        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('sales/sales_returns_view',$data);
    }//end of funtcion
    
    public function sales_return_add($sales_id) {
        $data = $this->application->set_data();
        $data['module']    = $this->module;
        
        //prepare data-set
        $fields   = array('tbl07_sales.id AS sales_id', "IF(ISNULL(dealer_id),'Retail',dealer_name) AS dealer", 'date_sold');
        $criteria = array('tbl07_sales.id' => $sales_id);
        $join1    = array('tbl05_dealer','tbl07_sales.dealer_id = tbl05_dealer.id');
        $data['sales'] = $this->sales_model->get_sale($fields,$criteria,$join1);
        
        $stock_allowed = $this->config->item('max_sales_return_precentage');
        $fields = array(
            'sales_sku AS sku', "CONCAT(book_title,' - ', isbn) AS description", 'book_status', 'tbl06_book.agent_id AS agent_id', 
            'quantity_sold', "FLOOR(cur_stock * {$stock_allowed} / 100)AS max_allowed_stock",
            'IFNULL(SUM(quantity_returned - quantity_given),0) AS pending_quantity',
            'IFNULL(quantity_sold - SUM(quantity_returned - quantity_given),quantity_sold) AS max_returnable_quantity'
        );
        $criteria = array('tbl08_sales_book.sales_id' => $sales_id);
        $join1    = array('tbl06_book', 'tbl08_sales_book.sales_sku = tbl06_book.sku');
        $join2    = array('tbl09_sales_return', "tbl08_sales_book.sales_sku = tbl09_sales_return.return_sku AND tbl09_sales_return.sales_id ='{$sales_id}' AND (tbl09_sales_return.return_status = 'pending' OR tbl09_sales_return.return_status = 'received')");
        $order_by = 'tbl08_sales_book.sales_sku ASC';
        $group_by = array('tbl08_sales_book.sales_sku','`tbl08_sales_book`.`sales_sku`','description','book_status','agent_id','quantity_sold','max_allowed_stock');

        $data['sales_books'] = $this->sales_book_model->get_sales_books($fields,$criteria,'','',$order_by,$join1,$group_by,$join2);
        
        //get agents and merge with sales_books
        $fields = array('DISTINCT(agent_id)');
        $order_by  = 'agent_id ASC';
        $agent_ids = $this->sales_book_model->get_sales_books($fields,$criteria,'','',$order_by,$join1);
        $temp = array();
        foreach($agent_ids AS $agent_id) {
            array_push($temp, $agent_id['agent_id']);
        }
        $agent_ids = $temp;
        //var_dump($agent_ids);

        $fields = array('id AS agent_id', 'agent_name', 'agent_status');
        $criteria_in  = array('id',$agent_ids);
        $order_by  = 'id ASC';
        $agents = $this->agent_model->get_agents($fields,'','','',$order_by,'','','','',$criteria_in);
        $agents = $this->application->array_set_index($agents,'agent_id');
        //var_dump($agents);
        
        for($i = 0; $i < count($data['sales_books']); $i++) {
            $data['sales_books'][$i]['agent_name']   = $agents[$data['sales_books'][$i]['agent_id']]['agent_name'];
            $data['sales_books'][$i]['agent_status'] = $agents[$data['sales_books'][$i]['agent_id']]['agent_status'];
        }
        
        //var_dump($data);
        $this->load->view('sales/add_sales_return_view',$data);
    }//end of function
    
    //actions
    public function add_sales_return() {
        //var_dump($_POST);

        //prepare data sets
        $data_set_sales_returns = array();
        $data_set_books         = array(); //to update stock for given quantities
        $ids                    = array();// for get books to update
        foreach($_POST as $key => $val) {
            $id = $val;
            if((boolean)preg_match("/chk_select_book_/", $key)) {  //book is selected
                array_push($data_set_sales_returns,array(
                    'sales_id'          => $this->input->post('hddn_sales_id'),
                    'agent_id'          => $this->input->post("hddn_agent_id_{$id}"),
                    'return_sku'        => $id,
                    'quantity_returned' => $this->input->post("txt_quantity_returned_{$id}"),
                    'quantity_given'    => $this->input->post("txt_quantity_given_{$id}"),
                    'user_added'        => $this->session->userdata('user_id'),
                    'date_added'        => get_cur_date()
                ));
                if((int)$this->input->post("txt_quantity_given_{$id}") > 0) { //book has given
                    array_push($data_set_books,array(
                        'sku'       => $id,
                        'cur_stock' => $this->input->post("txt_quantity_given_{$id}") //will be reduced by the exisitng stock 
                    ));
                }

            }//if
        }//for
        if($data_set_books) {
            foreach($data_set_books AS $book) {
                array_push($ids, $book['sku']);
            }
        }
        //var_dump($data_set_sales_returns);var_dump($data_set_books);var_dump($ids);
        
        $this->db->trans_start();

            //insert sales returns
        $this->sales_return_model->add_sales_returns($data_set_sales_returns);

            //update stocks if any given books
        if($data_set_books) {
                //get book data
            $fields      = array('sku','cur_stock');
            $criteria_in = array('sku',$ids);
            $books = $this->book_model->get_books($fields,'','','','','','','','',$criteria_in);
            $books = $this->application->array_set_index($books,'sku');
                //var_dump($books);

                //calculate current stock
            for($i = 0; $i < count($data_set_books); $i++) {
                $data_set_books[$i]['cur_stock'] = $books[$data_set_books[$i]['sku']]['cur_stock'] - $data_set_books[$i]['cur_stock'];
            }
                //var_dump($data_set_books);

                //update books/stock
            $criteria = 'sku';
            $this->book_model->update_books($data_set_books,$criteria);
            }//if

            $this->db->trans_complete();

            $query_status = $this->db->trans_status();
            if($query_status) {
            //insert system log entry
                $description = "Add sales returns for sales: " . $this->input->post('hddn_sales_id');
                $this->application->write_log('sales', $description);

                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The new sales return is added successfully."
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new sales return.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('sales/sales_returns');
    }//end of function
    
    public function complete_sales_return($return_id) {

        $data_set_return = array(
            'return_status'   => 'completed',
            'date_com_can'    => get_cur_date()
        );
        $criteria = array('id' => $return_id);
        $query_status = $this->sales_return_model-> update_sales_return($data_set_return, $criteria);

        if($query_status) {
            //insert system log entry
            $description = "Complete sales return: {$return_id}.";
            $this->application->write_log('sales', $description);
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The sales return is completed successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated completing the sales return.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('sales/sales_returns');

    }//end of function
    
    public function cancel_sales_return($return_id) {

        $data_set_return = array(
            'return_status'   => 'canceled',
            'date_com_can'    => get_cur_date()
        );
        $criteria = array('id' => $return_id);
        $query_status = $this->sales_return_model-> update_sales_return($data_set_return, $criteria);

        if($query_status) {
            //insert system log entry
            $description = "Cancel sales return: {$return_id}.";
            $this->application->write_log('sales', $description);
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The sales return is canceled successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the sales return.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('sales/sales_returns');

    }//end of function
    
    //supportive
    public function sales_return_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('sales_returns_is_search',true);
        $this->session->set_userdata('sales_returns_search_key',$key);
        $this->session->set_userdata('sales_returns_search_value',$val);
        redirect('sales/sales_returns');

    }//end of function

    public function sales_return_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('sales_returns_is_search');
        $this->session->unset_userdata('sales_returns_search_key');
        $this->session->unset_userdata('sales_returns_search_value');
        redirect('sales/sales_returns');

    }//end of function

} //end of class
//end of file
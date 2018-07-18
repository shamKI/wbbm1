<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase extends CI_Controller {
    
    private $module = 'purchase';
    
    public function __construct() {
        parent::__construct();
        $this->application->level3();
        $this->load->model('purchase/requisition_model');
        $this->load->model('purchase/purchase_model');
        $this->load->model('purchase/purchase_book_model');
        $this->load->model('purchase/purchase_return_model');
        $this->load->model('purchase/purchase_return_sales_return_model');
        $this->load->model('stock/agent_model');
        $this->load->model('stock/book_model');
        $this->load->model('sales/sales_return_model');
    }
//----------------- purchase Section -------------------
    //views
    
    public function index($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	
        $criteria_in	= '';	

        //set criteria if any
        if($this->session->userdata('purchases_is_search')) { 
            $key   = $this->session->userdata('purchases_search_key');
            $value = $this->session->userdata('purchases_search_value'); 

            if($key == 'tbl11_purchase.id' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid id 				
                $value = FALSE;
            } else if($key == "agent") {
                $criteria1 = array("agent_name RLIKE" => "^$value" );
                $fields1   = array("id");
                $agents    = $this->agent_model->get_agents($fields1,$criteria1);
                $key       = "tbl11_purchase.agent_id";
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
            }else if($key == 'purchase_status') {
                switch ($value){
                    case '1' : $value = 'ordered' ; break; 
                    case '2' : $value = 'completed' ; break; 
                    case '3' : $value = 'canceled' ; break; 
                    default  : $key = 'tbl11_purchase.id';$value = FALSE;
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
        $total_rows = $this->purchase_model->get_total_rows($criteria,$criteria_in) ;
        $this->application->set_pagination($total_rows,site_url('purchase/index'));

        //set data
        $fields = array(
            "tbl11_purchase.id AS purchase_id",
            "agent_name AS agent", "agent_status",
            "IFNULL(invoice_no,' - ') AS invoice_no", "total_paid", "discount",
            "ROUND(total_amount-(total_amount*discount/100),2) AS net_amount",
            "ROUND((total_amount-(total_amount*discount/100)) - total_paid,2) AS credits",
            "date_ordered", "IFNULL(date_com_can,' - ') AS date_com_can",
            "purchase_status", "IF(purchase_status = 'ordered', TRUE, FALSE) AS is_com_can",
            "IF(DATE_ADD(date_ordered, INTERVAL 3 MONTH) < CURDATE() AND purchase_status = 'ordered','warning',IF(purchase_status='completed' AND DATE_ADD(date_com_can, INTERVAL 1 MONTH) < CURDATE() AND (((total_amount-(total_amount*discount/100)) - total_paid) > 0),'error','none')) AS notification"
        );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array("tbl04_agent", "tbl11_purchase.agent_id = tbl04_agent.id");
        $data['purchases'] = $this->purchase_model->get_purchases($fields,$criteria,$page,$limit,'',$join1,'','','',$criteria_in);
        
        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('purchase/purchases_view',$data);
    }//end of function

    public function add() {
        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get current agents
        $fields   = array('id', 'agent_name');
        $criteria = array('agent_status' => TRUE);
        $data['agents'] = $this->agent_model->get_agents($fields,$criteria);
        
        //var_dump($data);
        $this->load->view('purchase/add_purchase_view',$data);

    }//end of function
    
    public function complete($purchase_id) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get purchase details
        $fields = array(
            "tbl11_purchase.id AS purchase_id", "agent_id",
            "agent_name AS agent", "agent_status", "credit_amount AS credits"
        );
        $criteria = array('tbl11_purchase.id' => $purchase_id);
        $join1	  = array("tbl04_agent", "tbl11_purchase.agent_id = tbl04_agent.id");
        $data['purchase'] = $this->purchase_model->get_purchase($fields,$criteria,$join1);
        
        //get purchase books details
        $fields = array(
            "purchase_book_id","purchase_sku AS sku",
            "CONCAT(book_title,', ',isbn,'<br/>',author,', ',publisher) AS description",
            "quantity_ordered", "price_ordered"
        );
        $criteria = array('purchase_id' => $purchase_id);
        $order_by = 'purchase_book_id ASC';
        $join1	  = array("tbl06_book", "purchase_sku = sku");
        $data['purchase_books'] = $this->purchase_book_model->get_purchase_books($fields,$criteria,'','',$order_by,$join1);
        
        //var_dump($data);
        $this->load->view('purchase/complete_purchase_view',$data);

    }//end of function
    
    public function view($purchase_id) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get purchase details
        $fields = array(
            "tbl11_purchase.id AS purchase_id",
            "agent_name AS agent", "agent_status",
            "IFNULL(invoice_no,' - ') AS invoice_no", "total_amount", "total_paid","ROUND(total_amount-(total_amount*discount/100) - total_paid,2) AS credits",
            "discount","ROUND(total_amount-(total_amount*discount/100),2) AS net_amount",
            "date_ordered", "CONCAT(first_name,' ',last_name,', ',designation)AS user_ordered", "user_status",
            "IFNULL(date_com_can,' - ') AS date_com_can",
            "purchase_status", "IF(purchase_status = 'ordered', TRUE, FALSE) AS is_com_can"
        );
        $criteria = array('tbl11_purchase.id' => $purchase_id);
        $join1	  = array("tbl04_agent", "tbl11_purchase.agent_id = tbl04_agent.id");
        $join2	  = array("tbl01_user", "tbl11_purchase.user_ordered = tbl01_user.nic");
        $data['purchase'] = $this->purchase_model->get_purchase($fields,$criteria,$join1,$join2);
        
        //get purchase books details
        $fields = array(
            "purchase_sku AS sku",
            "CONCAT(book_title,', ',isbn,'<br/>',author,', ',publisher) AS description", "book_status",
            "quantity_ordered", "price_ordered","ROUND(quantity_ordered * price_ordered,2) AS amount_ordered",
            "quantity_purchased", "price_purchased","ROUND(quantity_purchased * price_purchased,2) AS amount_purchased",
        );
        $criteria = array('purchase_id' => $purchase_id);
        $order_by = 'purchase_sku ASC';
        $join1	  = array("tbl06_book", "purchase_sku = sku");
        $data['purchase_books'] = $this->purchase_book_model->get_purchase_books($fields,$criteria,'','',$order_by,$join1);
        
        //get grand totals
        $fields = array(
            "SUM(quantity_ordered) AS total_quantity_ordered",
            "SUM(quantity_purchased) AS total_quantity_purchased",
            "ROUND(SUM(quantity_ordered * price_ordered),2) AS total_amount_ordered",
            "ROUND(SUM(quantity_purchased * price_purchased),2) AS total_amount_purchased",
        );
        $criteria = array('purchase_id' => $purchase_id);
        $data['grand_totals'] = $this->purchase_book_model->get_purchase_book($fields,$criteria);
        
        //get payments
        $this->load->model('financial/payment_model');
        $fields = array(
            'DATE(date_made) AS date_made', 'tbl16_payment.amount AS amount',
            "IFNULL(cheque_no,'-') AS cheque_no",'trans_status'
        );
        $criteria = array(
            'is_purchase'       => TRUE,
            'sales_purchase_id' => $purchase_id
        );
        $order_by = 'tbl16_payment.id ASC';
        $join1 = array('tbl15_financial','tbl16_payment.trans_id = tbl15_financial.id');
        $data['payments'] = $this->payment_model->get_payments($fields,$criteria,'','',$order_by,$join1);
        //var_dump($data);
        $this->load->view('purchase/view_purchase_view',$data);

    }//end of function
    
    //actions
    
    public function add_purchase() {
        //var_dump($_POST); 
        
        $data_set_books          = array();
        $data_set_purchase_books = array();
        $data_set_purchse        = array(
            'agent_id'      => $this->input->post('lst_agent_id'),
            'user_ordered'  => $this->session->userdata('user_id'),
            'date_ordered'  => get_cur_date()
        );
        
        foreach($_POST as $key => $val) {
            if((boolean)preg_match("/chk_select_book_/", $key)) {  //book is selected
                //set variables
                $book_id    = $val;
                
                //add entries to arrays
                array_push($data_set_books,array(
                    'sku'       => $book_id,
                    'is_ordered'=> TRUE
                ));
                array_push($data_set_purchase_books,array(
                    'purchase_id' => NULL,          //will be set after adding the purhcase
                    'purchase_sku' => $book_id,
                    'quantity_ordered' => $this->input->post("txt_quantity_ordered_{$book_id}"),
                    'price_ordered' => $this->input->post("hddn_purchase_price_{$book_id}"),
                ));
                         
            }//if
        }//for
        //var_dump($data_set_purchse);
        //var_dump($data_set_purchase_books);
        //var_dump($data_set_books);
        
        $this->db->trans_start();
            
            //insert purchase
            $purchase_id = $this->purchase_model->add_purchase($data_set_purchse);
            
            //insert purchase id in to purchase books
            for($i = 0; $i < count($data_set_purchase_books); $i++) {
                $data_set_purchase_books[$i]['purchase_id'] = $purchase_id ;
            }
            
            //insert purchase books
            $this->purchase_book_model->add_purchase_books($data_set_purchase_books);
            
            //update books
            $criteria = 'sku';
            $this->book_model->update_books($data_set_books,$criteria);
            
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        if($query_status) {

                //insert system log entry
                $description = "Add Purchase: {$purchase_id}.";
                $this->application->write_log('purchase', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The new purchase order is added successfully.&nbsp;&nbsp;<a href='" . site_url('reports/purchase_order/'. $purchase_id) . "' target='_blank' ><button type='button' class='btn btn-success btn-xs'>Print Purchase Order</button></a>"
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new purchase order.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('purchase');
    }//end of function

    public function complete_purchase($purchase_id) {
        //var_dump($_POST);
        
        //prepare data set - agent
        $data_set_agent = array(
            'id'            => $this->input->post('hddn_agent_id'),
            'credit_amount' => $this->input->post('hddn_credits') + $this->input->post('txt_net_amount')
        ); 
        //prepare data set - purchase
        $data_set_purchase = array(
            'date_com_can'    => get_cur_date(),
            'invoice_no'      => $this->input->post('txt_invoice_no'),
            'discount'        => $this->input->post('txt_discount'),
            'total_amount'    => $this->input->post('txt_total_amount'),
            'purchase_status' => 'completed'
        ); 
        
        $data_set_skus  = array();
        $data_set_books = array();
        $data_set_purchase_books = array();
        
        foreach($_POST as $key => $val) {
            if((boolean)preg_match("/chk_select_book_/", $key)) {  //book is selected
                //set variables
                $id  = $val;
                $sku = $this->input->post("hddn_purchase_sku_{$id}");
                //add entries to arrays
                array_push($data_set_skus,$sku);
                array_push($data_set_books,array(
                    'sku'            => $sku,
                    'cur_stock'      => $this->input->post("txt_quantity_purchased_{$id}"), //the exisitng stock will be added
                    'purchase_price' => $this->input->post("txt_price_purchased_{$id}"),
                    'is_ordered'     => FALSE
                ));
                array_push($data_set_purchase_books,array(
                    'purchase_book_id'   => $id,
                    'quantity_purchased' => $this->input->post("txt_quantity_purchased_{$id}"),
                    'price_purchased'    => $this->input->post("txt_price_purchased_{$id}"),
                ));
            }//if
        }//for
        //var_dump($data_set_skus);var_dump($data_set_agent); var_dump($data_set_books);var_dump($data_set_purchase);var_dump($data_set_purchase_books);
        
        $this->db->trans_start();
            
            //get current stocks
            $fields = array('sku','cur_stock');
            $criteria_in = array('sku',$data_set_skus);
            $books = $this->book_model->get_books($fields,'','','','','','','','',$criteria_in);
            $books = $this->application->array_set_index($books,'sku');
            //var_dump($books);
            
            //update current stock
            for($i = 0; $i < count($data_set_books); $i++) {
                $data_set_books[$i]['cur_stock'] = $data_set_books[$i]['cur_stock'] + $books[$data_set_books[$i]['sku']]['cur_stock'];
            }
            //var_dump($data_set_books);
            
            //update agent
            $criteria = array('id' => $this->input->post('hddn_agent_id'));
            $this->agent_model->update_agent($data_set_agent,$criteria);
            //update purchase
            $criteria = array('id' => $purchase_id);
            $this->purchase_model->update_purchase($data_set_purchase,$criteria);
            //update purchase books
            $criteria = 'purchase_book_id';
            $this->purchase_book_model->update_purchase_books($data_set_purchase_books,$criteria);
            //update books
            $criteria = 'sku';
            $this->book_model->update_books($data_set_books,$criteria);
            
        $this->db->trans_complete();
        $query_status = $this->db->trans_status();
        
        if($query_status) {

                //insert system log entry
                $description = "Complete Purchase: {$purchase_id}.";
                $this->application->write_log('purchase', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The purchase is completed successfully.&nbsp;&nbsp;<a href='" . site_url('reports/grn/'. $purchase_id) . "' target='_blank' ><button type='button' class='btn btn-success btn-xs'>Print GRN</button></a>"
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated completing the purchase.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('purchase');
    
    }//end of function
    
    public function cancel_purchase($purchase_id) {
        
        //prepare data set - books
        $data_set_books = array();
        $fields   = array('purchase_sku AS sku');
        $criteria = array('purchase_id' => $purchase_id);
        $skus     = $this->purchase_book_model->get_purchase_books($fields, $criteria);
        foreach ($skus as $sku) {
            array_push($data_set_books,array(
                'sku'       => $sku['sku'],
                'is_ordered'=> FALSE
            ));
        }
        //var_dump($data_set_books);
        
        //prepare data set - purchase
        $data_set_purchase = array(
            'purchase_status'	=> 'canceled',
            'date_com_can'      => get_cur_date()
        );
        //var_dump($data_set_purchase);
    
        $this->db->trans_start();
            //update purchase
            $criteria = array('id' => $purchase_id);
            $this->purchase_model->update_purchase($data_set_purchase,$criteria);
            //update books
            $criteria = 'sku';
            $this->book_model->update_books($data_set_books,$criteria);
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        
        if($query_status) {
            //write system log
            $description = "Cancel Purchase : {$purchase_id}.";
            $this->application->write_log('purchase', $description);

            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The purchase order is canceled successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the order.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('purchase');
    
    }//end of function

    public function purchase_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('purchases_is_search',true);
        $this->session->set_userdata('purchases_search_key',$key);
        $this->session->set_userdata('purchases_search_value',$val);
        redirect('purchase');

    }//end of function

    public function purchase_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('purchases_is_search');
        $this->session->unset_userdata('purchases_search_key');
        $this->session->unset_userdata('purchases_search_value');
        redirect('purchase');

    }//end of function
    
    // supportive functions
    
    //get low stock books for specified agent via AJAX | View: add_purchase_view
    public function get_lowstock_books($agent_id) {
        $fields   = array(
            'sku', "CONCAT(book_title,', ',isbn,'<br/>',author,', ',publisher) AS description",
            "(max_stock - cur_stock) AS max_allowed_stock", 'cur_stock', 'purchase_price',
            "IFNULL(last_sold,'-') AS last_sold"
        );
        $criteria = "agent_id = '{$agent_id}' AND book_status = TRUE AND cur_stock <= min_stock AND is_ordered = FALSE";
        $data = $this->book_model->get_books($fields,$criteria);
        //var_dump($data);
        echo json_encode($data);
    }//end of function
   
//----------------- Requisitions section ---------------

    //views
    public function requisitions($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';
        
        //set criteria if any
        if($this->session->userdata('requisitions_is_search')) { 
            $key   = $this->session->userdata('requisitions_search_key');
            $value = $this->session->userdata('requisitions_search_value'); 

            if($key == 'requisition_status') {
                switch ($value){
                    case '1' : $value = 'pending' ; break; 
                    case '2' : $value = 'completed' ; break; 
                    case '3' : $value = 'canceled' ; break; 
                    default  : $key = 'tbl10_requisition.id';$value = FALSE;
                }
            } else if($key == 'DATE(date_added)') { 
                if(!(bool)preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}+$/',$value)) {
                    $value = FALSE;
                }
            } else if($key == 'MONTH(date_added)') { 
                if(!((bool)preg_match('/^\d+$/',$value) && $value >= 1 && $value <= 12)) {
                    $value = FALSE;
                }
            }
            $criteria = array($key => $value);
        }
        
        //set notification if any
        if($this->session->flashdata('is_notification')) {
            $data['is_notification']		= $this->session->flashdata('is_notification');
            $data['notification_type']		= $this->session->flashdata('notification_type');
            $data['notification_description']   = $this->session->flashdata('notification_description');
        }

        //set pagination
        $total_rows = $this->requisition_model->get_total_rows($criteria) ;
        $this->application->set_pagination($total_rows,site_url('purchase/requisitions'));

        //prepare data
        $fields = array(
            'id', 'note', 'requisition_status AS status', 'date_added', "IFNULL(date_com_can,' - ') AS date_com_can",
            "CONCAT(first_name,' ',last_name,', ',designation)AS user_added", "user_status",
            "IF(DATE_ADD(date_added, INTERVAL 3 MONTH) < CURDATE() AND requisition_status = 'pending','warning','none') AS notification"
        );
        $limit = $this->config->item('rows_per_page');
        $join1 = array('tbl01_user','tbl10_requisition.user_added = tbl01_user.nic');
        $data['requisitions'] = $this->requisition_model->get_requisitions($fields, $criteria, $page, $limit, '', $join1);
        
        $data['pagination_html'] = $this->pagination->create_links();
        
        //var_dump($data);
        $this->load->view('purchase/requisitions_view',$data);
    }//end of funtcion
    
    //actions
    public function add_requisition() {
        //var_dump($_POST);
        
        //insert requisition
        $data_set = array(
            'note'       => $this->input->post('txt_note'),
            'user_added' => $this->session->userdata('user_id'),
            'date_added' => get_cur_date()
        );
        $query_status = $this->requisition_model->add_requisition($data_set);
        if($query_status) {
            //insert system log entry
            $description = "Add requisiton: {$query_status}.";
            $this->application->write_log('purchase', $description);
            //prepare notifications
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'success',
                'notification_description'  => 'The new requisition is added successfully.'
            );
        } else {
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated adding the new requisiton.'
            );
        }
        $this->session->set_flashdata($notification);
        redirect('purchase/requisitions');
    }//end of function
    
    public function cancel_requisition($requisition_id) {

        //update category status as incative
        $criteria = array('id' => $requisition_id);
        $data_set = array(
            'requisition_status' => 'canceled',
            'date_com_can'       => get_cur_date()
        );
        $query_status = $this->requisition_model->update_requisition($data_set,$criteria);

        if($query_status) {
            //insert system log entry
            $description = "Cancel requisiton: {$requisition_id}.";
            $this->application->write_log('purchase', $description);
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The requisition is canceled successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the requisition.'
            );	
        }
        $this->session->set_flashdata($notification);
        //redirect
        redirect('purchase/requisitions');

    }//end of function

    public function complete_requisition($requisition_id) {

        //update category status as 'active'
        $criteria = array('id' => $requisition_id);
        $data_set = array(
            'requisition_status' => 'completed',
            'date_com_can'       => get_cur_date()
        );
        $query_status = $this->requisition_model->update_requisition($data_set,$criteria);

        if($query_status) {
            //insert system log entry
            $description = "Complete requisiton: {$requisition_id}.";
            $this->application->write_log('purchase', $description);
            //prepare notifications
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'success',
                'notification_description'  => 'The requisition is completed successfully.'
            );
        }else{
            $notification = array(
                'is_notification'           => true,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated completing the requisition.'
            );	
        }

        $this->session->set_flashdata($notification);
        //redirect
        redirect('purchase/requisitions');

    }//end of function
    
    public function requisition_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('requisitions_is_search',true);
        $this->session->set_userdata('requisitions_search_key',$key);
        $this->session->set_userdata('requisitions_search_value',$val);
        redirect('purchase/requisitions');

    }//end of function

    public function requisition_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('requisitions_is_search');
        $this->session->unset_userdata('requisitions_search_key');
        $this->session->unset_userdata('requisitions_search_value');
        redirect('purchase/requisitions');

    }//end of function
    
//----------------- purchase return Section ---------------
    //views
    public function purchase_returns($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	
        $criteria_in	= '';	

        //set criteria if any
        if($this->session->userdata('purchase_returns_is_search')) { 
            $key   = $this->session->userdata('purchase_returns_search_key');
            $value = $this->session->userdata('purchase_returns_search_value'); 

            if($key == 'tbl13_purchase_return.id' && !(bool)preg_match('/^[0-9]+$/', $value)) { //invalid id 				
                $value = FALSE;
            } else if($key == "agent") {
                $criteria1 = array("agent_name RLIKE" => "^$value" );
                $fields1   = array("id");
                $agents    = $this->agent_model->get_agents($fields1,$criteria1);
                $key       = "tbl13_purchase_return.agent_id";
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
            } else if($key == 'purchase_return_status') {
                switch ($value){
                    case '1' : $value = 'ordered' ; break; 
                    case '2' : $value = 'completed' ; break; 
                    case '3' : $value = 'canceled' ; break; 
                    default  : $key = 'tbl13_purchase_return.id';$value = FALSE;
                }
            } else if($key == 'DATE(date_ordered)') { 
                if(!(bool)preg_match( '/^\d{4}\-\d{1,2}\-\d{1,2}+$/',$value)) {
                    $value = FALSE;
                }
            } else if($key == 'MONTH(date_ordered)') { 
                if(!((bool)preg_match( '/^\d+$/',$value) && $value >= 1 && $value <= 12)) {
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
        $total_rows = $this->purchase_return_model->get_total_rows($criteria,$criteria_in) ;
        $this->application->set_pagination($total_rows,site_url('purchase/purchase_returns'));

        //set data
        $fields = array(
            "tbl13_purchase_return.id AS purchase_return_id",
            "agent_name AS agent", "agent_status",
            "date_ordered", "CONCAT(first_name,' - ',designation)AS user_ordered","user_status",
            "IFNULL(date_com_can,' - ') AS date_com_can",
            "purchase_return_status",
            "IF(DATE_ADD(date_ordered, INTERVAL 3 MONTH) < CURDATE() AND purchase_return_status = 'ordered','warning','') AS notification"
        );
        $limit    = $this->config->item('rows_per_page');
        $join1	  = array("tbl04_agent", "tbl13_purchase_return.agent_id = tbl04_agent.id");
        $join2	  = array("tbl01_user", "tbl13_purchase_return.user_ordered = tbl01_user.nic");
        $data['purchase_returns'] = $this->purchase_return_model->get_purchase_returns($fields,$criteria,$page,$limit,'',$join1,'',$join2,'',$criteria_in);
        
        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('purchase/purchase_returns_view',$data);
    }//end of function
    
    public function purchase_return_view($return_id) {
        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get purchase return details
        $fields = array(
            "tbl13_purchase_return.id AS purchase_return_id",
            "agent_name AS agent", "agent_status",
            "date_ordered", "CONCAT(first_name,' ',last_name,', ',designation)AS user_ordered", "user_status",
            "IFNULL(date_com_can,' - ') AS date_com_can",
            "purchase_return_status"
        );
        $criteria = array('tbl13_purchase_return.id' => $return_id);
        $join1	  = array("tbl04_agent", "tbl13_purchase_return.agent_id = tbl04_agent.id");
        $join2	  = array("tbl01_user", "tbl13_purchase_return.user_ordered = tbl01_user.nic");
        $data['purchase_return'] = $this->purchase_return_model->get_purchase_return($fields,$criteria,$join1,$join2);
        
        //get related sales return ids
        $fields   = array('sales_return_id');
        $criteria = array('purchase_return_id' => $return_id);
        $sales_return_ids = $this->purchase_return_sales_return_model->get_purchase_return_sales_returns($fields,$criteria);
        $sales_return_ids = $this->application->array_remove_assoc($sales_return_ids,'sales_return_id');
        
        //get sales return details
        $fields = array(
            'tbl09_sales_return.id AS return_id',
            'sales_id',
            'return_sku AS sku', "CONCAT(book_title,' - ',isbn) AS description","book_status",
            "quantity_returned",
        );
        $criteria_in = array('tbl09_sales_return.id',$sales_return_ids);
        $order_by = 'tbl09_sales_return.id ASC';
        $join1	  = array("tbl06_book", "tbl09_sales_return.return_sku = tbl06_book.sku");
        $data['sales_returns'] = $this->sales_return_model->get_sales_returns($fields,'','','',$order_by,$join1,'','','',$criteria_in);
        
        //var_dump($data);
        $this->load->view('purchase/view_purchase_return_view',$data);
    }//end of function
    
    public function purchase_return_add() {
        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get agents
        $fields   = array('id', 'agent_name', 'agent_status');
        $data['agents'] = $this->agent_model->get_agents($fields);
        
        //var_dump($data);
        $this->load->view('purchase/add_purchase_return_view',$data);
    }//end of function
    
    //actions
    public function add_purchase_return() {
        //var_dump($_POST);
        
        //prepare data sets
        $data_set_returns = array();
        $data_set_purchase_returns_sales_returns = array();
        $data_set_purchase_return = array(
            'agent_id'     => $this->input->post('lst_agent_id'),
            'user_ordered' => $this->session->userdata('user_id'),
            'date_ordered' => get_cur_date()
        );
        
        foreach($_POST as $key => $val) {
            if((boolean)preg_match("/chk_select_return_/", $key)) {  //sales return is selected
                $sales_return_id  = $val;
                
                array_push($data_set_returns,array(
                    'id'             => $sales_return_id,
                    'return_status'  => 'ordered'
                ));
                array_push($data_set_purchase_returns_sales_returns,array(
                    'purchase_return_id' => NULL,  //will be set later
                    'sales_return_id'    => $sales_return_id
                ));
            }//if
        }//for
        $this->db->trans_start();
            
            //insert purchase
            $purchase_return_id = $this->purchase_return_model->add_purchase_return($data_set_purchase_return);
            
            //insert purchase return id in to purchase returns sales returns
            for($i = 0; $i < count($data_set_purchase_returns_sales_returns); $i++) {
                $data_set_purchase_returns_sales_returns[$i]['purchase_return_id'] = $purchase_return_id;
            }
            
            //insert purchase returns - sales returns
            $this->purchase_return_sales_return_model->add_purchase_returns_sales_returns($data_set_purchase_returns_sales_returns);
            
            //update sales returns
            $criteria = 'id';
            $this->sales_return_model->update_sales_returns($data_set_returns,$criteria);
            
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        if($query_status) {

                //insert system log entry
                $description = "Add Purchase return: {$purchase_return_id}.";
                $this->application->write_log('purchase', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The new purchase return order is added successfully.&nbsp;&nbsp;<a href='" . site_url('reports/purchase_return_order/'. $purchase_return_id) . "' target='_blank' ><button type='button' class='btn btn-success btn-xs'>Print Purchase Return Order</button></a>"
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the new purchase return order.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('purchase/purchase_returns');
        
    }//end of function
    
    public function cancel_purchase_return($return_id) {
        //prepare data sets
        $data_set_purchase_return = array(
            'purchase_return_status' => 'canceled',
            'date_com_can'           => get_cur_date()
        );
        $fields   = array('sales_return_id AS id');
        $criteria = array('purchase_return_id' => $return_id);
        $data_set_sales_returns = $this->purchase_return_sales_return_model->get_purchase_return_sales_returns($fields,$criteria);
        for($i = 0; $i < count($data_set_sales_returns); $i++) {
            $data_set_sales_returns[$i]['return_status'] = 'pending';
        }
        //var_dump($data_set_purchase_return);var_dump($data_set_sales_returns);
        
        $this->db->trans_start();
            
            //update purchase return
            $criteria = array('id' => $return_id);
            $this->purchase_return_model->update_purchase_return($data_set_purchase_return,$criteria);
            
            //update sales returns
            $criteria = 'id';
            $this->sales_return_model->update_sales_returns($data_set_sales_returns,$criteria);
            
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        if($query_status) {

                //insert system log entry
                $description = "Cancel purchase return: {$return_id}.";
                $this->application->write_log('purchase', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The purchase return is canceled successfully."
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated cancelling the new purchase return.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('purchase/purchase_returns');
        
        
    }//end of function
    
    public function complete_purchase_return($return_id) {
        //prepare data sets
        $data_set_purchase_return = array(
            'purchase_return_status' => 'completed',
            'date_com_can'           => get_cur_date()
        );
        $cur_date = get_cur_date();
        $fields   = array(
            'sales_return_id AS id',
            "IF(quantity_returned = quantity_given,'completed','received') AS return_status",
            "IF(quantity_returned = quantity_given,'{$cur_date}',NULL) AS date_com_can"
        );
        $criteria = array('purchase_return_id' => $return_id);
        $join1    = array('tbl09_sales_return','tbl14_purchase_return_sales_return.sales_return_id = tbl09_sales_return.id');
        $data_set_sales_returns = $this->purchase_return_sales_return_model->get_purchase_return_sales_returns($fields,$criteria,'','','',$join1);
        //var_dump($data_set_purchase_return);var_dump($data_set_sales_returns);
        
        $this->db->trans_start();
            
            //update purchase return
            $criteria = array('id' => $return_id);
            $this->purchase_return_model->update_purchase_return($data_set_purchase_return,$criteria);
            
            //update sales returns
            $criteria = 'id';
            $this->sales_return_model->update_sales_returns($data_set_sales_returns,$criteria);
        
            //get cur stock
            $sales_return_ids = $this->application->array_remove_assoc($data_set_sales_returns,'id');
            $fields = array(
                'return_sku AS sku',
                '(cur_stock + SUM(quantity_given))AS cur_stock'
            );
            $criteria_in = array('tbl09_sales_return.id', $sales_return_ids);
            $join1       = array('tbl06_book','return_sku = sku');
            $group_by = array('return_sku');
            $data_set_books = $this->sales_return_model->get_sales_returns($fields,'','','','',$join1,$group_by,'','',$criteria_in);
            //var_dump($data_set_books);
            
            //update stock
            $criteria = 'sku';
            $this->book_model->update_books($data_set_books,$criteria);
            
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        if($query_status) {

                //insert system log entry
                $description = "Complete purchase return: {$return_id}.";
                $this->application->write_log('purchase', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The purchase return is completed successfully."
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated completing the new purchase return.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('purchase/purchase_returns');
        
    }//end of function
    
    public function purchase_return_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('purchase_returns_is_search',true);
        $this->session->set_userdata('purchase_returns_search_key',$key);
        $this->session->set_userdata('purchase_returns_search_value',$val);
        redirect('purchase/purchase_returns');

    }//end of function

    public function purchase_return_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('purchase_returns_is_search');
        $this->session->unset_userdata('purchase_returns_search_key');
        $this->session->unset_userdata('purchase_returns_search_value');
        redirect('purchase/purchase_returns');

    }//end of function
    
    //supportive functions
    
    //get pending sales returns for specified agent via AJAX | View: add_purchase_return_view
    public function get_sales_returns($agent_id) {
        $this->load->model('sales/sales_return_model');
        $fields   = array(
            'tbl09_sales_return.id AS return_id','sales_id','return_sku AS sku', 
            "CONCAT(book_title,' - ',isbn) AS description",'book_status',
            'quantity_returned'
        );
        $criteria = array(
            'tbl09_sales_return.agent_id' => $agent_id,
            'return_status'               => 'pending'
        );
        $order_by = 'tbl09_sales_return.id ASC';
        $join1    = array('tbl06_book','tbl09_sales_return.return_sku = tbl06_book.sku');
        $data = $this->sales_return_model->get_sales_returns($fields,$criteria,'','',$order_by,$join1);
        //var_dump($data);
        echo json_encode($data);
        
    }//end of function
    
} //end of class
//end of file
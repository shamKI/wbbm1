<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Financial extends CI_Controller {
    
    private $module = 'financial';
    
    public function __construct() {
        parent::__construct();
        $this->application->level2();
        $this->load->model('financial/financial_model');
        $this->load->model('financial/payment_model');
        $this->load->model('stock/agent_model');
        $this->load->model('stock/dealer_model');
        $this->load->model('purchase/purchase_model');
        $this->load->model('sales/sales_model');
    }
    
    //views
    
    public function index($page = 0) {
        $data 		= $this->application->set_data();
        $data['module'] = $this->module;
        $criteria	= '';	
        
        //set criteria if any
        if($this->session->userdata('transactions_is_search')) { 
            $key   = $this->session->userdata('transactions_search_key');
            $value = $this->session->userdata('transactions_search_value'); 

            if($key == 'trans_category') {
                switch ($value){
                    case '1' : $value = 'sales' ; break; 
                    case '2' : $value = 'credit' ; break; 
                    case '3' : $value = 'debit' ; break; 
                    default  : $key = 'tbl15_financial.id';$value = FALSE;
                }
            } else if($key == 'income') {
                switch ($value){
                    case '1' : $value = TRUE ; break; 
                    case '2' : $value = FALSE ; break; 
                    default  : $key = 'tbl15_financial.id';$value = FALSE;
                }
            } else if($key == 'DATE(date_made)') { //date
                if(!(bool)preg_match( '/^\d{4}\-\d{1,2}\-\d{1,2}+$/',$value)) {
                    $value = FALSE;
                }
            } else if($key == 'MONTH(date_made)') { //month
                if(!((bool)preg_match( '/^\d+$/',$value) && $value >= 1 && $value <= 12)) {
                    $value = FALSE;
                }
            } else if($key == 'trans_status') {
                switch ($value){
                    case '1' : $value = 'pending' ; break; 
                    case '2' : $value = 'completed' ; break; 
                    case '3' : $value = 'canceled' ; break; 
                    default  : $key = 'tbl15_financial.id';$value = FALSE;
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
        $total_rows = $this->financial_model->get_total_rows($criteria) ;
        $this->application->set_pagination($total_rows,site_url('financial/index'));

        //set data
        $fields = array(
            "id", "description", "trans_category AS category", "amount", "IFNULL(cheque_no, ' - ') AS cheque_no",
            "IF(income,'Income','Expenditure') AS type",
            "date_made", "CONCAT(nic,' - ',first_name,'<br/>',designation) AS user_made","user_status",
            "trans_status AS status","creditor_debtor_id",
            "IF(trans_category != 'sales',TRUE,FALSE) AS is_viewable",
            "IF(trans_status = 'pending' AND !ISNULL(cheque_no),TRUE,FALSE) AS is_editable",
        );
        $limit = $this->config->item('rows_per_page');
        $join1 = array('tbl01_user', 'tbl15_financial.user_made = tbl01_user.nic');
        $data['transactions'] = $this->financial_model->get_transactions($fields,$criteria,$page,$limit,'',$join1);

        $data['pagination_html'] = $this->pagination->create_links();

        //var_dump($data);
        $this->load->view('financial/financial_view',$data);
    }//end of function

    public function creditors_debtors() {
        $data                = $this->application->set_data();
        $data['module']      = $this->module;
        
        //set notification if any
        if($this->session->flashdata('is_notification')) {
            $data['is_notification']		= $this->session->flashdata('is_notification');
            $data['notification_type']		= $this->session->flashdata('notification_type');
            $data['notification_description']   = $this->session->flashdata('notification_description');
        }
        
        $fields = array(
            'id', 'agent_name','credit_amount',
            "IF(credit_amount > 0, TRUE, FALSE)AS is_payable",
            'agent_status',"IF(agent_status,'Current','Past') AS status");
        $data['agents'] = $this->agent_model->get_agents($fields);
        //get all dealers
        $fields = array(
            'id', 'dealer_name','debit_amount',
            "IF(debit_amount > 0, TRUE, FALSE)AS is_payable",
            'dealer_status',"IF(dealer_status,'Current','Past') AS status");
        $data['dealers'] = $this->dealer_model->get_dealers($fields);
        
        //var_dump($data);
        $this->load->view('financial/creditors_debtors_view',$data);
    
    }//end of function
    
    public function payments($transaction_id) {
        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get transaction data
        $fields = array(
            "description","amount", "IFNULL(cheque_no, ' - ') AS cheque_no",
            "date_made", "trans_status AS status",
        );
        $criteria = array('id' => $transaction_id);
        $data['transaction'] = $this->financial_model->get_transaction($fields,$criteria);
        
        //get payments data
        $fields   = array(
            "IF(is_purchase, 'Purchase', 'Sales') AS type", 'sales_purchase_id', 'amount'
        );
        $criteria = array('trans_id' => $transaction_id);
        $order_by = 'sales_purchase_id ASC';
        $data['payments'] = $this->payment_model->get_payments($fields,$criteria,'','',$order_by);
        //var_dump($data);
        $this->load->view('financial/payments_view',$data);
    }//end of function
    
    public function credits($agent_id) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        $fields   = array("id AS agent_id", "agent_name", "credit_amount");
        $criteria = array('id' => $agent_id);
        $data['agent'] = $this->agent_model->get_agent($fields,$criteria);
        
        //var_dump($data);
        $this->load->view('financial/credits_view',$data);

    }//end of function
    
    public function debits($dealer_id) {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        $fields   = array("id AS dealer_id", "dealer_name", "debit_amount");
        $criteria = array('id' => $dealer_id);
        $data['dealer'] = $this->dealer_model->get_dealer($fields,$criteria);
        
        //var_dump($data);
        $this->load->view('financial/debits_view',$data);
    }//end of function
    
    //actions
    
    public function pay_credit() {
        //var_dump($_POST);
        $agent_id  = $this->input->post('hddn_agent_id');
        $amount    = (float)$this->input->post('txt_amount_paid');
        $cheque_no = $this->input->post('txt_cheque_no');
        
        //retrieve purchases
        $fields = array(
            'id AS purchase_id','total_paid',
            'ROUND((total_amount-(total_amount*discount/100))-total_paid,2)AS purchase_credit'
        );
        $criteria  = "agent_id = '{$agent_id}' AND purchase_status = 'completed' AND ROUND(total_amount-(total_amount*discount/100),2) > total_paid";
        $order_by  = "id ASC";
        $purchases = $this->purchase_model->get_purchases($fields,$criteria,'','',$order_by);
        //var_dump($purchases);
        
        //prepare data sets
        $data_set_agent = array(
            'credit_amount' => (float)$this->input->post('hddn_credit_amount') - $amount
        );
        $data_set_financial = array(
            'trans_category'     => 'credit',
            'description'        => "Pay credit : " . $this->input->post('hddn_agent_name'),
            'amount'             => $amount,
            'creditor_debtor_id' => $agent_id,
            'income'             => FALSE,
            'date_made'          => get_cur_date_time(),
            'user_made'          => $this->session->userdata('user_id'),
        );
        if($cheque_no !='') {
            $data_set_financial['cheque_no']    = $cheque_no;
            $data_set_financial['trans_status'] = 'pending';
        }
        $data_set_purchases = array();
        $data_set_payments  = array();
        //process purchases
        $i = 0;
        while($i < count($purchases) && $amount > 0.00) {
            $purchase_credit = $purchases[$i]['purchase_credit'];
            $amount_paid     = 0.00;
            if($purchase_credit > $amount) {
                $amount_paid = $amount;
                $amount = 0.00;
            } else {
                $amount_paid = $purchase_credit;
                $amount     -= $amount_paid;
            }
            array_push($data_set_purchases,array(
                'id'         => $purchases[$i]['purchase_id'],
                'total_paid' => $purchases[$i]['total_paid'] + $amount_paid
            ));
            array_push($data_set_payments,array(
                'trans_id'          => null,
                'sales_purchase_id' => $purchases[$i]['purchase_id'],
                'amount'            => $amount_paid
            ));
            $i++;
        }//while
        //var_dump($data_set_agent);var_dump($data_set_financial);
        //var_dump($data_set_purchases);var_dump($data_set_payments);
        
        $this->db->trans_start();
            //insert financial
            $trans_id = $this->financial_model->add_transaction($data_set_financial);
            
            //insert trans id in to payments
            for($i = 0; $i < count($data_set_payments); $i++) {
                $data_set_payments[$i]['trans_id'] = $trans_id ;
            }
            //insert payments
            $this->payment_model->add_payments($data_set_payments);
            
            //update purchases
            $criteria = 'id';
            $this->purchase_model->update_purchases($data_set_purchases,$criteria);
            
            //update agent
            $criteria = array('id' => $agent_id);
            $this->agent_model->update_agent($data_set_agent,$criteria);
            
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        if($query_status) {

                //insert system log entry
                $description = "Pay credit:" . $this->input->post('hddn_agent_name');
                $this->application->write_log('financial', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The credit is added successfully."
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the credit.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('financial/creditors_debtors');
        
    }//end of function

    public function cancel_credit_payment($transaction_id, $agent_id, $amount) {
        
        //prepare dataset financial
        $data_set_financial = array('trans_status' => 'canceled');
        
        $this->db->trans_start();
            
            //prepare dataset - purchases
            $fields   = array('sales_purchase_id AS id','ROUND(total_paid - amount,2) AS total_paid');
            $criteria = array('trans_id' => $transaction_id);
            $join1    = array('tbl11_purchase','tbl16_payment.sales_purchase_id = tbl11_purchase.id');
            $data_set_purchases = $this->payment_model->get_payments($fields,$criteria,'','','',$join1);

            //prepare dataset - agent
            $fields   = array("ROUND(credit_amount + {$amount} ,2) AS credit_amount");
            $criteria = array('id' => $agent_id);
            $data_set_agent = $this->agent_model->get_agent($fields,$criteria);
            //var_dump($data_set_financial); var_dump($data_set_purchases); var_dump($data_set_agent);
            
            //update database
            $criteria = array('id' => $transaction_id);
            $this->financial_model->update_transaction($data_set_financial,$criteria);
            
            $criteria = array('id' => $agent_id);
            $this->agent_model->update_agent($data_set_agent,$criteria);
            
            $criteria = 'id';
            $this->purchase_model->update_purchases($data_set_purchases,$criteria);
            
        $this->db->trans_complete();
        $query_status = $this->db->trans_status();
        if($query_status) {
            //insert system log entry
            $description = "Cancel transaction: " . $transaction_id;
            $this->application->write_log('financial', $description);

            //prepare notifications
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'success',
                'notification_description'  => "The transaction is canceled successfully."
            );
        } else {
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the transaction.'
            );
        }
        $this->session->set_flashdata($notification);
        redirect('financial');

    }//end of function
    
    public function receive_debit() {
        var_dump($_POST);
        $dealer_id  = $this->input->post('hddn_dealer_id');
        $amount    = (float)$this->input->post('txt_amount_received');
        $cheque_no = $this->input->post('txt_cheque_no');
        
        //retrieve sales
        $fields = array(
            'id AS sales_id','total_paid',
            'ROUND((total_amount-(total_amount*discount/100))-total_paid,2) AS sales_debit'
        );
        $criteria  = "dealer_id = '{$dealer_id}' AND ROUND(total_amount-(total_amount*discount/100),2) > total_paid";
        $order_by  = "id ASC";
        $sales = $this->sales_model->get_sales($fields,$criteria,'','',$order_by);
        var_dump($sales);
        
        //prepare data sets
        $data_set_dealer = array(
            'debit_amount' => (float)$this->input->post('hddn_debit_amount') - $amount
        );
        $data_set_financial = array(
            'trans_category'     => 'debit',
            'description'        => "Receive debit : " . $this->input->post('hddn_dealer_name'),
            'amount'             => $amount,
            'creditor_debtor_id' => $dealer_id,
            'date_made'          => get_cur_date_time(),
            'user_made'          => $this->session->userdata('user_id'),
        );
        if($cheque_no !='') {
            $data_set_financial['cheque_no']    = $cheque_no;
            $data_set_financial['trans_status'] = 'pending';
        }
        $data_set_sales = array();
        $data_set_payments  = array();
        //process purchases
        $i = 0;
        while($i < count($sales) && $amount > 0.00) {
            $sales_debit = $sales[$i]['sales_debit'];
            $amount_paid     = 0.00;
            if($sales_debit > $amount) {
                $amount_paid = $amount;
                $amount = 0.00;
            } else {
                $amount_paid = $sales_debit;
                $amount     -= $amount_paid;
            }
            array_push($data_set_sales,array(
                'id'         => $sales[$i]['sales_id'],
                'total_paid' => $sales[$i]['total_paid'] + $amount_paid
            ));
            array_push($data_set_payments,array(
                'trans_id'          => null,
                'is_purchase'       => FALSE,
                'sales_purchase_id' => $sales[$i]['sales_id'],
                'amount'            => $amount_paid
            ));
            $i++;
        }//while
        //var_dump($data_set_agent);var_dump($data_set_financial);
        //var_dump($data_set_purchases);var_dump($data_set_payments);
        
        $this->db->trans_start();
            //insert financial
            $trans_id = $this->financial_model->add_transaction($data_set_financial);
            
            //insert trans id in to payments
            for($i = 0; $i < count($data_set_payments); $i++) {
                $data_set_payments[$i]['trans_id'] = $trans_id ;
            }
            //insert payments
            $this->payment_model->add_payments($data_set_payments);
            
            //update sales
            $criteria = 'id';
            $this->sales_model->update_sales($data_set_sales,$criteria);
            
            //update dealer
            $criteria = array('id' => $dealer_id);
            $this->dealer_model->update_dealer($data_set_dealer,$criteria);
            
        $this->db->trans_complete();
        
        $query_status = $this->db->trans_status();
        if($query_status) {

                //insert system log entry
                $description = "Receive Debit:" . $this->input->post('hddn_dealer_name');
                $this->application->write_log('financial', $description);
                
                //prepare notifications
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'success',
                    'notification_description'  => "The debit is added successfully."
                );

            } else {
                $notification = array(
                    'is_notification'		=> TRUE,
                    'notification_type'		=> 'error',
                    'notification_description'  => 'Error terminated adding the debit.'
                );
            }
            $this->session->set_flashdata($notification);
            redirect('financial/creditors_debtors');
    
    }//end of function
    
    public function cancel_debit_payment($transaction_id, $dealer_id, $amount) {
        //prepare dataset financial
        $data_set_financial = array('trans_status' => 'canceled');
        
        $this->db->trans_start();
            
            //prepare dataset - sales
            $fields   = array('sales_purchase_id AS id','ROUND(total_paid - amount,2) AS total_paid');
            $criteria = array('trans_id' => $transaction_id);
            $join1    = array('tbl07_sales','tbl16_payment.sales_purchase_id = tbl07_sales.id');
            $data_set_sales = $this->payment_model->get_payments($fields,$criteria,'','','',$join1);

            //prepare dataset - dealer
            $fields   = array("ROUND(debit_amount + {$amount} ,2) AS debit_amount");
            $criteria = array('id' => $dealer_id);
            $data_set_dealer = $this->dealer_model->get_dealer($fields,$criteria);
            //var_dump($data_set_financial); var_dump($data_set_sales); var_dump($data_set_dealer);
            
            //update database
            $criteria = array('id' => $transaction_id);
            $this->financial_model->update_transaction($data_set_financial,$criteria);
            
            $criteria = array('id' => $dealer_id);
            $this->dealer_model->update_dealer($data_set_dealer,$criteria);
            
            $criteria = 'id';
            $this->sales_model->update_sales($data_set_sales,$criteria);
            
        $this->db->trans_complete();
        $query_status = $this->db->trans_status();
        if($query_status) {
            //insert system log entry
            $description = "Cancel transaction: " . $transaction_id;
            $this->application->write_log('financial', $description);

            //prepare notifications
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'success',
                'notification_description'  => "The transaction is canceled successfully."
            );
        } else {
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated cancelling the transaction.'
            );
        }
        $this->session->set_flashdata($notification);
        redirect('financial');

    }//end of function
    
    public function complete_payment($transaction_id) {
        $data_set = array('trans_status' => 'completed');
        $criteria = array('id' => $transaction_id);
        $query_status = $this->financial_model->update_transaction($data_set,$criteria);
        if($query_status) {
            //insert system log entry
            $description = "complete transaction:" . $transaction_id;
            $this->application->write_log('financial', $description);

            //prepare notifications
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'success',
                'notification_description'  => "The transaction is completed successfully."
            );

        } else {
            $notification = array(
                'is_notification'           => TRUE,
                'notification_type'         => 'error',
                'notification_description'  => 'Error terminated completing the transaction.'
            );
        }
        $this->session->set_flashdata($notification);
        redirect('financial');
    }//end of function
    
    public function financial_search() { 

        //set search criteria
        $key = $this->input->post('lst_key');
        $val = $this->input->post('txt_value');

        $this->session->set_userdata('transactions_is_search',true);
        $this->session->set_userdata('transactions_search_key',$key);
        $this->session->set_userdata('transactions_search_value',$val);
        redirect('financial');

    }//end of function

    public function financial_clear_search() { 

        //unset search criteria if any
        $this->session->unset_userdata('transactions_is_search');
        $this->session->unset_userdata('transactions_search_key');
        $this->session->unset_userdata('transactions_search_value');
        redirect('financial');

    }//end of function
        
} //end of class
//end of file
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

    private $module = 'reports';
    private $css;
    private $pdf_A4_L;
    private $pdf_A4_P;
    private $pdf_small;
    private $page_header;
    private $page_footer;
    
    public function __construct() {
        parent::__construct(); 
        $this->application->level2();
        
        $this->load->model('stock/category_model');
        $this->load->model('stock/agent_model');
        $this->load->model('stock/dealer_model');
        $this->load->model('stock/book_model');
        $this->load->model('financial/financial_model');

        $this->load->library('Pdf');
        
        //A4-landscape
        $this->pdf_A4_L = new mPDF('','A4-L');
        $this->pdf_A4_L->setFooter('{PAGENO}');
        
        //A4-portrait
        $this->pdf_A4_P = new mPDF('','A4');
        $this->pdf_A4_P->setFooter('{PAGENO}');
        
        //invoice
        $this->pdf_small = new mPDF('',array(200,135),0,'',12,12,5,5);
        $this->pdf_small->setFooter('{PAGENO}');
        
        
        $this->css  = file_get_contents($this->config->item('css') . 'pdf.css') ;
        
        $this->page_header  =   "<html><body><header class='page-heading'>
        <table>
        <tr>
        <td><img src='" . $this->config->item('system_images') . 'heading.jpg' . "' /></td>
        <td>
        <span class='page-heading-title'>WISDOM BOOK SHOP</span><br/>
        <span class='page-heading-subtitle' >No. 310, Negombo Road, Wattala, Srilanka</span><br/>
        <span class='page-heading-subtitle'>Tel. No: 011 2948586, <i>Email: wisdom@sltnet.lk</i>,  www.wisdombooks.lk</span>
        </td>
        </tr>
        </table>    
        </header>";
        $this->page_footer = "</body></html>" ;
    }

//-------- view ---------------

    public function index() {

        $data = $this->application->set_data();
        $data['module'] = $this->module;
        
        //get categories
        $fields = array('id','category_name','category_status');
        $data['categories'] = $this->category_model->get_categories($fields);
        //get agents
        $fields = array('id','agent_name','agent_status');
        $data['agents'] = $this->agent_model->get_agents($fields);
        //get dealers
        $fields = array('id','dealer_name','dealer_status');
        $data['dealers'] = $this->dealer_model->get_dealers($fields);

        //var_dump($data);
        $this->load->view("reports/reports_view",$data);

    }//end of function
    
//------- reports --------------
    public function stock_report() {
        //var_dump($_POST);
        $date     = get_cur_date_time();
        $user     = $this->session->userdata('user_name');
        $category = 'ALL';
        $agent    = 'ALL';
        
        //insert system log entry
        $description = "Generate Stock Report.";
        $this->application->write_log('reports', $description);
        
        //prepare Data
        $fields = array(
            'sku','book_title', 'book_status',  
            'min_stock', 'max_stock', "IFNULL(last_sold, '-') AS last_sold", 'cur_stock',
            'list_price', "ROUND(cur_stock * list_price,2) AS amount",
            "IF(min_stock >= cur_stock AND book_status, TRUE, FALSE) AS is_low",
            "IF(cur_stock > 0 AND !book_status, TRUE, FALSE) AS has_stock"
        );
        $criteria = "(book_status = TRUE OR (book_status = FALSE AND cur_stock > 0))";
        if($this->input->post('lst_category') != 'all') {
            $criteria .= " AND category_id = " . $this->input->post('lst_category');
            //get category name
            $fields2    = array('category_name');
            $criteria2 = array('id' => $this->input->post('lst_category'));
            $category = $this->category_model->get_category($fields2,$criteria2)['category_name'];
        }
        if($this->input->post('lst_agent') != 'all') {
            $criteria .= " AND agent_id = " . $this->input->post('lst_agent');
            //get agent name
            $fields2    = array('agent_name');
            $criteria2 = array('id' => $this->input->post('lst_agent'));
            $agent = $this->agent_model->get_agent($fields2,$criteria2)['agent_name'];
        }
        $order_by = 'sku ASC';
        $books = $this->book_model->get_books($fields,$criteria,'','',$order_by);
        //var_dump($books);
        
        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >Stock Report</span></td></tr>
        <tr>
        <td width='10%'>Category:</td><td>{$category}</td>
        <td width='60%' class='text-right' >Date:</td><td>{$date}</td>
        </tr>
        <tr>
        <td>Agent:</td><td>{$agent}</td>
        <td class='text-right'>User:</td><td>{$user}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='8%'>SKU</td>
        <td class='data-heading' width='38%'>Title</td>
        <td class='data-heading text-right' width='8%'>Min-Stock</td>
        <td class='data-heading text-right' width='8%'>Max-Stock</td>
        <td class='data-heading text-center' width='13%'>Last Sold</td>
        <td class='data-heading text-right' width='8%'>Cur-Stock</td>
        <td class='data-heading currency-mid'>List Price</td>
        <td class='data-heading currency-mid'>Amount</td>
        </tr>
        </thead>
        <tbody>" ;
        //populate with data
        $total_amount = 0.00;
        $total_books  = 0 ;
        if(!$books){
            $str .= "<tr><td colspan='8'>No Result</tr>";
        } else {
            foreach($books as $book) {
                if($book['is_low']) {
                    $str .= "<tr class='bold-row'>" ;
                } else {
                    $str .= "<tr>" ;
                }
                if($book['has_stock']) {  //discontinued but has stock
                    $str .= "<td class='data-row'>{$book['sku']}&nbsp;*</td>" ;
                } else {
                    $str .= "<td class='data-row'>{$book['sku']}</td>" ;
                }
                $str .= "   <td class='data-row'>{$book['book_title']}</td>
                <td class='data-row text-right'>{$book['min_stock']}</td>
                <td class='data-row text-right'>{$book['max_stock']}</td>
                <td class='data-row text-center'>{$book['last_sold']}</td>
                <td class='data-row text-right'>{$book['cur_stock']}</td>
                <td class='data-row currency-mid'>" . to_acc_currency($book['list_price']) . "</td>
                <td class='data-row currency-mid'>" . to_acc_currency($book['amount']) . "</td>
                </tr>";
                $total_amount += $book['amount'];
                $total_books  += $book['cur_stock'];
            }
        }
        //add grand-total
        $str .= "<tr><td class='data-row'>&nbsp;</td></tr>
        <tr><td colspan='5' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$total_books}</td>
        <td class='currency-mid grand-total-mid'> - </td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount) . "</td>
        </tr></tbody></table>" ;
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = 'stock_report.pdf' ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'D');
        
    }//end of function
    
    public function sales_report() {
        //var_dump($_POST);

        //insert system log entry
        $description = "Generate sales Report.";
        $this->application->write_log('reports', $description);
        
        $date        = get_cur_date_time();
        $user        = $this->session->userdata('user_name');
        $year        = $this->input->post('lst_year');
        $month       = $this->input->post('lst_month');
        $month_name  = 'ALL';
        if($month != 'all') {
            $month_name = get_month_name($month);
        }
        $category_id = $this->input->post('lst_category');
        $category    = 'ALL';
        if($category_id != 'all') {
            $category = $this->category_model->get_category(array('category_name'), array('id'=>$category_id))['category_name'];
        } else {
            $category_id = '';
        }
        $dealer_id    = $this->input->post('lst_dealer');
        $dealer = 'ALL';
        if($dealer_id != 'all') {
            $dealer = $this->dealer_model->get_dealer(array('dealer_name'), array('id'=>$dealer_id))['dealer_name'];
        } else {
            $dealer_id = '';
        }
        $report_type = 'Annual';
        if($month != 'all') {
            $report_type = 'Monthly';
        }

        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >{$report_type} Sales Report</span></td></tr>
        <tr>
        <td width='10%'>Duration:</td><td>{$year} - {$month_name}</td>
        <td width='60%' class='text-right' >&nbsp;</td><td>&nbsp;</td>
        </tr>
        <tr>
        <td width='10%'>Category:</td><td>{$category}</td>
        <td width='60%' class='text-right' >Date:</td><td>{$date}</td>
        </tr>
        <tr>
        <td>Dealer:</td><td>{$dealer}</td>
        <td class='text-right'>User:</td><td>{$user}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='8%'>SKU</td>
        <td class='data-heading' width='38%'>Title</td>
        <td class='data-heading text-right' width='8%'>Quantity</td>
        <td class='data-heading currency-mid'>Amount Listed</td>
        <td class='data-heading currency-mid'>Amount Sold</td>
        </tr>
        </thead>
        <tbody>" ;
        
        //get report content
        if($month == 'all') { //annual report
            $str .= $this->get_annual_sales_str($year,$dealer_id,$category_id);
        } else { //monthly report
            $str .= $this->get_monthly_sales_str($year,$month,$dealer_id,$category_id);
        }
        $str .= '</tbody></table>';
        
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = "sales_report_{$year}_{$month}.pdf" ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'D');

    }//end of function
    
    private function get_monthly_sales_str($year,$month,$dealer_id,$category_id) {
        $sales = $this->get_sales($year,$month,$dealer_id,$category_id);
        //var_dump($sales);
        $str = '';
        $total_amount   = 0.00;
        $total_amount_sold = 0.00;
        $total_quantity = 0;
        if(!$sales) {
            $str .= "<tr><td colspan='5' class='text-center'>No sales</td></tr>";
        } else {
            foreach($sales as $book){
                $str .= "<tr>
                <td class='data-row'>{$book['sku']}</td>
                <td class='data-row'>{$book['title']}</td>
                <td class='data-row text-right'>{$book['quantity']}</td>
                <td class='data-row currency-mid'>" . to_acc_currency($book['amount']) . "</td>
                <td class='data-row currency-mid'>" . to_acc_currency($book['amount_sold']) . "</td>
                </tr>";
                $total_amount             += $book['amount'];
                $total_amount_sold        += $book['amount_sold'];
                $total_quantity           += $book['quantity'];
            }//for
        }//if
        //add grand-total
        $str .= "<tr><td colspan='2' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$total_quantity}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount) . "</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount_sold) . "</td>
        </tr>";

        return $str;

    }//end of function
    
    private function get_annual_sales_str($year,$dealer_id,$category_id) {
        $str = '' ;
        $grand_total_amount = 0.00;
        $grand_total_amount_sold = 0.00;
        $grand_total_quantity = 0;
        
        for($i=1; $i <= 12; $i++) { //for each month
            $sales = $this->get_sales($year,$i,$dealer_id,$category_id);
            $total_amount = 0.00;
            $total_amount_sold = 0.00;
            $total_quantity = 0;
            $str .= "<tr><td colspan='5'><b><br/>" . get_month_name($i). "</b></td></tr>";
            if(!$sales) {
                $str .= "<tr><td colspan='5'>No sales</td></tr>";
            } else {
                foreach($sales as $book){
                    $str .= "<tr>
                    <td class='data-row'>{$book['sku']}</td>
                    <td class='data-row'>{$book['title']}</td>
                    <td class='data-row text-right'>{$book['quantity']}</td>
                    <td class='data-row currency-mid'>" . to_acc_currency($book['amount']) . "</td>
                    <td class='data-row currency-mid'>" . to_acc_currency($book['amount_sold']) . "</td>
                    </tr>";
                    $total_amount             += $book['amount'];
                    $total_amount_sold        += $book['amount_sold'];
                    $total_quantity           += $book['quantity'];
                }//for
            }//if
            //add sub-total
            $str .= "<tr><td colspan='2' class='sub-total-mid'><b>Sub-Total<br/></b></td>
            <td class='sub-total text-right'>{$total_quantity}</td>
            <td class='sub-total currency-mid'>" . to_acc_currency($total_amount) . "</td>
            <td class='sub-total currency-mid'>" . to_acc_currency($total_amount_sold) . "</td>
            </tr>";
            $grand_total_amount      += $total_amount;
            $grand_total_amount_sold += $total_amount_sold;
            $grand_total_quantity    += $total_quantity;
        }//outer for
        
        //add garnd total
        $str .= "<tr><td colspan='2' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$grand_total_quantity}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($grand_total_amount) . "</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($grand_total_amount_sold) . "</td>
        </tr>";

        return $str;
    }//end of function
    
    private function get_sales($year,$month='',$dealer_id='',$category_id='') {
        $this->load->model('sales/sales_model');
        $sales = array();
        $criteria_in = '';
        if($category_id !='') { //category defined
            $fields   = array('sku');
            $criteria = array('category_id' => $category_id); 
            $skus = $this->book_model->get_books($fields,$criteria);
            if(count($skus)== 0) { //no books in this category
                return $sales;
            } else { //set criteria-in
                $temp = $skus;
                $temp = $this->application->array_remove_assoc($temp,'sku');
                $criteria_in = array('sales_sku',$temp);
            }
        }

        //retrieve data
        $fields = array(
            'sales_sku AS sku',
            'SUM(quantity_sold) AS quantity',
            "ROUND(SUM(quantity_sold * list_price),2) AS amount",
            "ROUND(SUM(list_price * ((100 - tbl08_sales_book.discount) / 100) * ((100 - tbl07_sales.discount) / 100) * quantity_sold),2) AS amount_sold"
        );
        $criteria = array(
            'YEAR(date_sold)' =>  $year,
            'MONTH(date_sold)' => $month,
        );
        if($dealer_id == 'retail') {
            $criteria['dealer_id'] = NULL;
        } else if($dealer_id != '') {
            $criteria['dealer_id'] = $dealer_id;
        } 
        $join1 = array('tbl08_sales_book', "tbl07_sales.id = tbl08_sales_book.sales_id");
        $order_by = "sales_sku ASC";
        $group_by = array('sales_sku');
        $sales    = $this->sales_model->get_sales($fields,$criteria,'','',$order_by,$join1,$group_by,'','',$criteria_in);

        //retrieve and merge book titles according to the sku if any sale exists
        if($sales) {
            $skus = array();
            foreach($sales as $sale) {
                array_push($skus,$sale['sku']);
            }
            $fields = array('sku', "CONCAT(book_title,' - ',isbn) AS title");
            $order_by = "sku ASC";
            $criteria_in = array('sku',$skus);
            $books = $this->book_model->get_books($fields,'','','',$order_by,'','','','',$criteria_in);
            
            $books     = $this->application->array_set_index($books,'sku');
            for($i = 0; $i < count($sales); $i++) {
                $sales[$i]['title'] = $books[$sales[$i]['sku']]['title'];
            }
        }
        return $sales;
        
    }//end of function
    
    public function purchase_report() {
        //var_dump($_POST);

        //insert system log entry
        $description = "Generate purchase Report.";
        $this->application->write_log('reports', $description);
        
        $date        = get_cur_date_time();
        $user        = $this->session->userdata('user_name');
        $year        = $this->input->post('lst_year');
        $month       = $this->input->post('lst_month');
        $month_name  = 'ALL';
        if($month != 'all') {
            $month_name = get_month_name($month);
        }
        $category_id = $this->input->post('lst_category');
        $category    = 'ALL';
        if($category_id != 'all') {
            $category = $this->category_model->get_category(array('category_name'), array('id'=>$category_id))['category_name'];
        } else {
            $category_id = '';
        }
        $agent_id    = $this->input->post('lst_agent');
        $agent = 'ALL';
        if($agent_id != 'all') {
            $agent = $this->agent_model->get_agent(array('agent_name'), array('id'=>$agent_id))['agent_name'];
        } else {
            $agent_id = '';
        }
        $report_type = 'Annual';
        if($month != 'all') {
            $report_type = 'Monthly';
        }

        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >{$report_type} Purchase Report</span></td></tr>
        <tr>
        <td width='10%'>Duration:</td><td>{$year} - {$month_name}</td>
        <td width='60%' class='text-right' >&nbsp;</td><td>&nbsp;</td>
        </tr>
        <tr>
        <td width='10%'>Category:</td><td>{$category}</td>
        <td width='60%' class='text-right' >Date:</td><td>{$date}</td>
        </tr>
        <tr>
        <td>Agent:</td><td>{$agent}</td>
        <td class='text-right'>User:</td><td>{$user}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='8%'>SKU</td>
        <td class='data-heading' width='38%'>Title</td>
        <td class='data-heading text-right' width='8%'>Quantity</td>
        <td class='data-heading currency-mid'>List Amount</td>
        <td class='data-heading currency-mid'>Purchased Amount</td>
        </tr>
        </thead>
        <tbody>" ;
        
        //get report content
        if($month == 'all') { //annual report
            $str .= $this->get_annual_purchase_str($year,$agent_id,$category_id);
        } else { //monthly report
            $str .= $this->get_monthly_purchase_str($year,$month,$agent_id,$category_id);
        }
        $str .= '</tbody></table>';
        
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = "purchase_report_{$year}_{$month}.pdf" ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'D');

    }//end of function
    
    private function get_monthly_purchase_str($year,$month,$agent_id,$category_id) {
        $purchases = $this->get_purchase($year,$month,$agent_id,$category_id);
        //var_dump($purchases);
        $str = '';
        $total_amount   = 0.00;
        $total_amount_purchased = 0.00;
        $total_quantity = 0;
        if(!$purchases) {
            $str .= "<tr><td colspan='5' class='text-center'>No Purchases</td></tr>";
        } else {
            foreach($purchases as $book){
                $str .= "<tr>
                <td class='data-row'>{$book['sku']}</td>
                <td class='data-row'>{$book['title']}</td>
                <td class='data-row text-right'>{$book['quantity']}</td>
                <td class='data-row currency-mid'>" . to_acc_currency($book['amount']) . "</td>
                <td class='data-row currency-mid'>" . to_acc_currency($book['amount_purchased']) . "</td>
                </tr>";
                $total_amount             += $book['amount'];
                $total_amount_purchased   += $book['amount_purchased'];
                $total_quantity           += $book['quantity'];
            }//for
        }//if
        //add grand-total
        $str .= "<tr><td colspan='2' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$total_quantity}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount) . "</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount_purchased) . "</td>
        </tr>";

        return $str;
        
    }//end of function
    
    private function get_annual_purchase_str($year,$agent_id,$category_id) {
        $str = '' ;
        $grand_total_amount = 0.00;
        $grand_total_amount_purchased = 0.00;
        $grand_total_quantity = 0;
        
        for($i=1; $i <= 12; $i++) { //for each month
            $purchases = $this->get_purchase($year,$i,$agent_id,$category_id);
            $total_amount = 0.00;
            $total_amount_purchased = 0.00;
            $total_quantity = 0;
            $str .= "<tr><td colspan='5'><b><br/>" . get_month_name($i). "</b></td></tr>";
            if(!$purchases) {
                $str .= "<tr><td colspan='5'>No Purchases</td></tr>";
            } else {
                foreach($purchases as $book){
                    $str .= "<tr>
                    <td class='data-row'>{$book['sku']}</td>
                    <td class='data-row'>{$book['title']}</td>
                    <td class='data-row text-right'>{$book['quantity']}</td>
                    <td class='data-row currency-mid'>" . to_acc_currency($book['amount']) . "</td>
                    <td class='data-row currency-mid'>" . to_acc_currency($book['amount_purchased']) . "</td>
                    </tr>";
                    $total_amount             += $book['amount'];
                    $total_amount_purchased   += $book['amount_purchased'];
                    $total_quantity           += $book['quantity'];
                }//for
            }//if
            //add sub-total
            $str .= "<tr><td colspan='2' class='sub-total-mid'><b>Sub-Total<br/></b></td>
            <td class='sub-total text-right'>{$total_quantity}</td>
            <td class='sub-total currency-mid'>" . to_acc_currency($total_amount) . "</td>
            <td class='sub-total currency-mid'>" . to_acc_currency($total_amount_purchased) . "</td>
            </tr>";
            $grand_total_amount += $total_amount;
            $grand_total_amount_purchased += $total_amount_purchased;
            $grand_total_quantity += $total_quantity;
        }//outer for
        
        //add garnd total
        $str .= "<tr><td colspan='2' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$grand_total_quantity}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($grand_total_amount) . "</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($grand_total_amount_purchased) . "</td>
        </tr>";

        return $str;
    }//end of function
    
    private function get_purchase($year,$month='',$agent_id='',$category_id='') {
        $this->load->model('purchase/purchase_model');
        $purchases = array();
        $criteria_in = '';
        if($category_id !='') { //category defined
            $fields   = array('sku');
            $criteria = array('category_id' => $category_id); 
            $skus = $this->book_model->get_books($fields,$criteria);
            if(count($skus)== 0) { //no books in this category
                return $purchases;
            } else { //set criteria-in
                $temp = $skus;
                $temp = $this->application->array_remove_assoc($temp,'sku');
                $criteria_in = array('purchase_sku',$temp);
            }
        }
        
        //retrieve data
        $fields = array(
            'purchase_sku AS sku',
            'SUM(quantity_purchased) AS quantity',
            "ROUND(SUM(quantity_purchased * price_purchased),2) AS amount",
            "ROUND(SUM((price_purchased - (price_purchased *discount/100))*quantity_purchased),2) AS amount_purchased"
        );
        $criteria = array(
            'purchase_status'       => 'completed',
            'YEAR(date_com_can)'    =>  $year,
            'MONTH(date_com_can)'   => $month,
        );
        if($agent_id != '') {
            $criteria['agent_id'] = $agent_id;
        }
        $join1 = array('tbl12_purchase_book', "tbl11_purchase.id = tbl12_purchase_book.purchase_id AND quantity_purchased > 0");
        $order_by = "purchase_sku ASC";
        $group_by = array('purchase_sku');
        $purchases = $this->purchase_model->get_purchases($fields,$criteria,'','',$order_by,$join1,$group_by,'','',$criteria_in);

        //retrieve and merge book titles according to the sku if any purchase exists
        if($purchases) {
            $skus = array();
            foreach($purchases as $purchase) {
                array_push($skus,$purchase['sku']);
            }
            $fields = array('sku', "CONCAT(book_title,' - ',isbn) AS title");
            $order_by = "sku ASC";
            $criteria_in = array('sku',$skus);
            $books = $this->book_model->get_books($fields,'','','',$order_by,'','','','',$criteria_in);
            
            $books     = $this->application->array_set_index($books,'sku');
            for($i = 0; $i < count($purchases); $i++) {
                $purchases[$i]['title'] = $books[$purchases[$i]['sku']]['title'];
            }
        }
        return $purchases;
        
    }//end of function
    
//------- business documentations ----------
    
    public function purchase_order($order_id) {
        $this->load->model('purchase/purchase_model');
        $this->load->model('purchase/purchase_book_model');
        
        //prepare Data - purhcase order
        $fields = array(
            'agent_name', 
            "CONCAT(first_name, ' ', last_name, ', ', designation )AS user_ordered",  
            'date_ordered'
        );
        $criteria = array('tbl11_purchase.id' => $order_id);
        $join1 = array('tbl01_user','tbl11_purchase.user_ordered = tbl01_user.nic');
        $join2 = array('tbl04_agent','tbl11_purchase.agent_id = tbl04_agent.id');
        $purchase = $this->purchase_model->get_purchase($fields,$criteria,$join1,$join2);
        //var_dump($purchase);
        
        //prepare Data - purhcase order books
        $fields = array(
            'purchase_sku', 'price_ordered', 'quantity_ordered',
            "CONCAT(book_title,', ',isbn,'<br/>',author,', ',publisher) AS description",
            "ROUND(price_ordered * quantity_ordered,2) AS amount"
        );
        $criteria = array('purchase_id' => $order_id);
        $join1 = array('tbl06_book','tbl12_purchase_book.purchase_sku = tbl06_book.sku');
        $order_by = 'purchase_sku ASC';
        $purchase_books = $this->purchase_book_model->get_purchase_books($fields, $criteria, '', '', $order_by, $join1);
        //var_dump($purchase_books);
        
        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >Purchase Order</span></td></tr>
        <tr>
        <td width='15%' >Order ID:</td><td width='25%' ><b>" . sprintf("%06s", $order_id) . "</b></td>
        <td width='30%' class='text-right'>Ordered by:</td><td width='30%' >{$purchase['user_ordered']}</td>
        </tr>
        <tr>
        <td>Ordered from:</td><td>{$purchase['agent_name']}</td>
        <td class='text-right'>Ordered on:</td><td>{$purchase['date_ordered']}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='8%'>SKU</td>
        <td class='data-heading' width='38%'>Title</td>
        <td class='data-heading currency-mid'>List Price</td>
        <td class='data-heading text-right' width='8%'>Quantity</td>
        <td class='data-heading currency-mid'>Amount</td>
        </tr>
        </thead>
        <tbody>" ;
        //populate with data
        $total_amount = 0.00;
        $total_books  = 0 ;
        foreach($purchase_books as $book) {
            $str .= "<tr>
            <td class='data-row'>{$book['purchase_sku']}</td>
            <td class='data-row'>{$book['description']}</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['price_ordered']) . "</td>
            <td class='data-row text-right' valign='bottom' >{$book['quantity_ordered']}</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['amount']) . "</td>
            </tr>";
            $total_amount += $book['amount'];
            $total_books  += $book['quantity_ordered'];
        }
        
        //add grand-total
        $str .= "<tr><td colspan='3' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$total_books}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount) . "</td>
        </tr></tbody></table>" ;
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = "purchase_order_{$order_id}.pdf" ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'I');

    }//end of function
    
    public function grn($purchase_id) {
        $this->load->model('purchase/purchase_model');
        $this->load->model('purchase/purchase_book_model');
        
        //prepare Data - purhcase
        $fields = array(
            'agent_name','date_ordered', 'date_com_can AS date_purchased','invoice_no','total_amount','discount',
            "ROUND(total_amount-(total_amount*discount/100),2) AS net_amount",
        );
        $criteria = array('tbl11_purchase.id' => $purchase_id);
        $join1 = array('tbl04_agent','tbl11_purchase.agent_id = tbl04_agent.id');
        $purchase = $this->purchase_model->get_purchase($fields,$criteria,$join1);
        //var_dump($purchase);
        
        //prepare Data - purhcase order books
        $fields = array(
            'purchase_sku', 'price_ordered', 'quantity_ordered','price_purchased','quantity_purchased',
            "CONCAT(book_title,', ',isbn,'<br/>',author,', ',publisher) AS description",
            "ROUND(price_ordered * quantity_ordered,2) AS ordered_amount",
            "ROUND(price_purchased * quantity_purchased,2) AS purchased_amount"
        );
        $criteria = array('purchase_id' => $purchase_id);
        $join1 = array('tbl06_book','tbl12_purchase_book.purchase_sku = tbl06_book.sku');
        $order_by = 'purchase_sku ASC';
        $purchase_books = $this->purchase_book_model->get_purchase_books($fields, $criteria, '', '', $order_by, $join1);
        //var_dump($purchase_books);
        
        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >GRN [Order ID: " . sprintf("%06s", $purchase_id) . "]</span></td></tr>
        <tr>
        <td width='15%' >Invoice No.:</td><td width='25%' >{$purchase['invoice_no']}</td>
        <td width='70%' class='text-right'>Ordered on:</td><td width='10%' >{$purchase['date_ordered']}</td>
        </tr>
        <tr>
        <td>Ordered from:</td><td>{$purchase['agent_name']}</td>
        <td class='text-right'>Received on:</td><td>{$purchase['date_purchased']}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='8%'>SKU</td>
        <td class='data-heading' width='38%'>Title</td>
        <td class='data-heading currency-mid'>Price<br/>Ordered</td>
        <td class='data-heading text-right' width='8%'>Quantity<br/>Ordered</td>
        <td class='data-heading currency-mid'>Amount<br/>Ordered</td>
        <td class='data-heading currency-mid'>Price<br/>Purchased</td>
        <td class='data-heading text-right' width='8%'>Quantity<br/>Purchased</td>
        <td class='data-heading currency-mid'>Amount<br/>Purchased</td>
        </tr>
        </thead>
        <tbody>" ;
        //populate with data
        $total_amount_ordered = 0.00;
        $total_books_ordered  = 0 ;
        $total_books_purchased  = 0 ;
        foreach($purchase_books as $book) {
            $str .= "<tr>
            <td class='data-row'>{$book['purchase_sku']}</td>
            <td class='data-row'>{$book['description']}</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['price_ordered']) . "</td>
            <td class='data-row text-right' valign='bottom' >{$book['quantity_ordered']}</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['ordered_amount']) . "</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['price_purchased']) . "</td>
            <td class='data-row text-right' valign='bottom' >{$book['quantity_purchased']}</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['purchased_amount']) . "</td>    
            </tr>";
            $total_amount_ordered += $book['ordered_amount'];
            $total_books_ordered  += $book['quantity_ordered'];
            $total_books_purchased  += $book['quantity_purchased'];
        }
        
        //add grand-total
        $str .= "<tr><td colspan='2' class='grand-total-mid'><b>Total</b></td>
        <td class='data-row currency-mid grand-total-mid' valign='bottom' > - </td>
        <td class='grand-total-mid text-right'>{$total_books_ordered}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($total_amount_ordered) . "</td>
        <td class='data-row currency-mid grand-total-mid' valign='bottom' > - </td>
        <td class='grand-total-mid text-right'>{$total_books_purchased}</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($purchase['total_amount']) . "</td>
        </tr>
        <tr>
        <td colspan='7' class='grand-total-mid'><b>Net Amount (Discount: {$purchase['discount']}%)</b></td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($purchase['net_amount']) . "</td>
        </tr>
        </tbody>
        </table>" ;
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = "GRN_{$purchase_id}.pdf" ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'I');

    }//end of function
    
    public function sales_invoice($sales_id,$is_retail, $payment = 0.00) {
        $this->load->model('sales/sales_model');
        $this->load->model('sales/sales_book_model');
        
        //prepare Data - sales
        $fields = array(
            'first_name AS user_sold', 'date_sold',
            'total_amount','tbl07_sales.discount AS discount',
            'ROUND(total_amount * tbl07_sales.discount / 100,2)AS discount_amount',
            'ROUND(total_amount - (total_amount * tbl07_sales.discount / 100),2)AS net_amount',
            "IFNULL(dealer_name,'') AS dealer_name"
        );
        $criteria = array('tbl07_sales.id' => $sales_id);
        $join1    = array('tbl01_user','tbl07_sales.user_sold = tbl01_user.nic');
        $join2    = array('tbl05_dealer','tbl07_sales.dealer_id = tbl05_dealer.id');
        $sale     = $this->sales_model->get_sale($fields,$criteria,$join1,$join2);
        //var_dump($sale);
        
        //prepare Data - sales books
        $fields = array(
            "sales_sku","CONCAT(book_title,' - ',isbn) AS description",
            'tbl08_sales_book.list_price AS list_price', 'tbl08_sales_book.discount AS discount','quantity_sold',
            "ROUND((tbl08_sales_book.list_price - (tbl08_sales_book.list_price * tbl08_sales_book.discount / 100)) * quantity_sold,2) AS amount"
        );
        $criteria = array('sales_id' => $sales_id);
        $join1 = array('tbl06_book','tbl08_sales_book.sales_sku = tbl06_book.sku');
        $order_by = 'sales_sku ASC';
        $sales_books = $this->sales_book_model->get_sales_books($fields, $criteria, '', '', $order_by, $join1);
        //var_dump($sales_books);


        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >Sales Invoice</span></td></tr>
        <tr>
        <td width='15%' >Invoice No:</td><td width='60%' ><b>" . sprintf("%06s", $sales_id) . "</b></td>
        <td>Cashier: {$sale['user_sold']}</td>
        </tr>
        <tr>";
        if($is_retail) {
            $str .= "<td>&nbsp;</td><td>&nbsp;</td>";
        } else {
            $str .= "<td>Dealer:</td><td>{$sale['dealer_name']}</td>";
        }
        $str .=        "<td>{$sale['date_sold']}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='8%'>SKU</td>
        <td class='data-heading' width='38%'>Title</td>
        <td class='data-heading currency-mid'>Price</td>
        <td class='data-heading text-right' width='8%'>Quantity</td>
        <td class='data-heading text-right' width='8%'>Discount</td>
        <td class='data-heading currency-mid'>Amount</td>
        </tr>
        </thead>
        <tbody>" ;
        //populate with data
        $total_books  = 0 ;
        foreach($sales_books as $book) {
            $str .= "<tr>
            <td class='data-row'>{$book['sales_sku']}</td>
            <td class='data-row'>{$book['description']}</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['list_price']) . "</td>
            <td class='data-row text-right' valign='bottom' >{$book['quantity_sold']}</td>
            <td class='data-row text-right' valign='bottom' >{$book['discount']}%</td>
            <td class='data-row currency-mid' valign='bottom' >" . to_acc_currency($book['amount']) . "</td>
            </tr>";
            $total_books  += $book['quantity_sold'];
        }
        
        //add grand-total
        $str .=         "<tr>
        <td colspan='3' class='grand-total-mid'>Total</td>
        <td class='grand-total-mid text-right'>{$total_books}</td>
        <td class='grand-total-mid text-right'>&nbsp;</td>
        <td class='currency-mid grand-total-mid'>" . to_acc_currency($sale['total_amount']) . "</td>
        </tr>
        <tr>
        <td colspan='4'>Discount</td>
        <td class='text-right'>{$sale['discount']}%</td>
        <td class='currency-mid'>" . to_acc_currency($sale['discount_amount']) . "</td>
        </tr>
        <tr>
        <td colspan='5'><b>Net Amount</b></td>
        <td class='currency-mid'><b>" . to_acc_currency($sale['net_amount']) . "</b></td>
        </tr>";
        if($is_retail) {
            $str .= "<tr>
            <td colspan='5'>Cash</td>
            <td class='currency-mid'>" . to_acc_currency($payment) . "</td>
            </tr>
            <tr>
            <td colspan='5'><b>Balance</b></td>
            <td class='currency-mid'><b>" . to_acc_currency($payment - $sale['net_amount']) . "</b></td>
            </tr>";
        }
        $str .=    "</tbody></table>" ;
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = "sales_invoice_{$sales_id}.pdf" ;
        $this->pdf_small->WriteHTML($this->css,1);
        $this->pdf_small->WriteHTML($str,2);
        $this->pdf_small->Output($file_name,'I');

    }//end of function

    public function purchase_return_order($order_id) {
        $this->load->model('purchase/purchase_return_model');
        $this->load->model('purchase/purchase_return_sales_return_model');
        $this->load->model('sales/sales_return_model');
        
        //prepare data sets
        //get purchase return details
        $fields = array(
            "tbl13_purchase_return.id AS purchase_return_id",
            "agent_name AS agent",
            "date_ordered", "CONCAT(first_name,' ',last_name,', ',designation)AS user_ordered",
        );
        $criteria = array('tbl13_purchase_return.id' => $order_id);
        $join1	  = array("tbl04_agent", "tbl13_purchase_return.agent_id = tbl04_agent.id");
        $join2	  = array("tbl01_user", "tbl13_purchase_return.user_ordered = tbl01_user.nic");
        $purchase_return = $this->purchase_return_model->get_purchase_return($fields,$criteria,$join1,$join2);
        //var_dump($purchase_return);
        
        //get related sales return ids
        $fields   = array('sales_return_id');
        $criteria = array('purchase_return_id' => $order_id);
        $sales_return_ids = $this->purchase_return_sales_return_model->get_purchase_return_sales_returns($fields,$criteria);
        $sales_return_ids = $this->application->array_remove_assoc($sales_return_ids,'sales_return_id');
        
        //get sales return details
        $fields = array(
            'return_sku AS sku', 
            "CONCAT(book_title,' - ',isbn) AS description",
            "SUM(quantity_returned) AS quantity"
        );
        $criteria_in = array('tbl09_sales_return.id',$sales_return_ids);
        $order_by = 'tbl09_sales_return.return_sku ASC';
        $group_by = array('tbl09_sales_return.return_sku');
        $join1	  = array("tbl06_book", "tbl09_sales_return.return_sku = tbl06_book.sku");
        $sales_returns = $this->sales_return_model->get_sales_returns($fields,'','','',$order_by,$join1,$group_by,'','',$criteria_in);
        //var_dump($sales_returns);
        
        //prepare print string
        $str = $this->page_header;
        
        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='4' align='center'><span class='report-title' >Purchase Return Order</span></td></tr>
        <tr>
        <td width='15%' >Order ID:</td><td width='25%' ><b>" . sprintf("%06s", $order_id) . "</b></td>
        <td width='30%' class='text-right'>Ordered by:</td><td width='30%' >{$purchase_return['user_ordered']}</td>
        </tr>
        <tr>
        <td>Ordered from:</td><td>{$purchase_return['agent']}</td>
        <td class='text-right'>Ordered on:</td><td>{$purchase_return['date_ordered']}</td>
        </tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
        <td class='data-heading' width='10%'>SKU</td>
        <td class='data-heading'>Title</td>
        <td class='data-heading text-right' width='15%'>Quantity</td>
        </tr>
        </thead>
        <tbody>" ;
        //populate with data
        $total_quantity  = 0 ;
        foreach($sales_returns as $book) {
            $str .= "<tr>
            <td class='data-row'>{$book['sku']}</td>
            <td class='data-row'>{$book['description']}</td>
            <td class='data-row text-right' valign='bottom' >{$book['quantity']}</td>
            </tr>";
            $total_quantity  += $book['quantity'];
        }
        
        //add grand-total
        $str .= "<tr><td colspan='2' class='grand-total-mid'><b>Total</b></td>
        <td class='grand-total-mid text-right'>{$total_quantity}</td>
        </tr></tbody></table>" ;
        //add footer 
        $str .= $this->page_footer ;
        //generate PDF
        $file_name = "purchase_return_order_{$order_id}.pdf" ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'I');
        
    }//end of the function



    // monthly Income or expenditure report
    public function generateIncomeExpenditureReport()
    {
        $criteria   = '';   
        $page = 0;
        $date     = get_cur_date_time();
        $user     = $this->session->userdata('user_name');

        $report_year = $this->input->post('year');
        $report_month = $this->input->post('month');
        $report_type = $this->input->post('type');

        // generate 1st and last day of the month
        $first_day_of_month = date('Y-m-01 00:00:00', strtotime($report_month.' 01, '.$report_year));
        $last_day_of_month = date('Y-m-t 11:59:59', strtotime($report_month.' 01, '.$report_year));

        $total_rows = $this->financial_model->get_total_rows($criteria) ;
        $this->application->set_pagination($total_rows,site_url('financial/index'));

        $fields = array(
            "id", "description", "trans_category AS category", "amount", "IFNULL(cheque_no, ' - ') AS cheque_no",
            "IF(income,'Income','Expenditure') AS type",
            "date_made", "CONCAT(nic,' - ',first_name,'<br/>','(',designation,')') AS user_made","user_status",
            "trans_status AS status","creditor_debtor_id"
        );
        $limit = $this->config->item('rows_per_page');
        $join1 = array('tbl01_user', 'tbl15_financial.user_made = tbl01_user.nic');

        $criteria = array(
            "IF(income,'Income','Expenditure')" => $report_type,
            'trans_status' => 'completed', 
            'date_made >=' => $first_day_of_month, 
            'date_made <=' => $last_day_of_month
        );

        $transactions = $this->financial_model->get_transactions($fields,$criteria,$page,$limit,'',$join1);

        //prepare print string
        $str = $this->page_header;

        //report title & criteria
        $str .= "<table width='100%' >
        <tr><td colspan='3' align='center'><span class='report-title' >Monthly {$report_type} Report ({$report_month} - {$report_year})</span></td></tr>
        <tr>
            <td width='20%'>&nbsp;</td>
            <td width='60%' class='text-right' >Date:</td>
            <td width='20%'>{$date}</td>
        </tr>
        <tr>
            <td width='20%'>&nbsp;</td>
            <td width='60%' class='text-right'>User:</td>
            <td width='20%'>{$user}</td>
        </tr>
        <tr><td colspan='3' class='data-row'>&nbsp;</td></tr>
        </table>";

        //table headings
        $str .= "<table class='data' >
        <thead>
        <tr>
            <td class='data-heading text-center'>ID</td>
            <td class='data-heading text-center'>Category</td>
            <td class='data-heading text-center'>Description</td>
            <td class='data-heading text-center'>Date Made</td>
            <td class='data-heading text-center'>User Made</td>
            <td class='data-heading text-center'>Cheque</td>
            <td class='data-heading currency-mid'>Amount</td>
        <tr>
        </thead>
        <tbody>" ;

        if( count($transactions) == 0 ){
            $str .= "<tr><td colspan='7' align='center'>There is no result to display</td></tr>" ;
        }else{
            $total = 0;
            foreach( $transactions as $transaction ){
                $str .= "  <tr>
                <td class='data-row text-center'>{$transaction['id']}</td>
                <td class='text-center'>{$transaction['category']}</td>
                <td class='text-center'>{$transaction['description']}</td>
                <td class='text-center' >{$transaction['date_made']}</td>";
                if($transaction['user_status']) {
                    $str .= "<td>{$transaction['user_made']}</td>";
                } else {
                    $str .= "<td class='text-danger'>{$transaction['user_made']}</td>";
                }

                $str .=       "<td class='text-center'>{$transaction['cheque_no']}</td>
                <td class='currency-mid'>" . to_acc_currency($transaction['amount']) . "</td>";

                $str .=    "</td></tr>";
                $total += $transaction['amount'];
            }


            //add grand-total
            $str .= "<tr><td class='data-row'>&nbsp;</td></tr>
            <tr><td colspan='6' class='grand-total-mid text-right'><b>Total :</b></td>
            <td class='currency-mid grand-total-mid'>" . to_acc_currency($total) . "</td>
            </tr></tbody></table>" ;

        }


        //add grand-total
        $str .= "</tbody></table>" ;
        //add footer 
        $str .= $this->page_footer ;


        //generate PDF
        $file_name = "monthly_{strtolower($report_type)}_report_{$report_year}_{$report_month}.pdf" ;
        $this->pdf_A4_L->WriteHTML($this->css,1);
        $this->pdf_A4_L->WriteHTML($str,2);
        $this->pdf_A4_L->Output($file_name,'I');

    }

} //end of class
//end of file
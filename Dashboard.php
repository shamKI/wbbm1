<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    
    private $module = 'dashboard';
    public function __construct() {
        parent::__construct();
        $this->application->is_logged_in(TRUE);
        $this->load->model('charts/ChartModel');
    }

    public function index()
    {
        // Get top buyer
        $data['top_buyer'] = $this->ChartModel->get_top_buyer();

        // Get most sold book
        $data['top_sold_book'] = $this->ChartModel->get_top_sold_book();

        $data['module'] = $this->module;
        $data['user_name'] = $this->session->userdata('user_name');
        $this->load->view('dashboard/view', $data);
    }

}
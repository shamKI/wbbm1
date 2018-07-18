<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ChartData extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('charts/ChartModel');
    }

    // Get book category chart data : return JSON
    public function get_book_category_chart_data()
    {
        $chartData = $this->ChartModel->getBookCategoryChartData();

        $value_row = [];
        foreach ($chartData as $row) {
            array_push($value_row, [$row["category_name"], (int)$row["book_count"]]);
        };

        echo json_encode($value_row);
    }

}
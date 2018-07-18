<?php
class ChartModel extends CI_Model{

    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    // Get most sold book
    public function get_top_sold_book()
    {
        $this->db->select('b.*, sum(quantity_sold) as qty');
        $this->db->from('tbl08_sales_book sb');
        $this->db->join('tbl06_book b', 'sb.sales_sku = b.sku');
        $this->db->group_by('sb.sales_sku');
        $this->db->order_by("qty", "desc");
        $this->db->limit(1);

        $q = $this->db->get();

        return $q->row();
    }

    // Get Top buyer
    public function get_top_buyer()
    {
        $this->db->select('d.*, count(s.dealer_id) as cnt');
        $this->db->from('tbl07_sales s');
        $this->db->join('tbl05_dealer d', ' s.dealer_id = d.id');
        $this->db->group_by('s.dealer_id');
        $this->db->order_by("cnt", "desc");
        $this->db->limit(1);

        $q = $this->db->get();

        return $q->row();
    }

    // Get Book Chart Data
    public function getBookCategoryChartData()
    {
        $this->db->select('count(b.category_id) as book_count, c.category_name');
        $this->db->from('tbl06_book b');
        $this->db->join('tbl03_category c', 'b.category_id = c.id');
        $this->db->group_by('category_id');
        $q = $this->db->get();

        return $q->result_array();
    }

}
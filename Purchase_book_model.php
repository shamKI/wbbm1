<?php
class Purchase_book_model extends CI_Model{
		
	private $table = 'tbl12_purchase_book';
        
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }//end of function

    public function get_purchase_books($fields = '', $criteria = '', $offset = '', $limit = '', $order_by = '', $join1 = '', $group_by = '', $join2 = '', $join3 = '', $criteria_in = '') {
        if ($fields != '') { $this->db->select($fields); }
        if ($criteria != '') { $this->db->where($criteria); }
        if ($offset != '') { $this->db->offset($offset); }
        if ($limit != '') { $this->db->limit($limit); }
        if ($order_by != '') { 
            $this->db->order_by($order_by); 
        } else { 
            $this->db->order_by($this->table . '.purchase_sku ASC'); 
        }
        if ($join1 != '') { $this->db->join($join1[0], $join1[1], 'left'); }
        if ($join2 != '') { $this->db->join($join2[0], $join2[1], 'left'); }
        if ($join3 != '') { $this->db->join($join3[0], $join3[1], 'left'); }
        if ($group_by != '') { $this->db->group_by($group_by); }
        if ($criteria_in != '') { $this->db->where_in($criteria_in[0], $criteria_in[1]); }

        return $this->db->get($this->table)->result_array();
    }//end of function

    public function get_purchase_book($fields = '', $criteria = '', $join1 = '', $join2 = '') {
        if ($fields != '') { $this->db->select($fields); }
        if ($criteria != '') { $this->db->where($criteria); }
        if ($join1 != '') { $this->db->join($join1[0], $join1[1], 'left'); }
        if ($join2 != '') { $this->db->join($join2[0], $join2[1], 'left'); }

        return $this->db->get($this->table)->row_array();
    }//end of function

    public function add_purchase_books($data_set){
        return $this->db->insert_batch($this->table,$data_set);
    }//end of function

    public function update_purchase_book($data_set, $criteria) {
        return $this->db->update($this->table,$data_set, $criteria);
    }//end of function

    public function delete_purchase_book($criteria) {
        return $this->db->delete($this->table, $criteria);
    }//end of function
    
    public function update_purchase_books($data_set,$criteria){
        return $this->db->update_batch($this->table,$data_set,$criteria);
    }//end of function

    public function get_total_rows($criteria = '', $criteria_in = '') {
        if($criteria != '') { $this->db->where($criteria); }
        if($criteria_in != '') { $this->db->where_in($criteria_in[0], $criteria_in[1]); }
        return $this->db->get($this->table)->num_rows();
    }//end of function

}//end of class

//end of file
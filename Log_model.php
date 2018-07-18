
<?php
class Log_model extends CI_Model{
		
    private $table = 'tbl02_log';

    public function __construct(){

            parent::__construct();
            $this->load->database();

    }//end of function

    public function get_entries($fields = '', $criteria = '', $offset = '', $limit = '', $order_by = '', $join1 = '', $join2 = '', $group_by = ''){
        if($fields != '') { $this->db->select($fields); }
        if($criteria != '') { $this->db->where($criteria); }
        if($offset != '') { $this->db->offset($offset); }
        if($limit != '') { $this->db->limit($limit); }
        if($order_by != '') { 
            $this->db->order_by($order_by); 
        } else { 
            $this->db->order_by( $this->table . '.id DESC'); 
        }
        if($join1 != '') { $this->db->join($join1[0],$join1[1], 'left'); }
        if($join2 != '') { $this->db->join($join2[0],$join2[1], 'left'); }
        if($group_by != '') { $this->db->group_by($group_by); }
        return $this->db->get($this->table)->result_array();
    }//end of function

    public function add_entry($data_set) {
        $query_status = $this->db->insert($this->table,$data_set);
        if($query_status) {
            return $this->db->insert_id(); 
        } else {
            return false ;
        }
    }//end of function

    public function get_total_rows($criteria = '', $criteria_in = '') {
        if($criteria != '') { $this->db->where($criteria); }
        if($criteria_in != '') { $this->db->where_in($criteria_in[0], $criteria_in[1]); }
        return $this->db->get($this->table)->num_rows();
    }//end of function
    
    public function clear_log(){
        $this->db->truncate($this->table);
    }//end of function

}//end of class

//end of file
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Telegram_model extends CI_Model
{
	public function checkIfNew($id){
        $this->db->where('telegram', $id);
        $query = $this->db->get('telegram');
        return $query->row_array();
	}
	
	public function insertNew($data){
        $this->db->insert('telegram', $data); 
        if($this->db->affected_rows() > 0){
            return true;
        }else{
            return false;
        }
    }
   

}
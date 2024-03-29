<?php
defined('BASEPATH') OR exit('Access denied');

/**
 * Description of User
 *
 * @author Amir <amirsanni@gmail.com>
 * @date 5th Jum. Akhar, 1437AH (13th March, 2016)
 */
class User extends CI_Model{
    public function __construct(){
        parent::__construct();
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    /**
     * 
     * @param type $username
     * @param type $first_name
     * @param type $last_name
     * @param type $email
     * @param type $profession
     * @param type $mobile_1
     * @param type $mobile_2
     * @param type $password
     * @param type $logo
     * @param type $street
     * @param type $city
     * @param type $state
     * @param type $country
     * @return boolean
     */
    public function add($username, $first_name, $last_name, $email, $profession, $mobile_1, $mobile_2, $password, $logo,
                $street, $city, $state, $country){
        $data = ['username'=>$username, 'first_name'=>$first_name, 'last_name'=>$last_name, 'email'=>$email, 'mobile_1'=>$mobile_1,
            'mobile_2'=>$mobile_2, 'profession'=>$profession, 'password'=>$password, 'logo'=>$logo, 'state'=>$state, 'country'=>$country,
            'street'=>$street, 'city'=>$city];
        
        $this->db->set('signup_date', 'NOW()', FALSE);
        
        $this->db->insert('users', $data);
        
        if($this->db->insert_id()){
            return $this->db->insert_id();
        }
        
        else{
            return FALSE;
        }
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    /**
     * 
     * @param type $customerId
     * @param type $firstName
     * @param type $lastName
     * @param type $otherName
     * @param type $mobile1
     * @param type $mobile2
     * @param type $email
     * @param type $gender
     * @param type $address
     * @param type $city
     * @param type $state
     * @param type $country
     * @return boolean
     */
    public function update($title, $customerId, $firstName, $lastName, $otherName, $mobile1, $mobile2, $email, $gender, $address, $city, $state, $country){
        $data = ['firstName'=>$firstName, 'lastName'=>$lastName, 'otherName'=>$otherName, 'mobile1'=>$mobile1, 'mobile2'=>$mobile2,
            'email'=>$email, 'gender'=>$gender, 'address'=>$address, 'city'=>$city, 'state'=>$state, 'country'=>$country, 'title'=>$title];
        
        $this->db->where('custId', $customerId);
        
        $this->db->update('customers', $data);
        
        return TRUE;
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    /**
     * 
     * @param type $order_by
     * @param type $order_format
     * @param type $start
     * @param type $limit
     * @return boolean
     */
    public function get_all($order_by, $order_format, $start, $limit){        
        $this->db->select('id, username, first_name, last_name, email, profession, mobile_1, mobile_2');
        $this->db->order_by($order_by, $order_format);
        $this->db->limit($limit, $start);
        
        $run_q = $this->db->get('users');
        
        if($run_q->num_rows() > 0){
            return $run_q->result();
        }
        
        else{
            return FALSE;
        }
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
   public function get_user_more_details($user_id){
       $this->db->select('logo, street, city, state, country, count(projects.id) as "total_projects_created", signup_date');
       $this->db->join('projects', 'users.id=projects.user_id', 'LEFT');
       $this->db->where('users.id', $user_id);
       
       $run_q = $this->db->get('users');
       
       if($run_q->num_rows()){
           return $run_q->result();
       }
       
       else{
           return FALSE;
       }
   }




   /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    

    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */

    public function user_search($value){
        $q = "SELECT * FROM customers WHERE 
                first_name LIKE '%".$this->db->escape_like_str($value)."%'
                || last_name LIKE '%".$this->db->escape_like_str($value)."%' 
                || username LIKE '%".$this->db->escape_like_str($value)."%'
                || email LIKE '%".$this->db->escape_like_str($value)."%'
                || mobile_1 LIKE '%".$this->db->escape_like_str($value)."%'
                || mobile_2 LIKE '%".$this->db->escape_like_str($value)."%' 
                || city LIKE '%".$this->db->escape_like_str($value)."%'
                || state LIKE '%".$this->db->escape_like_str($value)."%'
                || country LIKE '%".$this->db->escape_like_str($value)."%'";
        
        $run_q = $this->db->query($q, [$value, $value, $value, $value, $value, $value, $value, $value, $value, $value]);
        
        if($run_q->num_rows() > 0){
            return $run_q->result();
        }
        
        else{
            return FALSE;
        }
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    
    
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
}

<?php
defined('BASEPATH') OR exit('');

/**
 * Description of Users
 *
 * @author Amir <amirsanni@gmail.com>
 * @date 12th March, 2016
 */
class Users extends CI_Controller{
    
    public function __construct(){
        parent::__construct();
        
        $this->genlib->checkLogin();
        
        $this->load->model(['user']);
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    public function index(){
        $data['pageContent'] = $this->load->view('users/users', '', TRUE);
        $data['pageTitle'] = "Users";
        
        $this->load->view('main', $data);
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    /**
     * lau_ = "Load all users"
     */
    public function lau_(){
        //set the sort order
        $order_by = $this->input->get('orderBy', TRUE) ? $this->input->get('orderBy', TRUE) : "first_name";
        $order_format = $this->input->get('orderFormat', TRUE) ? $this->input->get('orderFormat', TRUE) : "ASC";
        
        //count the total users in db
        $total_users = $this->db->count_all('users');
        
        $this->load->library('pagination');
        
        $page_number = $this->uri->segment(3, 0);//set page number to zero if the page number is not set in the third segment of uri
	
        $limit = $this->input->get('limit', TRUE) ? $this->input->get('limit', TRUE) : 10;//show $limit per page
        $start = $page_number == 0 ? 0 : ($page_number - 1) * $limit;//start from 0 if $page_number is 0, else start from the next iteration
        
        //call setPaginationConfig($totalRows, $urlToCall, $limit, $attributes, $uri_segment=3) in genlib to configure pagination
        $config = $this->genlib->setPaginationConfig($total_users, "users/lau_", $limit, ['class'=>'lnp'], "");
        
        $this->pagination->initialize($config);//initialize the library class
        
        //get all users from db
        $data['all_users'] = $this->user->get_all($order_by, $order_format, $start, $limit);
        $data['range'] = $total_users > 0 ? ($start+1) . "-" . ($start + count($data['all_users'])) . " of " . $total_users : "";
        $data['links'] = $this->pagination->create_links();//page links
        $data['sn'] = $start+1;
        
        $json['usersTable'] = $this->load->view('users/all_users', $data, TRUE);//get view with populated customers table

        $this->output->set_content_type('application/json')->set_output(json_encode($json));
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    /**
     * Get more details about a users, not just bio. Might include number of created projects and the like
     */
    public function get_user_more_details(){
        $this->genlib->ajaxOnly();
        
        $user_id = $this->input->post('user_id', TRUE);
        
        //call model to get info
        $user_info = $this->user->get_user_more_details($user_id);
        
        if($user_info){
            
            foreach($user_info as $get){
                $data['logo_url'] = $get->logo ? base_url() . $get->logo : "../aura_users/default_logo.jpg";
                $street = $get->street ? $get->street . ", " : "";
                $city = $get->city ? $get->city . ", " : "";
                $state = $get->state ? $get->state . ", " : "";
                $country = $get->country ? $get->country : "";
                $data['address'] = $street . $city . $state . $country;
                $data['total_projects_created'] = $get->total_projects_created;
                $data['reg_date'] = date('jS M, Y h:ia', strtotime($get->signup_date));
            }
            
            $this->load->view('users/user_details', $data);
        }
        
        else{
            echo "";
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
     * To add new user (admin can do this, perhaps for a limited time)
     */
    public function add(){
        $this->genlib->ajaxOnly();
        
        $this->load->library('form_validation');

        $this->form_validation->set_error_delimiters('', '');
        
        $this->form_validation->set_rules('username', 'Username', ['required', 'trim', 'is_unique[users.username]'], 
                ['required'=>"required", 'is_unique'=>"Username exists"]);
        $this->form_validation->set_rules('first_name', 'First name', ['required', 'trim', 'max_length[20]', 'strtolower', 'ucfirst'], 
                ['required'=>"required"]);
        $this->form_validation->set_rules('last_name', 'Last name', ['required', 'trim', 'max_length[20]', 'strtolower', 'ucfirst'], 
                ['required'=>"required"]);
        $this->form_validation->set_rules('email', 'Email', ['required', 'trim', 'valid_email', 'is_unique[users.email]', 'strtolower'], 
                ['required'=>"required", 'is_unique'=>"This email is already attached to a member"]);
        $this->form_validation->set_rules('profession', 'Profession', ['required', 'trim', 'strtolower', 'ucfirst'], ['required'=>"required"]);
        $this->form_validation->set_rules('mobile_1', 'Phone number', ['required', 'trim', 'numeric', 'max_length[15]', 'min_length[11]', 'is_unique[users.mobile_1]'], 
                ['required'=>"required", 'is_unique'=>"This number is already attached to a user"]);
        $this->form_validation->set_rules('mobile_2', 'Other number', ['trim', 'numeric', 'max_length[15]', 'min_length[11]']);
        $this->form_validation->set_rules('password', 'Password', ['required'], ['required'=>"required"]);
        $this->form_validation->set_rules('passwordConf', 'Confirm Password', ['required', 'matches[password]'], ['required'=>"required"]);
        $this->form_validation->set_rules('street', 'Street', '', '');
        $this->form_validation->set_rules('city', 'City', ['strtolower', 'ucfirst'], '');
        $this->form_validation->set_rules('state', 'State', ['strtolower', 'ucfirst'], '');
        $this->form_validation->set_rules('country', 'Country', ['strtolower', 'ucfirst'], '');
        
        
        if($this->form_validation->run() !== FALSE){
            
            //move logo to disk and get url if logo was uploaded
            if(!empty($_FILES['logo']['tmp_name'])){
                /*
                 * upload_logo method will try to upload file and return status based on the success or failure of the upload
                 * The status and msg will be returned to the client.
                 */
                $logo_info = $this->upload_logo($_FILES['logo'], set_value('email'));
                
                //insert details if logo was uploaded successfully
                $inserted_id = $logo_info['status'] === 1 
                    ? 
                    $this->user->add(set_value('username'), set_value('first_name'), set_value('last_name'), set_value('email'), 
                    set_value('profession'), set_value('mobile_1'), set_value('mobile_2'), set_value(password_hash('password', PASSWORD_BCRYPT)), 
                    $logo_info['logo_url'], set_value('street'), set_value('city'), set_value('state'), set_value('country')) 
                    : 
                    "";
                
                $json['status'] = $inserted_id ? 1 : 0;
                $json['logo_error'] = $logo_info['logo_error_msg'];
            }
            
            
            else{
            
                /**
                 * insert info into db
                 * function header: add($username, $first_name, $last_name, $email, $profession, $mobile_1, $mobile_2, $password, $logo
                 * $street, $city, $state, $country)
                 */
                $inserted_id = $this->user->add(set_value('username'), set_value('first_name'), set_value('last_name'), set_value('email'), 
                    set_value('profession'), set_value('mobile_1'), set_value('mobile_2'), set_value(password_hash('password', PASSWORD_BCRYPT)), '', 
                    set_value('street'), set_value('city'), set_value('state'),set_value('country'));
                
                //send welcome email to user
                //$inserted_id ? $this->genlib->sendWelcomeMessage($membershipId, $memberName, set_value('email')) : "";

                $json['status'] = $inserted_id ? 1 : 0;
            
            }
        }
        
        else{
            //return all error messages
            $json = $this->form_validation->error_array();//get an array of all errors
            
            $json['msg'] = "One or more required fields are empty or not correctly filled";
            $json['status'] = 0;
        }
                    
        $this->output->set_content_type('application/json')->set_output(json_encode($json));
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    private function upload_logo($file, $email){
        $json = [];
        
        if(!empty($file)){
            
            /*
             * We replace the '.' and '@' chars from the email to prevent folder naming error as it
             * will be used as the name of the user's folder
             */
            $stringified_email = str_replace(['@', '.'], ['at', 'dot'], $email);
            
            //make dir to upload logo
            if(!file_exists("../aura_users/{$stringified_email}")){
                mkdir("../aura_users/{$stringified_email}");
            }
            
            $config['file_name'] = "my_logo";//use this as the name of all user's logos
            $config['upload_path'] = "../aura_users/{$stringified_email}/";//files are stored outside the app root
            $config['allowed_types'] = 'jpg|png|jpeg|jpe';
            $config['file_ext_tolower'] = FALSE;
            $config['encrypt_name'] = TRUE;
            $config['max_size'] = 500;//in kb

            $this->load->library('upload', $config);//load CI's 'upload' library

            if($this->upload->do_upload('logo') == FALSE){
                $msg = $this->upload->display_errors();
                
                $json = ['logo_error_msg'=>$msg, 'status'=>0];
            }

            else{
                //get array of file info on success
                $data = $this->upload->data();

                //set values to insert into db
                $file_name = $data['file_name'];//new file name with the extension
                $logo_url = "download/logo/{$stringified_email}/{$file_name}";//link that will be visible to users
                
                $json = ['status'=>1, 'logo_url'=>$logo_url, 'logo_error_msg'=>''];
            }
        }
        
        
        else{
            $json = ['status'=>0, 'logo_error_msg'=>"No image was selected"];
        }
        
        
        return $json;
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
     */
    public function update(){
        $this->genlib->ajaxOnly();
        
        $this->load->library('form_validation');

        $this->form_validation->set_error_delimiters('', '');
        
        $this->form_validation->set_rules('title', 'Title', ['trim', 'max_length[25]', 'strtolower', 'ucfirst']);
        $this->form_validation->set_rules('firstName', 'First name', ['required', 'trim', 'max_length[20]', 'strtolower', 'ucfirst'], 
                ['required'=>"required"]);
        $this->form_validation->set_rules('lastName', 'Last name', ['required', 'trim', 'max_length[20]', 'strtolower', 'ucfirst'], 
                ['required'=>"required"]);
        $this->form_validation->set_rules('otherName', 'Other names', ['trim', 'max_length[30]', 'strtolower', 'ucfirst']);
        $this->form_validation->set_rules('mobile1', 'Phone number', ['required', 'trim', 'numeric', 'max_length[15]', 
            'min_length[11]', 'callback_crosscheckMobile['. $this->input->post('custId', TRUE).']'], ['required'=>"required"]);
        $this->form_validation->set_rules('mobile2', 'Other number', ['trim', 'numeric', 'max_length[15]', 'min_length[11]']);
        $this->form_validation->set_rules('email', 'Email', ['required', 'trim', 'valid_email', 'callback_crosscheckEmail['. $this->input->post('custId', TRUE).']']);
        $this->form_validation->set_rules('gender', 'Gender', ['required', 'trim'], ['required'=>"required"]);
        $this->form_validation->set_rules('membershipId', 'Membership ID', ['required', 'trim', 'numeric', 
            'callback_crosscheckMembershipId['. $this->input->post('custId', TRUE).']'], ['required'=>"required"]);
        $this->form_validation->set_rules('address', 'Address', ['required'], ['required'=>"required"]);
        $this->form_validation->set_rules('city', 'City', ['required'], ['required'=>"required", 'strtolower', 'ucfirst']);
        $this->form_validation->set_rules('state', 'State', ['required'], ['required'=>"required", 'strtolower', 'ucfirst']);
        $this->form_validation->set_rules('country', 'Country', ['required'], ['required'=>"required", 'strtolower', 'ucfirst']);
        
        if($this->form_validation->run() !== FALSE){
            $this->db->trans_start();
            
            /**
             * update info in db
             * function header: update($customerId, $firstName, $lastName, $otherName, $mobile1, $mobile2, $email, $gender, $address, $city, $state, $country)
             */
				
            $customerId = $this->input->post('custId', TRUE);

            $updated = $this->customer->update(set_value('title'), $customerId, set_value('firstName'), set_value('lastName'),
                    set_value('otherName'), set_value('mobile1'), set_value('mobile2'), set_value('email'), set_value('gender'), 
                    set_value('address'), set_value('city'), set_value('state'), set_value('country'));
            
            $membershipId = $this->genmod->gettablecol('customers', 'membershipId', 'custId', $customerId);
            
            //insert into eventlog
            //function header: addevent($event, $eventRowId, $eventDesc, $eventTable, $staffId)
            $desc = "The details of member with membership ID '$membershipId' was updated";
            
            $updated ? $this->genmod->addevent("Member details update", $customerId, $desc, "customers", $this->session->admin_id) : "";
            
            $this->db->trans_complete();
            
            $json = $updated ? 
                    ['status'=>1, 'msg'=>"Member info successfully updated"] 
                    : 
                    ['status'=>0, 'msg'=>"Oops! Unexpected server error! Pls contact administrator for help. Sorry for the embarrassment"];
            
            //notify member of update
            $memberName = set_value('firstName')." ".set_value('lastName')." ".set_value('otherName');
            $this->genlib->sendMemberUpdateMsg($memberName, set_value('email'));
        }
        
        else{
            //return all error messages
            $json = $this->form_validation->error_array();//get an array of all errors
            
            $json['msg'] = "One or more required fields are empty or not correctly filled";
            $json['status'] = 0;
        }
                    
        $this->output->set_content_type('application/json')->set_output(json_encode($json));
    }
    
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    /**
     * Used as a callback while updating user's info to ensure 'mobile_1' field does not contain a number already used by another user
     * @param type $mobile_number
     * @param type $user_id
     */
    public function crosscheckMobile($mobile_number, $user_id){
        //check db to ensure number was previously used for user with $user_id i.e. the same user we're updating
        $user_with_num = $this->genmod->getTableCol('users', 'id', 'mobile_1', $mobile_number);
        
        //if number does not exist or it exist but was used by current user
        if(!$user_with_num || ($user_with_num == $user_id)){
            return TRUE;
        }
        
        else{//if it exist and was used by another customer
            $this->form_validation->set_message('crosscheckMobile', 'This number is already used by another user');
                
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
     * Used as a callback while updating cust info to ensure 'email' field does not contain an email already used by another user
     * @param type $email
     * @param type $user_id
     */
    public function crosscheckEmail($email, $user_id){
        //check db to ensure email was previously used for user with $user_id i.e. the same user we're updating his details
        $user_with_email = $this->genmod->getTableCol('users', 'id', 'email', $email);
        
        //if email does not exist or it exist but was used by current user
        if(!$user_with_email || ($user_with_email == $user_id)){
            return TRUE;
        }
        
        else{
            $this->form_validation->set_message('crosscheckEmail', 'This email is already used by another user');
                
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
    
    /**
     * To get user's biodata only
     */
    public function get_user_bio(){
        $this->genlib->ajaxOnly();
        
        $user_id = $this->input->post('user_id', TRUE);
        
        //call model to get info
        $user_info = $this->customer->getCustBio($user_id);
        
        if($user_info){
            
            foreach($user_info as $get){
                $json['title'] = $get->title;
                $json['firstName'] = $get->firstName;
                $json['lastName'] = $get->lastName;
                $json['otherName'] = $get->otherName;
                $json['custId'] = "CUS-ID-".$get->custId;
                $json['mobile1'] = $get->mobile1;
                $json['mobile2'] = $get->mobile2;
                $json['email'] = $get->email;
                $json['gender'] = $get->gender;
                $json['membershipId'] = $get->membershipId;
                $json['address'] = $get->address;
                $json['city'] = $get->city;
                $json['state'] = $get->state;
                $json['country'] = $get->country;
            }
            
            $json['status'] = 1;
        }
        
        else{
            $json = ['status'=>0];
        }
        
        $this->output->set_content_type('application/json')->set_output(json_encode($json));
    }
    
    
    /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
    
    
    /**
     * Get all projects created by a user
     */
    public function get_user_projects(){
        $this->genlib->ajaxOnly();
        
        $this->load->model('project');
        $this->load->helper('text');
        
        $user_id = $this->input->get('user_id', TRUE);
        
        //set the sort order (itemName, quantity, unitPrice, totalPrice, transDate[default])
        $order_by = $this->input->get('order_by') ? $this->input->get('order_by', TRUE) : "projects.date_created";
        $order_format = $this->input->get('order_format') ? $this->input->get('order_format', TRUE) : "DESC";
        
        //count the total number of transactions customer was involved
        $total_projects = count($this->project->get_user_projects($user_id, $order_by, $order_format, '', ''));
        
        $this->load->library('pagination');
        
        $page_number = $this->uri->segment(3, 0);//set page number to zero if the page number is not set in the third segment of uri
	
        $limit = $this->input->get('limit') ? $this->input->get('limit', TRUE) : 10;//show $limit per page
        $start = $page_number == 0 ? 0 : ($page_number - 1) * $limit;//start from 0 if pageNumber is 0, else start from the next iteration
        
        //call setPaginationConfig($totalRows, $urlToCall, $limit, $attributes) in genlib to configure pagination
        $config = $this->genlib->setPaginationConfig($total_projects, "users/get_user_projects", $limit, ['class'=>'lupnp']);
        
        $this->pagination->initialize($config);//initialize the library class
        
        //get projects
        $user_projects = $this->project->get_user_projects($user_id, $order_by, $order_format, $start, $limit);
        
        if($user_projects){//if at least one result is returned
            $data['user_projects'] = $user_projects;
            $data['sn'] = $start+1;//table SN
            
            //load transactions table
            $json['userProjectListTable'] = $this->load->view('users/user_project_list_table', $data, TRUE);
            
            //other info to return
            $json['range'] = $total_projects > 0 ? ($start+1) . "-" . ($start + count($user_projects)) . " of " . $total_projects : "";//range being displayed
            $json['links'] = $this->pagination->create_links();//page links
            $json['userName'] = trim($this->genmod->gettablecol('users', 'CONCAT_WS(" ", first_name, last_name)', 'id', $user_id));
            $json['status'] = 1;
        }
        
        else{
            $json = ['status'=>0];
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($json));
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
    
    




   /*
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    ********************************************************************************************************************************
    */
}
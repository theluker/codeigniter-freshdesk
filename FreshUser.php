<?php 

class FreshUser {

	private $CI;
		
	public function __construct($fresh_config)
    {
	    // Grab the CodeIgniter Super Variable thingy...	
	  	$this->CI =& get_instance();
        $this->CI->load->library('session');
        
        // Set variables from config
        $this->api_key 			= $fresh_config['api_key'];
        $this->category_id 		= $fresh_config['category_id'];

	} // end construct

    private function request($url, $method = 'get', $data = null) {
        $ch = curl_init ("https://ondatasuite.freshdesk.com/{$url}");        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->api_key}:X");   
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        # TODO: post data to json endpoint
        # curl_setopt($ch, );
        
        $data = json_decode(curl_exec($ch));
        curl_close($ch);
        return $data;
    }


	// Get User Name
	// Argument: user_id
	// Returns: 
	// Request URL: domain_URL/contacts/[id].json
	// Request method: GET
	function get_user_info($user_id) 
	{
		return $this->request('contacts/{$user_id}.json', 'get');
	}
	
	// Viewing all users
	// Request URL: domain_URL/contacts.json
	// Request method: GET
	function get_all_users() 
	{
		return $this->request('contacts/contacts.json', 'get');
	}
	
	// Modify a User
	// Arguments: user_id 
	// Request URL: domain_URL/contacts/[id].json
	// Request method: PUT
	function _modify_user($user_id) 
	{
		
	}
	
	// Delete a User
	// Arguments: user_id 
	// Request URL: domain_URL/contacts/[id].json
	// Request method: DELETE
	function _delete_user($user_id) 
	{
		return $this->request('https://ondatasuite.freshdesk.com/contacts/'.$user_id.'.json', 'delete');
	}
	
	
	
}
?>
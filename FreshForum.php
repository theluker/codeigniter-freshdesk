<?php 

class FreshForum {

    private $CI;
	
    public function __construct($config)
    {
        $this->CI =& get_instance();
        $this->CI->load->library('session');
        
        $this->CI->user['email'];
        
        // Set variables from config
        $this->api_key 		= $config['api_key'];
        $this->category_id 	= $config['category_id'];
        
    } // end construct
        
    private function _request($url, $method = 'get', $data = null) {
        $ch = curl_init ("{$fresh_baseurl}/{$url}");        
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
    
    // ********************************
    // ************ FORUM CATEGORIES  *
    // ********************************

    // Create a forum category
    //
    // Arguments: name (mandatory), description (optional)
    // Request URL: domain_URL/categories.json
    // Request method: POST
    function create_forum_category($name, $description = '') 
    {
        $data = array(
            'name' => $name,
            'description' => $description,
        );
        return $this->_request('categories.json', 'post', $data);
    }
    
    // View all Forum Categories
    // Returns: (json) All forum categories available in help desk 
    // Arguments: None
    // Request URL: domain_URL/categories.json
    // Request method: GET
    function get_forum_categories() 
    {
        return $this->_request('categories.json');
    }
        
    // Viewing Forums in a Forum Category
    // Returns: (json) All forums in a specific forum category
    // Arguments: id
    // Request URL: domain_URL/categories/{id}.json
    // Request method: GET
    function get_forums_from_category() 
    {
        return $this->_request("categories/{$this->category_id}.json");
    }
    
    // Update a Forum Category
    // Returns: Nothing - This helps you to update/modify/edit an existing forum category
    // Arguments: name, description 
    // Request URL: domain_URL/categories/[category_id].json
    // Request method: PUT
    function _update_forum_category($category_id, $name, $description = '') 
    {
        $data = array(
            'name' => $name,
            'description' => $description,
        );
        return $this->_request('categories/{$category_id}.json', 'post', $data);
    }
    
    // Delete a Forum Category
    // Returns: 
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id].json
    // Request method: DELETE 
    function _delete_forum_category($category_id) 
    {
        return $this->_request("categories/{$category_id}.json", 'delete');
    }
    // ********************************
    // *********************** FORUMS *
    // ********************************

    // Create a new forum
    // Returns:
    // Arguments: description (Optional), forum-type, forum-visibility, name
    // Request URL: domain_URL/categories/[category_id]/forums.json
    // Request method: POST
    function _create_forum($category_id, $name, $type, $visibility, $description = '') 
    {
        $data = array(
            'name' => $name,
            'forum-type' => $type,
            'forum-visibility' => $visibility,
        );
        return $this->_request("categories/{$category_id}/forums/forums.json", 'post', $data);
    }
    // View topics in a forum
    // Returns: forum_id, cat_id
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[id].json
    // Request method: GET
    function get_forum_topics($forum_id) 
    {
        return $this->_request("categories/{$this->category_id}/forums/{$forum_id}.json", 'get');
    }
    
    function get_forum_name($forum_id)
    {
	    $forum = $this->_request("categories/{$this->category_id}/forums/{$forum_id}.json", 'get');
		return $forum->forum->name;
    }
    // Update a forum
    // Returns:
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id].json
    // Request method: PUT
    function _update_forum($category_id, $forum_id) 
    {
        return $this->_request("categories/{$category_id}/forums/{$forum_id}.json", 'put');
    }
    // Delete a forum
    // Returns:
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id].json
    // Request method: DELETE
    function _delete_forum($category_id, $forum_id) 
    {
        return $this->_request("categories/{$category_id}/forums/{$forum_id}", 'delete');
    }

    
    // ********************************
    // *********************** TOPICS *
    // ********************************

    // View all Posts in a Topic
    // Returns:
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[id].json
    // Request method: GET
    function get_posts_from_topic($forum_id, $topic_id)
    {
        return $this->_request("categories/{$this->category_id}/forums/{$forum_id}/topics/{$topic_id}.json", 'get'); 
    }
    // Create a topic
    // Returns:
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics.json
    // Request method: POST
    function create_topic($forum_id, $title, $description_html) 
    {
	     $data = array(
	     	'name' => $this->user['name'],
            'title' => $title,
            'description_html' => $description_html
        );
        
        return $this->_request("categories/{$this->category_id}/forums/{$forum_id}/topics.json", 'post', $data); 
    }
    // Update a topic
    // Returns: Status Code
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id].json
    // Request method: PUT
    function _update_topic()
    {
        return $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json", 'put'); 
    }
    // Delete Topic
    // Returns: Nothing
    // Arguments:
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id].json
    // Request method: DELETE
    function _delete_topic()
    {
        return $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json", 'delete'); 
    }
    // Reply to a topic
    // Returns: Nothing
    // Arguments:
    // Request URL: domain_URL/posts.json?forum_id=[forum_id]&category_id=[category_id]&topic_id=[topic_id]
    // Request method: POST
    function reply_topic() 
    {
        return $this->_request("posts.json?forum_id={$forum_id}&category_id={$category_id}&topic_id={$topic_id}", 'post');
    }
    // Edit Comment
    // Returns: Nothing
    // Arguments: 
    // Request URL: domain_URL/posts/[post_id].json?forum_id=[forum_id]&category_id=[category_id]&topic_id=[topic_id]
    // Request method: PUT
    function _edit_comment() 
    {
        return $this->_request("posts.json?forum_id={$forum_id}&category_id={$category_id}&topic_id={$topic_id}", 'put');
    }
    // Delete Comment
    // Returns: Nothing
    // Arguments: 
    // Request URL: domain_URL/posts/[post_id].json?forum_id=[forum_id]&category_id=[category_id]&topic_id=[topic_id]
    // Request method: DELETE
    function _delete_comment() 
    {
        return $this->_request("posts.json?forum_id={$forum_id}&category_id={$category_id}&topic_id={$topic_id}", 'delete');   
    }
    // Monitor forum topic
    // Returns: Nothing
    // Arguments: 
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id]/monitorship.json 
    // Request method: POST
    function _monitor_topic ()
    {
        return $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}/monitorship.json", 'post');
    }
    // Unmonitor a forum topic
    // Returns: Nothing
    // Arguments:
    // Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id]/monitorship.json 
    // Request method: DELETE
    function _unmonitor_topic () 
    {
        return $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}/monitorship.json", 'delete');
    }
        
}
?>

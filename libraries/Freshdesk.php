<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * FreshDesk Library
 */
class Freshdesk
{
    private $CI;

    protected $api_key;
    protected $base_url;

    public $Category;

    public function __construct($params = array())
    {
        // Get CI instance
        $this->CI =& get_instance($params);

        // Attempt to load config values from file
        if ($config = $this->CI->config->load('freshdesk', TRUE, TRUE))
        {
            $this->api_key = $this->CI->config->item('api_key', 'freshdesk');
            $this->base_url = $this->CI->config->item('base_url', 'freshdesk');
        }
        // Attempt to load config values from params
        if ($api_key = @$params['api_key'] and $base_url = @$params['base_url'])
        {
            $this->api_key = $api_key;
            $this->base_url = $base_url;
        }

        // Instantiate API accessors
        $this->ForumCategory = new FreshDeskForumCategory($this->base_url, $this->api_key);
    }
}

/**
 * FreshDesk API
 */
class FreshDeskAPI
{
    private $api_key;
    protected $base_url;    

    public function __construct($base_url, $api_key)
    {
        $this->api_key  = $api_key;
        $this->base_url = $base_url;
    }

    /**
     * Perform an API request.
     * 
     * @param  string $resource Freshdesk API resource
     * @param  string $method   HTTP request method
     * @param  array  $data     HTTP PUT/POST data
     * @return mixed            JSON object or HTTP response code
     */
    protected function _request($resource, $method = 'GET', $data = null)
    {
        $ch = curl_init ("{$this->base_url}/{$resource}");        
        $headers = array('Content-Type: application/json');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->api_key}:X");   
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if ($data)
        {
            $data = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $headers[] = 'Content-Length: ' . strlen($data);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        log_message('debug', var_dump($info, $data));
        if (curl_errno($ch) and $error = curl_error($ch)) 
        {
            log_message('error', var_dump($error));
            curl_close($ch);
            return FALSE;
        }
        if (in_array($info['http_code'], array(404, 406)) and $error = $data) 
        {
            log_message('error', var_dump($error));
            curl_close($ch);
            return FALSE;   
        }        
        curl_close($ch);

        // Return JSON object if data was returned
        if ($data = json_decode($data))
        {
            return $data;
        }

        // Return HTTP response code by default
        return $info['http_code'];
    }
}

/**
 * FreshDesk Forum Category
 *
 * Create, View, Update, and Delete Forum Categories.
 *
 * @link http://freshdesk.com/api/forums/forum-category
 */
class FreshDeskForumCategory extends FreshDeskAPI
{
    /**
     * Create a new Forum Category.
     * 
     * Request URL: domain_URL/categories.json
     * Request method: POST
     * 
     * Request:
     *     array = (
     *         'forum-category' => array(
     *             'name'        => 'Test',                # required
     *             'description' => 'New testing category' # optional
     *         )
     *     );
     * Response:
     *     stdClass Object (
     *         [created_at] => 2011-03-15T02:23:15-07:00
     *         [description] => Welcome to the Freshdesk community forums
     *         [id] => 2
     *         [name] => Freshdesk Forums
     *         [position] => 1
     *         [updated_at] => 2011-03-21T02:42:58-07:00
     *     );
     *
     * @link http://freshdesk.com/api/forums/forum-category#create-a-forum-category
     * 
     * @param  string $name        Forum Category Name
     * @param  string $description Forum Category Description
     * @return object              Forum Category object
     */
    public function create($name, $description = '')
    {
        // Build array of request data
        $data = array(
            'name' => $name,
            'description' => $description
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories.json", 'POST', $data))
        {
            return FALSE;
        }

        // Extract and return category data from its 'forum_category' container
        return $response->forum_category;
    }

    /**
     * View all Forum Categories.
     *
     * Request URL: domain_URL/categories.json
     * Request method: GET
     *
     * Response:
     *   Array (
     *       [0] => stdClass Object (
     *           [created_at] => 2011-03-15T02:23:15-07:00
     *           [description] => Welcome to the Freshdesk community forums
     *           [id] => 2
     *           [name] => Freshdesk Forums
     *           [position] => 1
     *           [updated_at] => 2011-03-21T02:42:58-07:00
     *       )
     *       ...
     *   );
     * 
     * @link   http://freshdesk.com/api/forums/forum-category#view-all-forum-categories
     * 
     * @return array Array of Forum Category Objects
     */
    public function get_all()
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories.json"))
        {
            return FALSE;
        }
        
        // Default category array
        $categories = array();

        // Return empty array if HTTP 200 received
        if ($response == 200)
        {
            return $categories;
        }

        // Extract category data from its 'forum_category' container
        foreach ($response as $category)
        {
            $categories[] = $category->forum_category;
        }

        // Return restructured array of categories
        return $categories;
    }

    /**
     * View Forums in a Category.
     *
     * Request URL: domain_URL/categories/[id].json
     * Request method: GET
     *
     * Response:
     *     stdClass Object (
     *         [created_at] => 2011-03-15T02:23:15-07:00
     *         [description] => Welcome to the Freshdesk community forums
     *         [id] => 2
     *         [name] => Freshdesk Forums
     *         [position] => 1
     *         [updated_at] => 2011-03-21T02:42:58-07:00
     *         [forums] => Array (
     *             [0] => stdClass Object (
     *                 [description] => General helpdesk announcements to the customers.
     *                 [description_html] => <p>General helpdesk announcements to the customers.</p>
     *                 [forum_category_id] => 2
     *                 [forum_type] => 4
     *                 [forum_visibility] => 1
     *                 [id] => 6
     *                 [name] => Announcements
     *                 [position] => 1
     *                 [posts_count] => 0
     *                 [topics_count] => 0
     *             )
     *             ...
     *         )
     *   );
     *
     * @link   http://freshdesk.com/api/forums/forum-category#viewing-forums-in-a-category
     * 
     * @param  integer $category_id Forum Category ID
     * @return mixed                Array or singleton Forum Category Object
     */
    public function get($category_id = null)
    {
        // Return all categories if no Category ID was passed
        if ( ! $category_id)
        {
            return $this->get_all();
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.json"))
        {
            return FALSE;
        }

        // Extract and return category data from its 'forum_category' container
        return $response->forum_category;
    }

    /**
     * Updated an existing Forum Category.
     *
     * Request URL: domain_URL/categories/[category_id].json
     * Request method: PUT
     *
     * Request:
     *     Array (
     *         'forum-category' => Array (
     *             'name'        => 'Test',                # optional
     *             'description' => 'New testing category' # optional
     *         )
     *     );
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/forums/forum-category#update-a-forum-category
     * 
     * @param  integer $category_id Forum Category ID
     * @param  string  $name        Forum Category Name
     * @param  string  $description Forum Category Description
     * @return integer              HTTP response code             
     */
    public function update($category_id, $name = '', $description = '')
    {
        // Build array of request data
        $data = array(
            'name' => $name,
            'description' => $description,
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.json", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP 200 code
        return $response;
    }

    /**
     * Delete an existing Forum Category.
     *
     * Request URL: domain_URL/categories/[category_id].json
     * Request method: DELETE
     *
     * Response:
     *     stdClass Object (
     *         [created_at] => 2011-03-15T02:23:15-07:00
     *         [description] => Welcome to the Freshdesk community forums
     *         [id] => 2
     *         [name] => Freshdesk Forums
     *         [position] => 1
     *         [updated_at] => 2011-03-21T02:42:58-07:00
     *     );
     *         
     * @link   http://freshdesk.com/api/forums/forum-category#delete-a-forum-category
     * 
     * @param  integer $category_id Forum Category ID
     * @return object               Forum Category object
     */
    public function delete($category_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.json", 'DELETE'))
        {
            return FALSE;
        }

        // Extract and return category data from its 'forum_category' container
        return $response->forum_category;
    }
}

/* End of file Freshdesk.php */

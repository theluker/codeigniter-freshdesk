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
     * @param  string $endpoint Freshdesk API endpoint
     * @param  string $method   HTTP request method
     * @param  array  $data     HTTP PUT/POST data
     * @return mixed            SimpleXML object or HTTP response code
     */
    protected function _request($endpoint, $method = 'GET', $data = null)
    {
        $method = strtoupper($method);

        $ch = curl_init ("{$this->base_url}/{$endpoint}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->api_key}:X");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Convert array of data to XML
        if ($data)
        {
            $index = array_keys($data)[0];
            $xml = new SimpleXMLElement("<{$index}/>");
            array_walk_recursive(array_flip($data[$index]), array ($xml, 'addChild'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
        }
        
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        log_message('debug', var_dump($info, htmlspecialchars($data)));
                
        // CURL error handling
        if (curl_errno($ch) and $error = curl_error($ch)) 
        {
            log_message('error', var_dump($error));
            curl_close($ch);
            return FALSE;
        }
        if (in_array($info['http_code'], array(404, 406, 302)) and $error = $data) 
        {
            log_message('error', var_dump($error));
            curl_close($ch);
            return FALSE;   
        }
        curl_close($ch);

        // Load SimpleXML object if data was returned and properly parsed
        if ($data = @simplexml_load_string($data))
        {
            // Return FALSE if data contains an error response
            if ($error = @$data->error)
            {
                log_message('error', var_dump($error));
                return FALSE;
            }

            // Return data
            return $data;
        }

        // Return HTTP response code by default
        return $info['http_code'];
    }
}

/**
 * Freshdesk User
 *
 * Create, View, Update, and Delete Users.
 *
 * @link http://freshdesk.com/api/users
 */
class FreshDeskUser extends FreshDeskAPI
{
    
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
     * Request URL: domain_URL/categories.xml
     * Request method: POST
     * 
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-category>
     *       <name>Test</name>                                 <!--- (Mandatory) -->
     *       <description>New testing category</description>   <!--- (Optional) --->
     *     </forum-category>
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-category>
     *       <created-at type="datetime">2012-12-05T16:04:12+05:30</created-at>
     *       <description>New testing category</description>
     *       <id type="integer">2</id>
     *       <name>Test</name>
     *       <position type="integer">2</position>
     *       <updated-at type="datetime">2012-12-05T16:04:12+05:30</updated-at>
     *     </forum-category>
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
            'forum-category' => array(
                'name' => $name,
                'description' => $description
            )
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories.xml", 'POST', $data))
        {
            return FALSE;
        }

        // Return Forum Category object
        return $response;
    }

    /**
     * View all Forum Categories.
     *
     * Request URL: domain_URL/categories.xml
     * Request method: GET
     *
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-categories type="array">
     *       <forum-category>
     *         <created-at type="datetime">2011-03-15T02:23:15-07:00</created-at>
     *         <description>Welcome to the Freshdesk community forums</description>
     *         <id type="integer">2</id>
     *         <name>Freshdesk Forums</name>
     *         <updated-at type="datetime">2011-03-21T02:42:58-07:00</updated-at>
     *       </forum-category>
     *       ...
     *     </forum-categories>
     * 
     * @link   http://freshdesk.com/api/forums/forum-category#view-all-forum-categories
     * 
     * @return array Array of Forum Category Objects
     */
    public function get_all()
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories.xml"))
        {
            return FALSE;
        }
        
        // Default category array
        $categories = array();

        // Extract category data from its 'forum-category' container
        foreach (@$response->{'forum-category'} as $category)
        {
            $categories[] = $category;
        }

        // Return restructured array of categories
        return $categories;
    }

    /**
     * View Forums in a Category.
     *
     * Request URL: domain_URL/categories/[id].xml
     * Request method: GET
     *
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-category>
     *       <created-at type="datetime">2012-12-05T16:04:12+05:30</created-at>
     *       <description>New testing category</description>
     *       <id type="integer">2</id>
     *       <name>Test</name>
     *       <position type="integer">2</position>
     *       <updated-at type="datetime">2012-12-05T16:04:12+05:30</updated-at>
     *       <forums type="array">
     *         <forum>
     *           <description>General helpdesk announcements to the customers.</description>
     *           <description-html>
     *             <p>General helpdesk announcements to the customers.</p>
     *           </description-html>
     *           <forum-category-id type="integer">2</forum-category-id>
     *           <forum-type type="integer">4</forum-type>
     *           <id type="integer">5</id>
     *           <name>Announcements</name>
     *           <position type="integer">5</position>
     *           <posts-count type="integer">0</posts-count>
     *           <topics-count type="integer">0</topics-count>
     *         </forum>
     *         <forum>
     *           <account-id type="integer">2</account-id>
     *           <description>Customers can voice their ideas here.</description>
     *           <description-html>
     *             <p>Customers can voice their ideas here.</p>
     *           </description-html>
     *           <forum-category-id type="integer">2</forum-category-id>
     *           <forum-type type="integer">2</forum-type>
     *           <id type="integer">6</id>
     *           <name>Feature Requests</name>
     *           <position type="integer">6</position>
     *           <posts-count type="integer">11</posts-count>
     *           <topics-count type="integer">7</topics-count>
     *         </forum>
     *         ...
     *       </forums>
     *     </forum-category>
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
        if ( ! $response = $this->_request("categories/{$category_id}.xml"))
        {
            return FALSE;
        }

        // Return Forum Category object
        return $response;
    }

    /**
     * Updated an existing Forum Category.
     *
     * Request URL: domain_URL/categories/[category_id].xml
     * Request method: PUT
     *
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-category>
     *       <name>Test</name>                                 <!--- (Mandatory) -->
     *       <description>New testing category</description>   <!--- (Optional) --->
     *     </forum-category>
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
            'forum-category' => array(
                'name' => $name,
                'description' => $description
            )
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.xml", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }

    /**
     * Delete an existing Forum Category.
     *
     * Request URL: domain_URL/categories/[category_id].xml
     * Request method: DELETE
     *
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-category>
     *       <created-at type="datetime">2012-12-05T16:04:12+05:30</created-at>
     *       <description>New testing category</description>
     *       <id type="integer">2</id>
     *       <name>Test</name>
     *       <position type="integer">2</position>
     *       <updated-at type="datetime">2012-12-05T16:04:12+05:30</updated-at>
     *     </forum-category>
     *       
     * @link   http://freshdesk.com/api/forums/forum-category#delete-a-forum-category
     * 
     * @param  integer $category_id Forum Category ID
     * @return object               Forum Category object
     */
    public function delete($category_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.xml", 'DELETE'))
        {
            return FALSE;
        }

        // Return Forum Category object
        return $response;
    }
}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

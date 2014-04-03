<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * All documentation Copyright © Freshdesk Inc. (http://freshdesk.com/api)
 */

/**
 * Freshdesk Library
 */
class Freshdesk
{
    private $CI;
    private $params;
    private static $accessors = array('Agent', 'User');

    public function __construct($params = array())
    {
        // Get CI instance
        $this->CI =& get_instance($params);

        // Attempt to load config values from file
        if ($config = $this->CI->config->load('freshdesk', TRUE, TRUE))
        {
            $api_key = $this->CI->config->item('api_key', 'freshdesk');
            $username = $this->CI->config->item('username', 'freshdesk');
            $password = $this->CI->config->item('password', 'freshdesk');
            $base_url = $this->CI->config->item('base_url', 'freshdesk');
        }
        // Attempt to load config values from params
        $api_key = @$params['api_key'] ?: @$params['api-key'];
        $username = @$params['username'];
        $password = @$params['password'];
        $base_url = @$params['base_url'] ?: @$params['base-url'];

        // API Key takes precendence
        if ($api_key)
        {
            $username = $api_key;
            $password = 'X';
        }

        // Build list of default params
        $this->params = array(
            'base_url' => $base_url,
            'username' => $username,
            'password' => $password
        );

        // Instantiate API accessors
        foreach ($this->accessors as $accessor)
        {
            $class = "Freshdesk{$accessor}";
            $this->$accessor = new $class($this->params);
        }
    }

    public function __call($name, $args)
    {
        // Dynamically load and return wrapped API accessor
        if (in_array($name, $this->accessors))
        {
            $class = "Freshdesk{$name}Wrapper";
            return new $class($this->params, $args);
        }
    }
}

/**
 * Freshdesk API
 */
class FreshdeskAPI
{
    private $base_url;
    private $username;
    private $password;

    public function __construct($params)
    {
        $this->base_url = $params['base_url'];
        $this->username  = $params['username'];
        $this->password  = $params['password'];
    }

    /**
     * Perform an API request.
     *
     * @param  string $resource Freshdesk API resource
     * @param  string $method   HTTP request method
     * @param  array  $data     HTTP PUT/POST data
     * @return mixed            JSON object or HTTP response code
     */
    protected function _request($resource, $method = 'GET', $data = NULL)
    {
        $method = strtoupper($method);
        $ch = curl_init ("{$this->base_url}/{$resource}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Set POST data if passed to method
        if ($data)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
        if (in_array($info['http_code'], [400, 404, 406, 302]))
        {
            log_message('error', var_dump($data));
            curl_close($ch);
            return FALSE;
        }
        curl_close($ch);

        // Load JSON object if data was returned and properly parsed
        if ($data = @json_decode($data))
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
 * Freshdesk Agent
 */
class FreshdeskAgent extends FreshdeskAPI
{
    public function get_all()
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("agents.json"))
        {
            return FALSE;
        }

        // Default agent array
        $agents = array();

        // Return empty array of users if HTTP 200 received
        if ($response == 200)
        {
            return $agents;
        }

        // Extract agent data from its 'agent' container
        foreach ($response as $agent)
        {
            $agents[] = $agent->agent;
        }

        // Return restructured array of agents
        return $agents;
    }

    public function get($agent_id = NULL)
    {
        // Return all agents if no Agent ID was passed
        if ( ! $agent_id)
        {
            return $this->get_all();
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("agents/{$agent_id}.json"))
        {
            return FALSE;
        }

        // Return Agent object(s)
        return $response->agent;
    }
}

/**
 * Freshdesk User
 *
 * Create, View, Update, and Delete Users.
 *
 * Data:
 *     {'user': {
 *         'id':             (integer)  User ID                  // read-only
 *         'name':           (string)   User Name                // required
 *         'email':          (string)   User Email address       // required
 *         'address':        (string)   User Address
 *         'description':    (string)   User Description
 *         'job_title':      (string)   User Job Title
 *         'twitter_id':     (integer)  User Twitter ID
 *         'fb_profile_id':  (integer)  User Facebook ID
 *         'phone':          (integer)  User Telephone number
 *         'mobile':         (integer)  User Mobile number
 *         'language':       (string)   User Language. 'en' default
 *         'time_zone':      (string)   User Time Zone
 *         'customer_id':    (integer)  User Customer ID
 *         'deleted':        (boolean)  True if deleted
 *         'helpdesk_agent': (boolean)  True if agent            // read-only
 *         'active':         (boolean)  True if active
 *     }}
 *
 * @link http://freshdesk.com/api/#user
 */
class FreshdeskUser extends FreshdeskAPI
{
    # TODO: More meaningful key names once roles are determined
    public static $ROLE = array(
        'ROLE_1' => 1,
        'ROLE_2' => 2,
        'ROLE_3' => 3
    );

    /**
     * Create a new User
     *
     * Request URL: /contacts.xml
     * Request method: POST
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST \
     *         -d '{ "user": { "name":"Super Man", "email":"superman@marvel.com" }}' \
     *         http://domain.freshdesk.com/contacts.json
     *
     * Request:
     *     {"user": {
     *         "name":"Super Man",
     *         "email":"superman@marvel.com"
     *     }}
     *
     * Response:
     *     {"user": {
     *         "active":false,
     *         "address":null,
     *         "created_at":"2014-01-07T19:33:43+05:30",
     *         "customer_id":null,
     *         "deleted":false,
     *         "description":null,
     *         "email":"superman@marvel.com",
     *         "external_id":null,
     *         "fb_profile_id":null,
     *         "id":19,
     *         "job_title":null,
     *         "language":"en",
     *         "mobile":null,
     *         "name":"Super Man",
     *         "phone":null,
     *         "time_zone":"Hawaii",
     *         "twitter_id":null,
     *         "updated_at":"2014-01-07T19:33:43+05:30"
     *     }}
     *
     * @link   http://freshdesk.com/api/#create_user
     *
     * @param  array $data  Array of User data
     * @return object       JSON User object
     */
    public function create($data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'user' container
        if (array_shift(array_keys($data)) != 'user')
        {
            $data = array('user' => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts.json", 'POST', $data))
        {
            return FALSE;
        }

        // Return User object
        return $response;
    }

    /**
     * Retrieve all Users
     *
     * Request URL: /contacts.xml
     * Request method: GET
     *
     * Filter:
     *     State: /contacts?state=[state]
     *     Note: state may be 'verified', 'unverified', 'all', or 'deleted'
     *     Example: /contacts.json?state=all
     *
     *     Query: /contacts.json?query=[condition]
     *     Note: condition may be 'email', 'mobile', or 'phone'
     *     Example: /contacts.json?query=email is user@yourcompany.com
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X GET \
     *         http://domain.freshdesk.com/contacts.json
     *
     * Response:
     *     [
     *         {"user": {
     *             "active":false,
     *             "address":"",
     *             "created_at":"2013-12-20T15:04:16+05:30",
     *             "customer_id":null,
     *             "deleted":false,
     *             "description":"",
     *             "email":"superman@marvel.com",
     *             "external_id":null,
     *             "fb_profile_id":null,
     *             "helpdesk_agent":false,
     *             "id":19,
     *             "job_title":"Super Hero",
     *             "language":"en",
     *             "mobile":"",
     *             "name":"Super Man",
     *             "phone":"",
     *             "time_zone":"Hawaii",
     *             "twitter_id":"",
     *             "updated_at":"2013-12-20T15:04:16+05:30"
     *          }},
     *          ...
     *      ]
     *
     * @link   http://freshdesk.com/api/#view_all_user
     *
     * @param  string $state Filter state
     * @param  string $query Filter query
     * @return array         Array of JSON User objects
     */
    public function get_all($state = '', $query = '')
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts.json?state={$state}&query={$query}"))
        {
            return FALSE;
        }

        // Default user array
        $users = array();

        // Return empty array of users if HTTP 200 received
        if ($response == 200)
        {
            return $users;
        }

        // Extract user data from its 'user' container
        foreach ($response as $user)
        {
            $users[] = $user->user;
        }

        // Return restructured array of users
        return $users;
    }

    /**
     * Retrieve a User
     *
     * Request URL: /contacts/[user_id].xml
     * Request method: GET
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT \
     *         -d '{ "user": { "name":"SuperMan", "job_title":"Avenger" }}' \
     *         http://domain.freshdesk.com/contacts/19.json
     *
     * Response:
     *     {"user":{
     *         "active":false,
     *         "address":null,
     *         "created_at":"2014-01-07T19:33:43+05:30",
     *         "customer_id":null,
     *         "deleted":false,
     *         "description":null,
     *         "email":"superman@marvel.com",
     *         "external_id":null,
     *         "fb_profile_id":null,
     *         "id":19,
     *         "job_title":null,
     *         "language":"en",
     *         "mobile":null,
     *         "name":"Super Man",
     *         "phone":null,
     *         "time_zone":"Hawaii",
     *         "twitter_id":null,
     *         "updated_at":"2014-01-07T19:33:43+05:30"
     *      }}
     *
     *
     * @link   http://freshdesk.com/api/#view_user
     *
     * @param  mixed  $user_id User ID or Filter state
     * @param  string $query   Filter query
     * @return object          JSON User object
     */
    public function get($user_id = NULL, $query = NULL)
    {
        // Return all users if no User ID or if get_all() args were passed
        if ( ! ($state = $user_id) or is_string($state) or is_string($query))
        {
            return $this->get_all($state, $query);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.json"))
        {
            return FALSE;
        }

        // Return User object(s)
        return $response->user;
    }

    /**
     * Update a User
     *
     * Request URL: /contacts/[user_id].xml
     * Request method: PUT
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT \
     *         -d '{ "user": { "name":"SuperMan", "job_title":"Avenger" }}' \
     *         http://domain.freshdesk.com/contacts/19.json
     *
     * Request:
     *     {"user": {
     *         "name":"SuperMan",
     *         "job_title":"Avenger"
     *     }}
     *
     * Response:
     *     HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#update_user
     *
     * @param  integer $user_id  User ID
     * @param  array   $data     User data
     * @return integer           HTTP response code
     */
    public function update($user_id, $data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'user' container
        if (array_shift(array_keys($data)) != 'user')
        {
            $data = array('user' => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.json", 'PUT', $data))
        {
            return FALSE;
        }

        // Return User object if HTTP 200
        return $response == 200 ? $this->get($user_id) : FALSE;
    }

    /**
     * Delete a User
     *
     * Request URL: /contacts/[user_id].xml
     * Request method: DELETE
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE \
     *         http://domain.freshdesk.com/contacts/1.json
     *
     * Response:
     *     HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#delete_user
     *
     * @param  integer $user_id  User ID
     * @return integer           HTTP response code
     */
    public function delete($user_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.json", 'DELETE'))
        {
            return FALSE;
        }

        // Return TRUE if HTTP 200
        return $response == 200 ? TRUE : FALSE;
    }
}

/**
 * Freshdesk Forum Category
 *
 * Create, View, Update, and Delete Forum Categories.
 *
 * Data:
 *     {'forum_category': {
 *         'id':            (integer)   Forum Category ID        // read-only
 *         'name':          (string)    Forum Category Name      // required
 *         'description':   (string)    Forum Category Description
 *         'postition':     (integer)   Forum Category Position  // read-only
 *     }}
 *
 * @link http://freshdesk.com/api/#forum-category
 */
class FreshdeskForumCategory extends FreshdeskAPI
{
    public $Forum;

    public function __construct($base_url, $username, $password)
    {
        FreshdeskAPI::__construct($base_url, $username, $password);
        $this->Forum = new FreshdeskForum($this->base_url, $this->username, $this->password);
    }

    /**
     * Create a new Forum Category.
     *
     * Request URL:  /categories.json
     * Request method: POST
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST 
     * 		-d '{ "forum_category": { "name":"How to", "description":"Getting Started" }}' http://domain.freshdesk.com/categories.json     
     *
     * Request:
	 *	  {"forum_category": { 
     *         "name":"How to",
     *         "description":"Queries on How to ?"
     *      }}   
     * Response:
	 * 	   {"forum_category":{
     *         "created_at":"2014-01-08T06:38:11+05:30",
     *         "description":"Getting Started",
     *         "id":3,
     *         "name":"How to",
     *         "position":3,
     *         "updated_at":"2014-01-08T06:38:11+05:30"
     *      }
     * 
     *
     * @link http://freshdesk.com/api/#create_forum_category
     *
     * @param  JSON object $data Forum Category Data '{ "forum_category": { "name":"How to", "description":"Getting Started" }}'
     * @return JSON object              Forum Category JSON object
     */
    public function create($data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'forum-category' container
        if (array_shift(array_keys($data)) != 'forum-category')
        {
            $data = array('forum-category' => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories.json", 'POST', $data))
        {
            return FALSE;
        }

        // Return forum-category object
        return $response;
    }

    /**
     * View all Forum Categories.
     *
     * Request URL: categories.json
     * Request method: GET
     *
     * CURL:
     *		curl -u user@yourcompany.com:test -H "Content-Type: application/json" 
     *		-X GET http://domain.freshdesk.com/categories.json
     * Response:
	 * 		{"forum_category":{
     *            "created_at":"2014-01-08T06:38:11+05:30",
     *            "description":"Tell us your problems",
     *            "id":3,
     *            "name":"Report Problems",
     *            "position":3,
     *            "updated_at":"2014-01-08T06:38:11+05:30"
     *        }
     *
     * @link   http://freshdesk.com/api/#view_all_forum_category
     *
     * @return Object    JSON Object of Forum Category Objects
     */
    public function get_all()
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories.json"))
        {
            return FALSE;
        }

        // Return restructured array of categories
        return $response;
    }

    /**
     * View Forums in a Category.
     *
     * Request URL: /categories/[id].json
     * Request method: GET
     *
     * Response:
	 *		{"forum_category":{
     *        	"created_at":"2014-01-08T06:38:11+05:30",
	 *		 	"description":"Recently Changed",
	 *		 	"id":2,
	 *		 	"name":"Latest Updates",
	 *		 	"position":4,
	 *		 	"updated_at":"2014-01-08T06:38:11+05:30"
	 *		 }
     *   
     *
     * @link   http://freshdesk.com/api/#view_forum_category
     *
     * @param  integer $category_id Forum Category ID
     * @return Object  JSON Forum Category Object
     */
    public function get($category_id = NULL)
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

        // Return Forum Category object(s)
        return $response;
    }

    /**
     * Update an existing Forum Category.
     *
     * Request URL: /categories/[id].json
     * Request method: PUT
     *
     * Request:
     *     {"forum_category":{
     *         "name":"Report Problems",
     *         "description":"Tell us your problems"
     *      }}
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#update_forum_category
     *
     * @param  integer $category_id Forum Category ID
     * @param  object  $data        Forum Category JSON Object 
     * @return bool	   TRUE			Return TRUE if HTTP 200 OK
     */
    public function update($category_id, $data)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.json", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }

    /**
     * Delete an existing Forum Category.
     *
     * Request URL: categories/[id].json 
     * Request method: DELETE
     *
     * CURL:
     *		curl -u user@yourcompany.com:test -H "Content-Type: application/json" 
     *		-X DELETE http://domain.freshdesk.com/categories/3.json
     * Response:
	 *		{"forum_category":{
     *        	"created_at":"2014-01-08T06:38:11+05:30",
	 *		 	"description":"How to Queries",
	 *		 	"id":3,
	 *		 	"name":"How and What?",
	 *		 	"position":null,
	 *		 	"updated_at":"2014-01-08T07:13:56+05:30"
	 *		 }}
     *
     * @link   http://freshdesk.com/api/#delete_forum_category
     *
     * @param  integer	$category_id 	Forum Category ID
     * @return bool		TRUE			Returns TRUE if HTTP Response: 200 OK
     */
    public function delete($category_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.json", 'DELETE'))
        {
            return FALSE;
        }

        // Return TRUE if HTTP 200
        return $response == 200 ? TRUE : FALSE;
    }
}

/**
 * Freshdesk Forum
 *
 * Create, View, Update, and Delete Forums.
 *
 * @link http://freshdesk.com/api/forums/forum
 */
class FreshdeskForum extends FreshdeskAPI
{
    public $ForumCategory;

    public static $TYPE = array(
        'HOWTO' => 1,
        'IDEA' => 2,
        'PROBLEM' => 3,
        'ANNOUNCEMENT' => 4
    );
    public static $VISIBILITY = array(
        'ALL' => 1,
        'USERS' => 2,
        'AGENTS' => 3
    );

    public function __construct($base_url, $username, $password)
    {
        FreshdeskAPI::__construct($base_url, $username, $password);
        $this->ForumCategory = new FreshdeskForumCategory($this->base_url, $this->username, $this->password);
    }

    /**
     * Create a new Forum.
     *
     * Request URL: categories/[id]/forums.json 
     * Request method: POST
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST 
     *		-d '{ "forum": { "description": "Ticket related functions", "forum_type":2, "forum_visibility":1, "name":"Ticket Operations" }}' 
     *		http://domain.freshdesk.com/categories/1/forums.json
     * Request:
	 *		{"forum": {
     *        	 "description":"Ticket related functions",
	 *		 	 "forum_type":2,
	 *		 	 "forum_visibility":1,
	 *		 	 "name":"Ticket Operations"
	 *		 }}  
     * Response:
	 * 		{"forum":{
     *       	 "description":"Ticket related functions",
	 *		 	 "description_html":"\u003Cp\u003ETicket related functions\u003C/p\u003E",
	 *		 	 "forum_category_id":1,
	 *		 	 "forum_type":2,
	 *		 	 "forum_visibility":1,
	 *		 	 "id":2,
	 *		 	 "name":"Ticket Operations",
	 *		 	 "position":5,
	 *		 	 "posts_count":0,
	 *		 	 "topics_count":0
     *       }} 
     *
     * @link http://freshdesk.com/api/#create_forum
     *
     * @todo   Determine avilable type/visibility options.
     * @todo   Determine commonly default type/visibility option.
     *
     * @param  string $name        Forum Name
	 * @param  object $data		   Forum JSON object
     * @return object              Forum JSON object
     */
    public function create($category_id, $data)
    {
        // Determine type and visibility
        // $type = is_string($type) ? @self::$TYPE[$type] : $type;
        // $visibility = is_string($visibility) ? @self::$VISIBILITY[$visibility] : $visibility;
        
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }
        // Encapsulate data in 'forum' container
        if (array_shift(array_keys($data)) != 'forum')
        {
            $data = array('forum' => $data);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums.json", 'POST', $data))
        {
            return FALSE;
        }
        // Return Forum object
        return $response;
    }

    /**
     * View Forums in a Category.
     *
     * Request URL: domain_URL/categories/[category_id].xml
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
     * @return mixed                Array or single Forum Category Object
     */
    public function get_all($category_id)
    {
        return $this->ForumCategory->get($category_id);
    }

    /**
     * View Forum
     *
     * Request URL: categories/[id]/forums/[id].json 
     * Request method: GET
     *
     * Response:
	 *		{"forum":{
     *         "description":"Ticket related functions",
     *         "description_html":"\u003Cp\u003ETicket related functions\u003C/p\u003E",
     *         "forum_category_id":1,
     *         "forum_type":2,
     *         "forum_visibility":1,
     *         "id":2,
     *         "name":"Ticket Operations",
     *         "position":5,
     *         "posts_count":0,
     *         "topics_count":0,
     *         "topics":[]
     *      }
	 *
     * @link   http://freshdesk.com/api/#view_forum
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @return mixed                Array or single Forum Object
     */
    public function get($category_id, $forum_id = NULL)
    {
        // Return all forums if no Forum ID was passed
        if ( ! $forum_id)
        {
            return $this->get_all($category_id);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{forum_id}.json", 'POST'))
        {
            return FALSE;
        }

        // Return Forum object(s)
        return $response;
    }

    /**
     * Update an existing Forum.
     *
     * Request URL: categories/[id]/forums/[id].json
     * Request method: PUT
     *
     * Request:
	 *		{"forum": {
     *       	"forum_type":2,
     *       	"description":"Tickets and Ticket fields related queries",
     *       	"forum_visibility":1
     *    	 }}
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#update_forum
     *
     * @todo   Determine avilable type/visibility options.
     * @todo   Determine commonly default type/visibility option.
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
	 * @param  object  $data		Forum JSON Object
     * @return integer              HTTP response code
     */
    public function update($category_id, $forum_id, $data)
    {
        // Determine type and visibility
        // $type = $type and is_string($type) ? @self::$TYPE[$type] : $type;
        // $visibility = $visibility and is_string($visibility) ? @self::$VISIBILITY[$visibility] : $visibility;

       // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}.json", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }

    /**
     * Delete an existing Forum.
     *
     * Request URL: categories/[id]/forums/[id].json 
     * Request method: DELETE
     *
     * CURL: 
     *		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE 
     *		http://domain.freshdesk.com/categories/1/forums/2.json
     * Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#delete_forum
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @return integer              HTTP response code
     */
    public function delete($category_id, $forum_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}.json", 'DELETE'))
        {
            return FALSE;
        }

        // Return TRUE if HTTP 200
        return $response == 200 ? TRUE : FALSE;	  
    }
}

/**
 * Freshdesk Forum Topic
 *
 * Create, View, Update, and Delete Forum Topics
 *
 * Data:
 *     {'topic': {
 *			'id':			(number) 	Unique id of the topic 								// Read-Only
 *			'title': 		(script) 	Title of the forum 									// Mandatory
 *			'forum_id': 	(number) 	ID of the Forum in which this topic is present
 *			'hits':			(number) 	Number of views of that forum 						// Read-Only
 *			'last_post_id': (number) 	ID of the latest comment on the forum 				// Read-Only
 *			'locked': 		(boolean) 	Set as true if the forum is locked
 *			'posts_count': 	(number) 	Number of posts in that topic
 *			'sticky': 		(number) 	Describes whether a topic can be deleted or not
 *			'user_id': 		(number) 	ID of the user 										// Read-Only
 *			'user_votes': 	(number) 	Number of votes in the topic 						// Read-Only
 *			'replied_at': 	(datetime) 	Timestamp of the latest comment made in the topic 	// Read-Only
 *			'replied_by': 	(datetime) 	Id of the user who made the latest comment in that topic 
 *     }}
 *
 * @link http://freshdesk.com/api/#topic
 */
class FreshdeskTopic extends FreshdeskAPI
{
    public static $STAMP = array(
        'PLANNED' => 1,
        'IMPLEMENTED' => 2,
        'TAKEN' => 3
    );
    /**
     * Create a new Forum Topic
     *
     * Request URL: /categories/[id]/forums/[id]/topics.json
     * Request method: POST
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST 
     *		-d '{ "topic": { "sticky":0, "locked":0, "title":"how to create a custom field", "body_html":"Can someone give me the steps ..." }}'
     *		 http://domain.freshdesk.com/categories/1/forums/1/topics.json
     * Request:
	 *		{"topic": {
     *       	"sticky":0, 
	 *			"locked":0,
	 *			"title":"how to create a custom field",
	 *			"body_html":"Can someone give me the steps..."
	 *		}}    
     * Response:
	 *		{"topic":{
     *       	 "account_id":1,
	 *		 	 "created_at":"2014-01-08T08:54:01+05:30",
	 *		 	 "delta":true,
	 *		 	 "forum_id":5,
	 *		 	 "hits":0,
	 *		 	 "id":3,
	 *		 	 "import_id":null,
	 *           "last_post_id":null,
	 *           "locked":false,
	 *           "posts_count":0,
	 *           "replied_at":"2014-01-08T08:54:01+05:30",
	 *           "replied_by":null,
	 *           "stamp_type":null,
	 *           "sticky":0,
	 *           "title":"how to create a custom field",
	 *           "updated_at":"2014-01-08T08:54:01+05:30",
	 *           "user_id":1,
	 *           "user_votes":0
	 *       }}
     *
     * @link http://freshdesk.com/api/#create_topic
     *
     * @todo   Determine avilable type/visibility options.
     * @todo   Determine commonly default type/visibility option.
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
	 * @param  object   $data		 Forum Topic JSON object
     * @return object                Forum Topic JSON object
     */    
    public function create($category_id = '', $forum_id = '', $data = '')
    {
    	// Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }
        // Encapsulate data in 'forum' container
        if (array_shift(array_keys($data)) != 'topic')
        {
            $data = array('topic' => $data);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}/topics.json", 'POST', $data))
        {
            return FALSE;
        }
        // Return Forum object
        return $response;
    }
    
    /**
     * Update an existing forum Topic
     *
     * Request URL: domain_URL/categories/[id]/forums/[id]/topics/[id].json 
     * Request method: PUT
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT 
     *		-d '{ "topic": { "sticky":0, "locked":0, "title":"How to create a new ticket field", "body_html":"Steps: Go to Admin tab ..." }}' 
     *		http://domain.freshdesk.com/categories/1/forums/1/topics/3.json
     * Request:
	 *		{"topic":{
     *       	"sticky":0,
	 * 			"locked":0,
	 *			"title":"How to create a new ticket field",
	 *			"body_html": "Steps: Go to Admin tab ..."
     *      }}
     * Response:
	 *		HTTP Status: 200 OK
	 *
     * @link http://freshdesk.com/api/#update_topic
     *
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id	 Forum Topic ID
	 * @param  object   $data		 Forum Topic JSON object
     * @return integer               HTTP Status: 200 OK
     */        
    public function update($category_id = '', $forum_id = '', $topic_id ='',  $data = '')
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }
        // Encapsulate data in 'topic' container
        if (array_shift(array_keys($data)) != 'topic')
        {
            $data = array('topic' => $data);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json", 'POST', $data))
        {
            return FALSE;
        }
        // Return Forum object
        return $response;
    }
    /**
     * View all conversations in a forum Topic
     *
     * Request URL: domain_URL/categories/[id]/forums/[id]/topics/[id].json  
     * Request method: GET
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" 
     *		-X GET http://domain.freshdesk.com/categories/1/forums/1/topics/3.json
     * Response:
	 *		{
     *      "topic":{
     *         "account_id":1,
     *         "created_at":"2014-01-08T08:54:01+05:30",
     *         "delta":true,
     *         "forum_id":5,
     *         "hits":0,
     *         "id":3,
     *         "import_id":null,
     *         "last_post_id":9,
     *         "locked":false,
     *         "posts_count":0,
     *         "replied_at":"2014-01-08T08:54:01+05:30",
     *         "replied_by":1,
     *         "stamp_type":null,
     *         "sticky":0,
     *         "title":"How to create a ticket field",
     *         "updated_at":"2014-01-08T08:54:01+05:30",
     *         "user_id":1,
     *         "user_votes":0,
     *         "posts":[
     *            {
     *               "account_id":1,
     *               "answer":false,
     *               "body":"Steps: Go to Admin tab ...",
     *               "body_html":"Steps: Go to Admin tab ...",
     *               "created_at":"2014-01-08T08:54:01+05:30",
     *               "forum_id":5,
     *               "id":9,
     *               "import_id":null,
     *               "topic_id":3,
     *               "updated_at":"2014-01-08T08:54:01+05:30",
     *               "user_id":1
     *            }
     *         ]
     *      }
     *   
	 *
     * @link http://freshdesk.com/api/#view_topic
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id	 Forum Topic ID
	 * @param  object   $data		 Forum Topic JSON object
     * @return object                Forum Topic JSON Object
     */        
    public function get($category_id = '', $forum_id = '', $topic_id = '')
    {
	    if (!$response = $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json ", "GET"))
	    {
		    return FALSE;
	    }
	    return $response;
    }
    /**
     * Delete Topic
     *
     * Request URL: domain_URL/categories/[id]/forums/[id]/topics/[id].json  
     * Request method: DELETE
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" 
     *		-X DELETE http://domain.freshdesk.com/categories/1/forums/1/topics/1.json
     * Response:
	 *		TRUE if HTTP Status: 200 OK
	 *
     * @link http://freshdesk.com/api/#delete_topic
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id	 Forum Topic ID
     * @return bool		TRUE         Return TRUE if HTTP Status: 200 OK
     */        
    public function delete($category_id = '', $forum_id = '', $topic_id = '')
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json", 'DELETE'))
        {
            return FALSE;
        }

        // Return TRUE if HTTP 200
        return $response == 200 ? TRUE : FALSE;	    
    }
}

/**
 * Freshdesk Forum Post
 *
 * Create, View, Update, and Delete Forum Posts
 *
 * Data:
 *     {'post': {
 *			'id' 		(number) 	Unique ID of the post or comment		// Read-Only
 *			'body' 		(string) 	Content of the post in plaintext
 *			'body_html' (string) 	Content of the post in HTML. 			// Mandatory
 *									(You can pass either body or body_html)
 *			'forum_id' 	(number) 	ID of the forum where the comment was posted
 *			'topic_id' 	(number) 	ID of the topic where the comment was posted
 *			'user_id' 	(number) 	ID of the user who posted the comment 
 *     }}
 *
 * @link http://freshdesk.com/api/#post
 */
class FreshdeskPost extends FreshdeskAPI
{
    /**
     * Create a new Forum Post
     *
     * Request URL: /posts.json
     * Request method: POST
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST 
     *		-d '{ "post": { "body_html":"What type of ticket field you are creating" }}' 
     * 		http://domain.freshdesk.com/posts.json?forum_id=1&category_id=1&topic_id=2
     * Request:
     *		{"post": { 
     *       	"body_html":"What type of ticket field you are creating"
     *       }
     * Response:
     *		{"post": {
     *		    "answer": false,
     *		    "body": "What type of ticket field you are creating",
     *		    "body_html": "What type of ticket field you are creating",
     *		    "created_at": "2014-02-07T12:32:34+05:30",
     *		    "forum_id": 1,
     *		    "id": 12,
     *		    "topic_id": 2,
     *		    "updated_at": "2014-02-07T12:32:34+05:30",
     *			"user_id": 1
     *		}}
     *
     * @link http://freshdesk.com/api/#create_post
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
	 * @param  object   $data		 Forum POST JSON object
     * @return object                Forum POST JSON object
     */
	public function create($category_id = '', $forum_id = '', $topic_id = '', $data)
	{
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'post' container
        if (array_shift(array_keys($data)) != 'post')
        {
            $data = array('post' => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("posts.json?category_id={$category_id}&forum_id={$forum_id}&topic_id={$topic_id}", 'POST', $data))
        {
            return FALSE;
        }

        // Return User object
        return $response;
	}
    /**
     * Update an existing post
     *
     * Request URL: /posts/[id].json
     * Request method: PUT
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT 
     *		-d '{ "post": { "body_html": "Ticket field have different types ..." }}' 
     *		http:/2domain.freshdesk.com/posts/1.json?forum_id=1&category_id=1&topic_id=2
     * Request:
     *		{"post": { 
     *       	"body_html":"What type of ticket field you are creating"
     *       }
     * Response:
     *		TRUE if HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#update_post
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
	 * @param  object   $data		 Forum POST JSON object
     * @return object                Forum POST JSON object
     */
	public function update($category_id = '', $forum_id = '', $topic_id = '', $data)
	{
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'post' container
        if (array_shift(array_keys($data)) != 'post')
        {
            $data = array('post' => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("posts.json?category_id={$category_id}&forum_id={$forum_id}&topic_id{$topic_id}", 'POST', $data))
        {
            return FALSE;
        }
   
        // Return posts object
        return $response;
	}
    /**
     * Delete an existing post
     *
     * Request URL: /posts/[id].json
     * Request method: DELETE
     *
     * CURL:
     * 		curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE 
     *		http://domain.freshdesk.com/posts/1.json?forum_id=1&category_id=1&topic_id=1
     * Response:
     *		TRUE if HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#delete_post
     *
     * @param	integer	$category_id	Forum Category ID
     * @param	integer	$forum_id		Forum ID
     * @param	integer	$topic_id		Forum Topic ID
     * @return	bool					TRUE if HTTP Status: 200 OK 
     */	
	public function delete($category_id = '', $forum_id = '', $topic_id='', $post_id = '')
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("posts/{$post_id}.json?category_id={$category_id}&forum_id={$forum_id}&topic_id={$topic_id}", 'DELETE'))
        {
            return FALSE;
        }
        // Return TRUE if HTTP 200
        return $response == 200 ? TRUE : FALSE;
    }


}
/**
 * Freshdesk Monitor
 *
 * Monitor, Un-Monitor, Check Monitoring Status, and get User Monitored Topics
 *
 * @link http://freshdesk.com/api/#monitor
 */
class FreshDeskMonitor extends FreshdeskAPI 
{
    /**
     * Get a user's Monitored Topics 
     *
     * Request URL: /support/discussions/user_monitored?user_id=[id]
     * Request method: GET
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X GET 
     *	   "http://domain.freshdesk.com/support/discussions/user_monitored?user_id=1218912"
     *
     * Response:
	 *	[{
     *     "topic": {
     *       "account_id":16699,
     *       "created_at":"2013-10-16T17:58:59+05:30",
     *       "delta":true,
     *       "forum_id":68251,
     *       "hits":4,
     *       "id":35774,
     *       "import_id":12345,
     *       "last_post_id":84456,
     *       "locked":false,
     *       "posts_count":3,
     *       "published":true,
     *       "replied_at":"2013-10-16T18:03:09+05:30",
     *       "replied_by":1218912,
     *       "stamp_type":9,
     *       "sticky":0,
     *       "title":"Ticket creation",
     *       "updated_at":"2013-10-16T17:58:59+05:30",
     *       "user_id":1218912,
     *       "user_votes":0
     *       }  
     *
     * @link   http://freshdesk.com/api/#user_monitored_topic
     *
     * @param  string $user_id User's Freshdesk ID
     * @return object       JSON Topic object
     */
	public function get_monitored($user_id = '') 
	{
		if (! $response = $this->_request("support/discussions/user_monitored?user_id={$user_id}", "GET"))
		{
			return FALSE;
		}
		return $response;
	}
    /**
     * Monitoring Status 
     *
     * Request URL: /support/discussions/topics/[id]/check_monitor.json?user_id=[id] 
     * Request method: GET
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X GET 
     *	   "http://domain.freshdesk.com/support/discussions/user_monitored?user_id=1218912"
     *
     * Response:
	 *	{"monitorship": { 
     *       "active":false,
     *       "id":18112,
     *       "monitorable_id":15483,
     *       "monitorable_type":"Topic",
     *       "user_id":1791107
     *     }}
     *   
     *
     * @link   http://freshdesk.com/api/#view_monitor_status
     *
     * @param  string $topic_id Freshdesk Topic ID
     * @param  string $user_id User's Freshdesk ID
     * @return object       JSON Monitor object
     */
	public function check_monitor($topic_id = '', $user_id = '') 
	{
		
		if ( ! $response = $this->_request("support/discussions/topics/{$topic_id}/check_monitor.json?user_id={$user_id}", "GET"))
		{
			return FALSE;
		}
		return $response;
	}
    /**
     * Monitor Topic 
     *
     * Request URL: /categories/[id]/forums/[id]/topics/[id]/monitorship.json 
     * Request method: POST
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST 
     *		"http://domain.freshdesk.com/categories/1/forums/2/topics/3/monitorship.json"
     *
     * Response:
	 *	HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#monitor_topic
     *
     * @param  string $category_id Freshdesk Category ID
     * @param  string $forum_id Freshdesk Forum ID
     * @param  string $topic_id Freshdesk Topic ID
     * @return TRUE if HTTP Status: 200 OK
     */
	public function monitor($category_id = '', $forum_id = '', $topic_id = '') 
	{
		// Return FALSE if we've failed to get a request response
		if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}/monitorship.json", "POST"))
		{
			return FALSE;
		}
		// Return TRUE if HTTP 200
		return $response == 200 ? TRUE : FALSE;
	}
    /**
     * Un-Monitor Topic 
     *
     * Request URL: /categories/[id]/forums/[id]/topics/[id]/monitorship.json 
     * Request method: DELETE
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE 
     *		"http://domain.freshdesk.com/discussions/topic/1/subscriptions/unfollow.json"
     *
     * Response:
	 *	HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#unmonitor_topic
     *
     * @param  string $category_id Freshdesk Category ID
     * @param  string $forum_id Freshdesk Forum ID
     * @param  string $topic_id Freshdesk Topic ID
     * @return TRUE if HTTP Status: 200 OK
     */
	public function unmonitor($category_id = '', $forum_id = '', $topic_id = '') 
	{
		// Return FALSE if we've failed to get a request response
		if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}/monitorship.json", "DELETE"))
		{
			return FALSE;
		}
		
		// Return TRUE if HTTP 200
		return $response == 200 ? TRUE : FALSE;
	}
}


/**
 * Wrapped Freshdesk Class
 *
 * Allows `id` and `args` to be passed at instantiation.
 *
 * Returns an object that can be used similar to a Model.
 */
class FreshdeskWrapper extends FreshdeskAPI
{
    protected $id;
    protected $api;
    protected $args;
    protected $data;

    public function __construct($params, $args)
    {
        FreshdeskAPI::__construct($params);

        $api = substr(get_class($this), 0, -strlen('Wrapper'));
        $this->api = new $api($params);

        // Return if no args were passed
        if ( ! $arg0 = @$args[0])
        {
            return;
        }

        // Set args if only args passed
        if (is_array($arg0))
        {
            $this->args = $arg0;
        }
        // Set args and data if id was passed
        else if ($this->id = intval(array_shift($args)))
        {
            $this->args = @$args[0];
            $this->data = $this->get();
        }
    }

    public function __get($name)
    {
        if ($value = (@$this->args[$name] ?: @$this->data->$name))
        {
            return $value;
        }
    }

    public function __set($name, $value)
    {
        $this->args[$name] = $value;
    }

    public function create($args = NULL)
    {
        return $this->api->create($args ?: $this->args);
    }

    public function get()
    {
        return $this->api->get($this->id);
    }

    public function update($args = NULL)
    {
        return $this->api->update($this->id, $args ?: $this->args);
    }

    public function delete()
    {
        return $this->api->delete($this->id);
    }
}



/**
 * Wrapped Freshdesk Classes
 */
class FreshdeskAgentWrapper extends FreshdeskWrapper {}
class FreshdeskUserWrapper extends FreshdeskWrapper {}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

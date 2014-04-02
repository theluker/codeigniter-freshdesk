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

class FreshdeskAPIBase extends FreshdeskAPI
{
    protected $node;
    protected $resource;

    public function create($data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'node' container
        if (array_shift(array_keys($data)) != $this->node)
        {
            $data = array($this->node => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("{$this->resource}.json", 'POST', $data))
        {
            return FALSE;
        }

        // Return object
        return $response->{$this->resource};
    }

    public function get($id = NULL)
    {
        // Return all objects if no Agent ID was passed
        if ( ! $id)
        {
            return $this->get_all();
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("{$this->resource}/{$id}.json"))
        {
            return FALSE;
        }

        // Return object(s)
        return $response->{$this->node};
    }

    public function get_all($query = '')
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("{$this->resource}.json{$query}"))
        {
            return FALSE;
        }

        // Default object array
        $objects = array();

        // Return empty array of objects if HTTP 200 received
        if ($response == 200)
        {
            return $objects;
        }

        // Extract objects data from its 'node' container
        foreach ($response as $object)
        {
            $objects[] = $object->{$this->node};
        }

        // Return restructured array of objects
        return $objects;
    }

    public function update($id, $data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // Encapsulate data in 'node' container
        if (array_shift(array_keys($data)) != $this->node)
        {
            $data = array($this->node => $data);
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("{$this->resource}/{$id}.json", 'PUT', $data))
        {
            return FALSE;
        }

        // Return object if HTTP 200
        return $response == 200 ? $this->get($id) : FALSE;
    }

    public function delete($id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("{$this->resource}/{$id}.json", 'DELETE'))
        {
            return FALSE;
        }

        // Return TRUE if HTTP 200
        return $response == 200 ? TRUE : FALSE;
    }
}

/**
 * Freshdesk Agent
 */
class FreshdeskAgent extends FreshdeskBaseAPI
{
    protected $node = 'agent';
    protected $resource = 'agents';
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
class FreshdeskUser extends FreshdeskBaseAPI
{
    protected $node = 'user';
    protected $resource = 'contacts';

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
        return FreshdeskBaseAPI::create($data);
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
        return FreshdeskBaseAPI::get_all("?state={$state}&query={$query}");
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
        return FreshdeskBaseAPI::get($user_id);
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
        return FreshdeskBaseAPI::update($user_id, $data);
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
        return FreshdeskBaseAPI::delete($user_id);
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
class FreshdeskForumCategory extends FreshdeskAPIBase
{
    protected $node = 'forum_category';
    protected $resource = 'categories';

    public function create($data)
    {
        return FreshdeskAPIBase::create($data);
    }

    public function get_all()
    {
        return FreshdeskAPIBase::get_all();
    }

    public function get($category_id)
    {
        return FreshdeskAPIBase::get($category_id);
    }

    public function update($category_id, $data)
    {
        return FreshdeskAPIBase::update($category_id, $data);
    }

    public function delete($category_id) {
        return FreshdeskAPIBase::delete($category_id);
    }
}

class FreshdeskForum extends FreshdeskAPI
{
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
}

class FreshdeskTopic extends FreshdeskAPI
{
    public static $STAMP = array(
        'PLANNED' => 1,
        'IMPLEMENTED' => 2,
        'TAKEN' => 3
    );
}

class FreshdeskPost extends FreshdeskAPI
{
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

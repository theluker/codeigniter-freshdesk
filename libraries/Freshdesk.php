<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * All documentation Copyright © Freshdesk Inc. (http://freshdesk.com/api)
 */

/**
 * Freshdesk Library
 * #TODO: Library infos
 */
class Freshdesk
{
    private $CI;
    private $params;
    private static $apis = array(
        'Agent', 'User',
        'ForumCategory', 'Forum', 'Topic', 'Post'
    );

    public function __construct($params = array())
    {
        // Get CI instance
        $this->CI =& get_instance($params);

        // Attempt to load config values from file
        if ($config = $this->CI->config->load('freshdesk', TRUE, TRUE))
        {
            $api_key  = $this->CI->config->item('api_key',  'freshdesk');
            $username = $this->CI->config->item('username', 'freshdesk');
            $password = $this->CI->config->item('password', 'freshdesk');
            $base_url = $this->CI->config->item('base_url', 'freshdesk');
        }
        // Attempt to load config values from params
        $api_key  = @$params['api_key']  ?: @$params['api-key'];
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

        // Load APIs
        foreach (self::$apis as $api)
        {
            $class = "Freshdesk{$api}";
            $this->$api = new $class($this->params);
        }
    }

    public function __call($name, $args)
    {
        // Dynamically load and return wrapped API
        if (in_array($name, self::$apis))
        {
            $class = "Freshdesk{$name}Wrapper";
            return new $class($this->params, $args);
        }
    }
}

/**
 * Freshdesk API Transport
 *
 * Performs HTTP calls to the Freshdesk web service.
 */
class FreshdeskTransport
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
        // Build request
        $method = strtoupper($method);
        $ch = curl_init ("{$this->base_url}/{$resource}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Set POST data if passed to method
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute request
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        log_message('debug', var_export(array($info, htmlspecialchars($data)), TRUE));

        // CURL error handling
        if (curl_errno($ch) and $error = curl_error($ch))
        {
            log_message('error', var_export($error, TRUE));
            curl_close($ch);
            return FALSE;
        }
        if (in_array($info['http_code'], array(400, 404, 406, 302)))
        {
            log_message('error', var_export($data, TRUE));
            curl_close($ch);
            return FALSE;
        }

        // Close rqeuest
        curl_close($ch);

        // Load JSON object if data was returned and properly parsed
        if ($data = @json_decode($data))
        {
            // Return FALSE if data contains an error response
            if ($error = @$data->error)
            {
                log_message('error', var_export($error, TRUE));
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
 * Freshdesk Base API
 *
 * Provides common create, get, update, and delete methods.
 */
class FreshdeskAPI extends FreshdeskTransport
{
    protected $NODE;

    /**
     * Create a resource
     *
     * @param  string $endpoint API Endpoint
     * @param  array  $data     Array of resource data
     * @return mixed            JSON object or FALSE
     */
    public function create($endpoint, $data)
    {
        parent::create($data);
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data)) return FALSE;
        // Encapsulate data in container node
        if (array_shift(array_keys($data)) != $this->NODE) $data = array($this->NODE => $data);
        // Return object else FALSE if we've failed to get a request response
        return $this->_request($endpoint, 'POST', $data) ?: FALSE;
    }

    /**
     * Retrieve a resource
     *
     * @param  string $endpoint API Endpoint
     * @return mixed            JSON object or FALSE
     */
    public function get($endpoint)
    {
        // Return object(s) else FALSE if we've failed to get a request response
        return $this->_request($endpoint) ?: FALSE;
    }

    /**
     * Retrieve all resources
     *
     * @param  string $endpoint API Endpoint
     * @return mixed            JSON object or FALSE
     */
    public function get_all($endpoint)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request($endpoint)) return FALSE;
        // Default object array
        $objects = array();
        // Return empty array of objects if HTTP 200 received
        if ($response == 200) return $objects;
        // Extract object data from its container node
        foreach ($response as $object) $objects[] = $object->{$this->NODE};
        // Return restructured array of objects
        return $objects;
    }

    /**
     * Update a resource
     *
     * @param  string $endpoint API Endpoint
     * @param  array  $data     Array of resource data
     * @return boolean          TRUE if HTTP 200 else FALSE
     */
    public function update($endpoint, $data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data)) return FALSE;
        // Encapsulate data in container node
        if (array_shift(array_keys($data)) != $this->NODE) $data = array($this->NODE => $data);
        // Return TRUE if HTTP 200 else FALSE
        return $this->_request($endpoint, 'PUT', $data) == 200 ? TRUE : FALSE;
    }

    /**
     * Delete a resource
     *
     * @param  string $endpoint API Endpoint
     * @return boolean          TRUE if HTTP 200 else FALSE
     */
    public function delete($endpoint)
    {
        // Return TRUE if HTTP 200 else FALSE
        return $this->_request($endpoint, 'DELETE') == 200 ? TRUE : FALSE;
    }
}

/**
 * Freshdesk Agent API
 *
 * Currently undocumented by Freshdesk
 *
 * @link http://freshdesk.com/api/#agent
 */
class FreshdeskAgent extends FreshdeskAPI
{
    protected $NODE = 'agent';

    public static $SCHEMA = array(
        'available'           => 'bool',          // Agent Available
        'created_at'          => 'string',        // Agent time created
        'id'                  => 'numeric',       // Agent ID
        'points'              => 'numeric',       // Agent Points               (?)
        'occasional'          => 'bool',          // Agent Occasional           (?)
        'scoreboard_level_id' => 'numeric',       // Agent Scoreboard Level ID  (?)
        'signature'           => 'string',        // Agent signature
        'signature_html'      => 'string',        // Agent signature HTML
        'ticket_permission'   => 'numeric',       // Agent Ticket permission    (?)
        'updated_at'          => 'string',        // Agent time last updated
        'user_id'             => 'numeric',       // User ID
        'user'                => 'FreshdeskUser'  // Incomplete set of User data
    );

    /**
     * Create a new Agent
     *
     * Currently unsupported
     *
     * @param  array   $data Array of Agent data
     * @return boolean       FALSE as unsupported
     */
    public function create($data)
    {
        // Currently unsupported
        return FALSE;
    }

    /**
     * Retrieve an Agent
     *
     * Request URL: /agents/[agent_id].json
     * Request method: GET
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"
     *      -X GET http://domain.freshdesk.com/agents/[agent_id].json
     *
     * Response:
     *     # TODO: FreshdeskAgent::get() response
     *
     * @link   http://freshdesk.com/api/#view_agent
     *
     * @param  integer $agent_id Agent ID
     * @return object            JSON Agent object
     */
    public function get($agent_id = NULL)
    {
        // Return all categories if no ID was passed
        if ( ! $agent_id) return $this->get_all();
        // Return parent method
        return parent::get("agents/{$agent_id}.json");
    }

    /**
     * Retrieve all Agents
     *
     * Request URL: agents.json
     * Request method: GET
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"
     *      -X GET http://domain.freshdesk.com/agents.json
     *
     * Response:
     *     # TODO: FreshdeskAgent::get_all() response
     *
     * @link   http://freshdesk.com/api/#view_all_agent
     *
     * @return array             Array of JSON Agent objects
     */
    public function get_all()
    {
        // Return parent method
        return parent::get_all("agents.json");
    }

    /**
     * Update an Agent
     *
     * Currently unsupported
     *
     * @param  integer $agent_id Agent ID
     * @param  array   $data     Array of Agent data
     * @return boolean           FALSE as unsupported
     */
    public function update($agent_id, $data)
    {
        // Currently unsupported
        return FALSE;
    }

    /**
     * Delete an Agent
     *
     * Currently unsupported
     *
     * @param  integer $agent_id Agent ID
     * @return boolean           FALSE as unsupported
     */
    public function delete($agent_id)
    {
        // Currently unsupported
        return FALSE;
    }
}

/**
 * Freshdesk User API
 *
 * Create, Retrieve, Update, and Delete Users
 *
 * @link http://freshdesk.com/api/#user
 */
class FreshdeskUser extends FreshdeskAPI
{
    protected $NODE = 'user';

    public static $SCHEMA = array(
        'id'             => 'numeric',  // User ID             (read-only)
        'name'           => 'string',   // User Name           (required)
        'email'          => 'string',   // User Email address  (required)
        'address'        => 'string',   // User Address
        'description'    => 'string',   // User Description
        'job_title'      => 'string',   // User Job Title
        'twitter_id'     => 'numeric',  // User Twitter ID
        'fb_profile_id'  => 'numeric',  // User Facebook ID
        'phone'          => 'numeric',  // User Telephone number
        'mobile'         => 'numeric',  // User Mobile number
        'language'       => 'string',   // User Language. 'en' is default
        'time_zone'      => 'string',   // User Time Zone
        'customer_id'    => 'numeric',  // User Customer ID
        'deleted'        => 'bool',     // True if deleted
        'helpdesk_agent' => 'bool',     // True if agent       (read-only)
        'active'         => 'bool',     // True if active
    );

    # TODO: More meaningful key names once roles are determined
    public static $ROLE = array(
        'ROLE_1' => 1,
        'ROLE_2' => 2,
        'ROLE_3' => 3
    );

    /**
     * Create a new User
     *
     * Request URL: /contacts.json
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
     * @param  array $data Array of User data
     * @return object      JSON User object
     */
    public function create($data)
    {
        // Return parent method
        return parent::create("contacts.json", $data);
    }

    /**
     * Retrieve a User
     *
     * Request URL: /contacts/[user_id].json
     * Request method: GET
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X GET \
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
     * @param  string $query   Filter query string
     * @return object          JSON User object
     */
    public function get($user_id = NULL, $query = NULL)
    {
        // Return all users if no User ID or if get_all() args were passed
        if ( ! ($state = $user_id) or is_string($state) or is_string($query))
        {
            return $this->get_all($state, $query);
        }
        // Return parent method
        return parent::get("contacts/{$user_id}.json");
    }

    /**
     * Retrieve all Users
     *
     * Request URL: /contacts.json
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
     * @param  string $query Filter query string
     * @return array         Array of JSON User objects
     */
    public function get_all($state = '', $query = '')
    {
        // Return parent method
        return parent::get_all("contacts.json?state={$state}&query={$query}");
    }

    /**
     * Update a User
     *
     * Request URL: /contacts/[user_id].json
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
     * @param  integer $user_id User ID
     * @param  array   $data    Array of User data
     * @return mixed            JSON User object or FALSE
     */
    public function update($user_id, $data)
    {
        // Return object if parent method succeeds
        return parent::update("contacts/{$user_id}.json", $data) ? $this->get($user_id) : FALSE;
    }

    /**
     * Delete a User
     *
     * Request URL: /contacts/[user_id].json
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
     * @param  integer $user_id User ID
     * @return boolean          TRUE if HTTP 200 else FALSE
     */
    public function delete($user_id)
    {
        // Return parent method
        return parent::delete("contacts/{$user_id}.json");
    }
}

class FreshdeskForumCategory extends FreshdeskAPI
{
    // public $Forum;

    protected $NODE = 'forum_category';

    public function __construct($params)
    {
        parent::__construct($params);
        // $this->Forum = new FreshdeskForum($params);
    }

    public static $SCHEMA = array(
        'id'          => 'numeric',  // Unique id of the forum category Read-Only
        'name'        => 'string',   // Name of the forum category Mandatory
        'description' => 'string',   // Description of the forum category
        'position'    => 'numeric'   // The rank of the category in the category listing
    );

    public function create($data)
    {
        // Return default method
        return parent::create("categories.json", $data);
    }

    public function get($category_id = NULL)
    {
        // Return all categories if no ID was passed
        if ( ! $category_id) return $this->get_all();
        // Return default method
        return parent::get("categories/{$category_id}.json");
    }

    public function get_all()
    {
        // Return default method
        return parent::get_all("categories.json");
    }

    public function update($category_id, $data)
    {
        // Return object if parent method succeeds
        return parent::update("categories/{$category_id}.json", $data) ? $this->get($category_id) : FALSE;
    }

    public function delete($category_id)
    {
        // Return default method
        return parent::delete("categories/{$category_id}.json");
    }
}

class FreshdeskForum extends FreshdeskAPI
{
    // public $ForumCategory;

    protected $NODE = 'forum';

    public static $SCHEMA = array(
        'id'                => 'numeric',  // Unique id of the forum Read-Only
        'name'              => 'string',   // Name of the forum Mandatory
        'description'       => 'string',   // Description about the forum
        'forum_category_id' => 'numeric',  // ID of the category of this forum
        'forum_type'        => 'numeric',  // Describes the type of forum (Supported types can be referred in Forum properties above )Mandatory
        'forum_visibility'  => 'numeric',  // Describes whether the forum is visible to all or logged in user or Agents or selected companies Mandatory
        'position'          => 'numeric',  // The rank of the forum in the forum listing
        'posts_count'       => 'numeric',  // The number of comments on that forum
        'topics_count'      => 'numeric'   // The number of topics in the forum
    );

    public static $TYPE = array(
        'HOWTO'        => 1,
        'IDEA'         => 2,
        'PROBLEM'      => 3,
        'ANNOUNCEMENT' => 4
    );

    public static $VISIBILITY = array(
        'ALL'    => 1,
        'USERS'  => 2,
        'AGENTS' => 3
    );

    public function __construct($params)
    {
        parent::__construct($params);
        //$this->ForumCategory = new FreshdeskForumCategory($params);
    }

    public function create($category_id, $data)
    {
        // Determine type and visibility
        // $type = is_string($type) ? @self::$TYPE[$type] : $type;
        // $visibility = is_string($visibility) ? @self::$VISIBILITY[$visibility] : $visibility;

        // Return parent method
        return parent::create("categories/{$category_id}/forums.json", $data);
    }

    public function get($category_id, $forum_id = NULL)
    {
        // Return all forums if no Forum ID was passed
        if ( ! $forum_id) return $this->get_all($category_id);
        // Return parent method
        return parent::get("categories/{$category_id}/forums/{$forum_id}.json");
    }

    public function get_all($category_id)
    {
        # TODO
        // return $this->ForumCategory->get($category_id);
    }

    public function update($category_id, $forum_id, $data)
    {
        // Determine type and visibility
        // $type = $type and is_string($type) ? @self::$TYPE[$type] : $type;
        // $visibility = $visibility and is_string($visibility) ? @self::$VISIBILITY[$visibility] : $visibility;

        // Return parent method
        return parent::update("categories/{$category_id}/forums/{$forum_id}.json", $data);
    }

    public function delete($category_id, $forum_id)
    {
        // Return parent method
        return parent::delete("categories/{$category_id}/forums/{$forum_id}.json");
    }
}

class FreshdeskTopic extends FreshdeskAPI
{
    protected $NODE = 'topic';

    public static $SCHEMA = array(
        'id'           => 'numeric',  // Unique id of the topic Read-Only
        'title'        => 'string',   // Title of the forum Mandatory
        'forum_id'     => 'numeric',  // ID of the Forum in which this topic is present
        'hits'         => 'numeric',  // Number of views of that forum Read-Only
        'last_post_id' => 'numeric',  // ID of the latest comment on the forum Read-Only
        'locked'       => 'boolean',  // Set as true if the forum is locked
        'posts_count'  => 'numeric',  // Number of posts in that topic
        'sticky'       => 'numeric',  // Describes whether a topic can be deleted or not
        'user_id'      => 'numeric',  // ID of the user Read-Only
        'user_votes'   => 'numeric',  // Number of votes in the topic Read-Only
        'replied_at'   => 'string',   // Timestamp of the latest comment made in the topic Read-Only
        'replied_by'   => 'string'    // ID of the user who made the latest comment in that topic Read-Only
    );

    public static $STAMP = array(
        'PLANNED'     => 1,
        'IMPLEMENTED' => 2,
        'TAKEN'       => 3
    );

    public function create($category_id, $forum_id, $data)
    {
        // Return parent method
        return parent::create("categories/{$category_id}/forums/{$forum_id}/topics.json", $data);
    }

    public function get($category_id, $forum_id, $topic_id = NULL)
    {
        // Return all topics if no Topic ID was passed
        if ( ! $topic_id) return $this->get_all($category_id, $forum_id);
        // Return parent method
        return parent::get("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json");
    }

    public function get_all($category_id, $forum_id) {
        # TODO: implement
    }

    public function update($category_id, $forum_id, $topic_id, $data)
    {
        // Return parent method
        return parent::update("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json", $data);
    }

    public function delete($category_id, $forum_id, $topic_id)
    {
        // Return parent method
        return parent::delete("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}.json");
    }
}

class FreshdeskPost extends FreshdeskAPI
{
    protected $NODE = 'post';

	public function create($category_id, $forum_id, $topic_id, $data)
	{
        // Return parent method
        return parent::create("posts.json?category_id={$category_id}&forum_id={$forum_id}&topic_id={$topic_id}", $data);
	}

    public function get($category_id, $forum_id, $topic_id, $post_id = NULL)
    {
        // Return all forums if no Forum ID was passed
        if ( ! $post_id) return $this->get_all($category_id, $forum_id, $topic_id);
        # TODO: implement
    }

    public function get_all($category_id, $forum_id, $topic_id)
    {
        # TODO implement
    }

	public function update($category_id, $forum_id, $topic_id, $data)
	{
        // Return parent method
        return parent::update("posts.json?category_id={$category_id}&forum_id={$forum_id}&topic_id{$topic_id}", $data);
	}

	public function delete($category_id, $forum_id, $topic_id, $post_id)
    {
        // Return parent method
        return parent::update("posts/{$post_id}.json?category_id={$category_id}&forum_id={$forum_id}&topic_id={$topic_id}");
    }
}

class FreshdeskWrapper extends FreshdeskTransport
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
        if ( ! $arg0 = @$args[0]) return;
        // Set args if only args passed
        if (is_array($arg0)) $this->args = $arg0;
        // Set args and data if id was passed
        else if ($this->id = intval(array_shift($args)))
        {
            $this->args = @$args[0];
            $this->data = $this->get();
        }
    }

    public function __get($name)
    {
        if ($value = (@$this->args[$name] ?: @$this->data->$name)) return $value;
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
        return $this->id ? $this->api->get($this->id) : FALSE;
    }

    public function update($args = NULL)
    {
        return $this->id ? $this->api->update($this->id, $args ?: $this->args) : FALSE;
    }

    public function delete()
    {
        return $this->id ? $this->api->delete($this->id) : FALSE;
    }
}

class FreshdeskAgentWrapper extends FreshdeskWrapper {}
class FreshdeskUserWrapper extends FreshdeskWrapper {}
class FreshdeskForumCategoryWrapper extends FreshdeskWrapper {}
class FreshdeskForumWrapper extends FreshdeskWrapper {}
class FreshdeskTopicWrapper extends FreshdeskWrapper {}
class FreshdeskPostWrapper extends FreshdeskWrapper {}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

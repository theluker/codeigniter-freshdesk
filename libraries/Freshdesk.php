<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Freshdesk
{
    private $CI;
    private $params;
    private static $apis = array('Agent', 'User', 'ForumCategory', 'Forum', 'Topic', 'Post', 'Monitor');

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

        // Instantiate APIs
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

class FreshdeskBase extends FreshdeskAPI
{
    protected $NODE;

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

    public function get($endpoint)
    {
        // Return object(s) else FALSE if we've failed to get a request response
        return $this->_request($endpoint) ?: FALSE;
    }

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

    public function update($endpoint, $data)
    {
        // Return FALSE if we did not receive an array of data
        if ( ! is_array($data)) return FALSE;
        // Encapsulate data in container node
        if (array_shift(array_keys($data)) != $this->NODE) $data = array($this->NODE => $data);
        // Return TRUE if HTTP 200 else FALSE
        return $this->_request($endpoint, 'PUT', $data) == 200 ? TRUE : FALSE;
    }

    public function delete($endpoint)
    {
        // Return TRUE if HTTP 200 else FALSE
        return $this->_request($endpoint, 'DELETE') == 200 ? TRUE : FALSE;
    }
}

class FreshdeskAgent extends FreshdeskBase
{
    protected $NODE = 'agent';

    public static $SCHEMA = array(
        'available'           => 'bool',
        'created_at'          => 'string',
        'id'                  => 'numeric',
        'points'              => 'numeric',
        'occasional'          => 'bool',
        'scoreboard_level_id' => 'numeric',
        'signature'           => 'string',
        'signature_html'      => 'string',
        'ticket_permission'   => 'numeric',
        'updated_at'          => 'string',
        'user_id'             => 'numeric',
        'user'                => 'FreshdeskUser'
    );

    public function create($data)
    {
        # TODO: implement method
        return FALSE;
    }

    public function get($agent_id = NULL)
    {
        // Return all categories if no ID was passed
        if ( ! $agent_id) return $this->get_all();
        // Return parent method
        return parent::get("agents/{$agent_id}.json");
    }

    public function get_all()
    {
        // Return parent method
        return parent::get_all("agents.json");
    }

    public function update($agent_id, $data)
    {
        # TODO: implement method
        return FALSE;
    }

    public function delete($agent_id)
    {
        # TODO: implement method
        return FALSE;
    }
}

class FreshdeskUser extends FreshdeskBase
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

    public function create($data)
    {
        // Return parent method
        return parent::create("contacts.json", $data);
    }

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

    public function get_all($state = '', $query = '')
    {
        // Return parent method
        return parent::get_all("contacts.json?state={$state}&query={$query}");
    }

    public function update($user_id, $data)
    {
        // Return object if parent method succeeds
        return parent::update("contacts/{$user_id}.json", $data) ? $this->get($user_id) : FALSE;
    }

    public function delete($user_id)
    {
        // Return parent method
        return parent::delete("contacts/{$user_id}.json");
    }
}

class FreshdeskForumCategory extends FreshdeskBase
{
    protected $NODE = 'forum_category';

    // public $Forum;

    public function __construct($params)
    {
        FreshdeskAPI::__construct($params);
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

class FreshdeskForum extends FreshdeskBase
{
    protected $NODE = 'forum'; # TODO: move node to schema;

    // public $ForumCategory;

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
        FreshdeskAPI::__construct($params);
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

class FreshdeskTopic extends FreshdeskBase
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

class FreshdeskPost extends FreshdeskBase
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

class FreshDeskMonitor extends FreshdeskAPI
{
    # TODO: FreshdeskUser->get_monitored()
	public function get($user_id)
	{
        // Return FALSE if we've failed to get a request response
		return $this->_request("support/discussions/user_monitored?user_id={$user_id}") ?: FALSE;
	}

    # TODO: FreshdeskUser->check_monitored()
	public function check($user_id, $topic_id)
	{
        // Return FALSE if we've failed to get a request response
		return $this->_request("support/discussions/topics/{$topic_id}/check_monitor.json?user_id={$user_id}") ?: FALSE;
	}

    # TODO: FreshdeskTopic->monitor()
    public function monitor($category_id, $forum_id, $topic_id)
	{
		// Return TRUE if HTTP 200 else FALSE
		return $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}/monitorship.json", 'POST') == 200 ? TRUE : FALSE;
	}

    # TODO: FreshdeskTopic->unmonitor()
	public function unmonitor($category_id, $forum_id, $topic_id)
	{
		// Return TRUE if HTTP 200 else FALSE
		return $this->_request("categories/{$category_id}/forums/{$forum_id}/topics/{$topic_id}/monitorship.json", 'DELETE') == 200 ? TRUE : FALSE;
	}
}

class FreshdeskTicket extends FreshdeskAPI
{
    public static $SCHEMA = array(
        'ticket' => array(
            'display_id'       => 'numeric',
            'email'            => 'string',
            'requester_id'     => 'numeric',
            'subject'          => 'string',
            'description'      => 'string',
            'description_html' => 'string',
            'status'           => 'numeric',
            'priority'         => 'numeric',
            'source'           => 'numeric',
            'deleted'          => 'boolean',
            'spam'             => 'boolean',
            'responder_id'     => 'numeric',
            'group_id'         => 'numeric',
            'ticket_type'      => 'numeric',
            'to_email'         => 'array',
            'cc_email'         => 'array',
            'email_config_id'  => 'numeric',
            'isescalated'      => 'boolean',
            'due_by'           => 'string',
            'id'               => 'numeric',
            'attachements'     => 'array'
        ),
        'note' => array(
            'id'          => 'number',
            'body'        => 'string',
            'body_html'   => 'string',
            'attachments' => 'array',
            'user_id'     => 'number',
            'private'     => 'boolean',
            'to_emails'   => 'array',
            'deleted'     => 'boolean'
        )
    );

    public static $SOURCE = array(
        'EMAIL'    => 1,
        'PORTAL'   => 2,
        'PHONE'    => 3,
        'FORUM'    => 4,
        'TWITTER'  => 5,
        'FACEBOOK' => 6,
        'CHAT'     => 7
    );

    public static $STATUS = array(
        'OPEN'     => 1,
        'PENDING'  => 2,
        'RESOLVED' => 3,
        'CLOSED'   => 4
    );

    public static $PRIORITY = array(
        'LOW'      => 1,
        'MEDIUM'   => 2,
        'HIGH'     => 3,
        'URGENT'   => 4
    );

    public function create($data)
    {
      return $this->_request("helpdesk/tickets.json", "POST", $data) ?: FALSE;
    }

    public function get($ticket_id)
    {
        return $this->_request("/helpdesk/tickets/{$ticket_id}") ?: FALSE;
    }

    public function get_all($ticket_id = '', $filter = '', $data = '')
    {
        $DEFAULT_FILTERS = array(
            'ALL'       => 'all_tickets',
            'NEW'       => 'new_my_open',
            'MONITORED' => 'monitored_by',
            'SPAM'      => 'spam',
            'DELETED'   => 'deleted'
        );

        $INFO_FILTERS = array(
            'NAME'  => 'company_name',
            'ID'    => 'company_id',
            'EMAIL' => 'email'
        );

        $filter = strtoupper($filter);
        // If filter variable exists in our default filters we don't require data
        if (in_array($filter, array_keys($DEFAULT_FILTERS)))
        {
            return $this->_request("helpdesk/tickets/{$DEFAULT_FILTERS[$filter]}/?format=json") ?: FALSE;
        }
        // Data is required past this point
        if ( ! $data) return FALSE;
        // If filter variable exists in our info filters we require data to be passed
        if (in_array($filter, array_keys($INFO_FILTERS)))
        {
            return $this->_request("helpdesk/tickets.json?{$INFO_FILTERS[$filter]}={$data}&filter_name=all_tickets") ?: FALSE;
        }
        // If filter variable is VIEW we require a view_id
        if($filter == "VIEW")
        {
            return $this->_request("helpdesk/tickets/view/{$data}?format=json") ?: FALSE;
        }
        // If filter variable is REQUESTER we require a requester_id
        if($filter == "REQUESTER")
        {
            return $this->_request("helpdesk/tickets/filter/requester/{$data}?format=json") ?: FALSE;
        }
        // Return all tickets by default
        return $this->_request("helpdesk/tickets.json") ?: FALSE;
    }

    public function update() {}
    public function pick() {}
    public function delete() {}
    public function restore() {}
    public function assign() {}
    public function get_all_ticket_fields() {}
    public function add_note() {}
}

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
class FreshdeskMonitorWrapper extends FreshdeskWrapper {}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

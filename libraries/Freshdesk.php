<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * All documentation Copyright © Freshdesk Inc. (http://freshdesk.com/api)
 */

# TODO: change access methods: $this->freshdesk->Forum($forum_id)->update();
# TODO: determine if update() methods require args if no change

/**
 * Freshdesk Library
 */
class Freshdesk
{
    private $CI;

    protected $api_key;
    protected $username;
    protected $password;
    protected $base_url;

    public $User;
    public $Agent;
    public $ForumCategory;
    public $Forum;

    public function __construct($params = array())
    {
        // Get CI instance
        $this->CI =& get_instance($params);

        // Attempt to load config values from file
        if ($config = $this->CI->config->load('freshdesk', TRUE, TRUE))
        {
            $this->api_key = $this->CI->config->item('api_key', 'freshdesk');
            $this->username = $this->CI->config->item('username', 'freshdesk');
            $this->password = $this->CI->config->item('password', 'freshdesk');
            $this->base_url = $this->CI->config->item('base_url', 'freshdesk');
        }
        // Attempt to load config values from params
        $this->api_key = @$params['api_key'] ?: @$params['api-key'];
        $this->username = @$params['username'];
        $this->password = @$params['password'];
        $this->base_url = @$params['base_url'] ?: @$params['base-url'];

        // API Key takes precendence
        if ($this->api_key)
        {
            $this->username = $this->api_key;
            $this->password = 'X';
        }

        // Instantiate API accessors
        $this->User = new FreshdeskUser($this->base_url, $this->username, $this->password);
        $this->Agent = new FreshdeskAgent($this->base_url, $this->username, $this->password);
        $this->ForumCategory = new FreshdeskForumCategory($this->base_url, $this->username, $this->password);
        $this->Forum = new FreshdeskForum($this->base_url, $this->username, $this->password);
    }
}

/**
 * Freshdesk API
 */
class FreshdeskAPI
{
    private $username;
    private $password;
    protected $base_url;

    public function __construct($base_url, $username, $password)
    {
        $this->base_url = $base_url;
        $this->username  = $username;
        $this->password  = $password;
    }

    /**
     * Perform an API request.
     *
     * @param  string $endpoint Freshdesk API endpoint
     * @param  string $method   HTTP request method
     * @param  array  $data     HTTP PUT/POST data
     * @return mixed            SimpleXML object or HTTP response code
     */
    protected function _request($endpoint, $method = 'GET', $data = NULL)
    {
        $method = strtoupper($method);

        $ch = curl_init ("{$this->base_url}/{$endpoint}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Set POST data if passed to method
        if ($data)
        {
            $root = array_keys($data)[0];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_toXML($data[$root], $root)->asXML());
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

    private function _toXML($data, $root, &$xml = null)
    {
        // Initialize XML if first run
        if (is_null($xml))
        {
            $xml = new SimpleXMLElement("<{$root}/>");
        }

        // Iterate nodes
        foreach ($data as $key => $value)
        {
            // Recurse if value is array
            if (is_array($value))
            {
                $node = $xml->addChild($key);
                $this->_toXML($value, $root, $node);
            }
            else
            {
                $xml->addChild($key, $value);
            }
        }

        // Return XML
        return $xml;
    }
}

/**
 * Freshdesk User
 *
 * Create, View, Update, and Delete Users.
 *
 * @link http://freshdesk.com/api/users
 */
class FreshdeskUser extends FreshdeskAPI
{
    /**
     * Create a new User.
     *
     * Request URL: domain_URL/contacts.xml
     * Request method: POST
     *
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <user>
     *       <name>Your User</name>                  <!--(Mandatory)-->
     *       <email>youruser@yourcompany.com</email> <!--(Mandatory)-->
     *     </user>
     * Response:
     *     <?xml version="1.0" encoding="UTF-8" ?>
     *     <user>
     *       <active type="boolean">false</active>
     *       <created-at type="datetime">2012-12-12T16:26:34+05:30</created-at>
     *       <customer-id type="integer">2</customer-id>
     *       <deleted type="boolean">false</deleted>
     *       <email>test@abc.com</email>
     *       <external-id nil="true" />
     *       <fb-profile-id nil="true" />
     *       <id type="integer">16</id>
     *       <language>en</language>
     *       <name>Test</name>
     *       <time-zone>Chennai</time-zone>
     *       <updated-at type="datetime">2013-01-09T17:16:03+05:30</updated-at>
     *       <user-role type="integer">3</user-role>
     *      </user>
     *
     * @link http://freshdesk.com/api/users#create-users
     *
     * @param  string $name  User Name
     * @param  string $email User Description
     * @return object        User object
     */
    public function create($name, $email = '')
    {
        // Build array of request data
        $data = array(
            'user' => array(
                'name' => $name,
                'email' => $email
            )
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts.xml", 'POST', $data))
        {
            return FALSE;
        }

        // Return User object
        return $response;
    }

    /**
     * View all Users.
     *
     * Request URL: domain_URL/contacts.xml
     * Request method: GET
     *
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <users type="array">
     *       <user>
     *         <active type="boolean">false</active>
     *         <created-at type="datetime">2012-12-12T16:26:34+05:30</created-at>
     *         <customer-id type="integer">2</customer-id>
     *         <deleted type="boolean">false</deleted>
     *         <email>test@abc.com</email>
     *         <external-id nil="true" />
     *         <fb-profile-id nil="true" />
     *         <id type="integer">16</id>
     *         <language>en</language>
     *         <name>Test</name>
     *         <time-zone>Chennai</time-zone>
     *         <updated-at type="datetime">2013-01-09T17:16:03+05:30</updated-at>
     *         <user-role type="integer">3</user-role>
     *        </user>
     *        ...
     *      </users>
     *
     * Note:
     *     By default only verified users will be returned.
     *     To view unverified users, pass the 'state' parameter to the method.
     *
     * @link   http://freshdesk.com/api/users#view-all-users
     *
     * @param  string   User state
     * @return array    Array of User Objects
     */
    public function getAll($state = NULL)
    {
        // Build request string
        $request = "contacts.xml";
        if ($state)
        {
            $request .= "?state={$state}";
        }

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request($request))
        {
            return FALSE;
        }

        // Default user array
        $users = array();

        // Extract user data from its 'user' container
        foreach (@$response->user as $user)
        {
            $users[] = $user;
        }

        // Return restructured array of users
        return $users;
    }

    /**
     * View a User.
     *
     * Request URL: domain_URL/contacts/[user_id].xml
     * Request method: GET
     *
     * Response:
     *     <?xml version="1.0" encoding="UTF-8" ?>
     *     <user>
     *       <active type="boolean">false</active>
     *       <created-at type="datetime">2012-12-12T16:26:34+05:30</created-at>
     *       <customer-id type="integer">2</customer-id>
     *       <deleted type="boolean">false</deleted>
     *       <email>test@abc.com</email>
     *       <external-id nil="true" />
     *       <fb-profile-id nil="true" />
     *       <id type="integer">16</id>
     *       <language>en</language>
     *       <name>Test</name>
     *       <time-zone>Chennai</time-zone>
     *       <updated-at type="datetime">2013-01-09T17:16:03+05:30</updated-at>
     *       <user-role type="integer">3</user-role>
     *     </user>
     *
     * @link   http://freshdesk.com/api/users#view-a-particular-user
     *
     * @param  integer $user_id     User ID
     * @return mixed                Array or single User Object
     */
    public function get($user_id = NULL)
    {
        // Return all users if no Category ID was passed
        if ( ! $user_id)
        {
            return $this->getAll();
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.xml"))
        {
            return FALSE;
        }

        // Return User object(s)
        return $response;
    }

    /**
     * Update an existing User.
     *
     * Request URL: domain_URL/contacts/[user_id].xml
     * Request method: PUT
     *
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <user>
     *       <name>Your User</name>                   <!--- (Optional) --->
     *       <email>youruser@yourcompany.com</email>  <!--- (Optional) --->
     *     </user>
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/users#modify-user-details
     *
     * @param  integer $user_id  User ID
     * @param  string  $name     User Name
     * @param  string  $email    User Description
     * @return object            User object
     * @return integer           HTTP response code
     */
    public function update($user_id, $name = '', $email = '')
    {
        // Build array of request data
        $data = array(
            'user' => array(
                'name' => $name,
                'email' => $email
            )
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.xml", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }

    /**
     * Delete an existing User.
     *
     * Request URL: domain_URL/contacts/[user_id].xml
     * Request method: DELETE
     *
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/users#delete-a-user
     *
     * @param  integer $user_id User ID
     * @return integer          HTTP response code
     */
    public function delete($user_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.xml", 'DELETE'))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }
}

/**
 * Freshdesk Agent
 */
class FreshdeskAgent extends FreshdeskAPI
{
    public function get($agent_id = NULL)
    {
        // Return all agents if no Agent ID was passed
        if ( ! $agent_id)
        {
            return $this->getAll();
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("agents/{$agent_id}.xml"))
        {
            return FALSE;
        }

        // Return Agent object(s)
        return $response;
    }

    public function getAll()
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("agents.xml"))
        {
            return FALSE;
        }

        // Default agent array
        $agents = array();

        // Extract agent data from its agents container
        foreach ($response as $agent)
        {
            $agents[] = $agent;
        }

        // Return restructured array of agents
        return $agents;
    }
}

/**
 * Freshdesk Forum Category
 *
 * Create, View, Update, and Delete Forum Categories.
 *
 * @link http://freshdesk.com/api/forums/forum-category
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
     * @return array    Array of Forum Category Objects
     */
    public function getAll()
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
    public function get($category_id = NULL)
    {
        // Return all categories if no Category ID was passed
        if ( ! $category_id)
        {
            return $this->getAll();
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}.xml"))
        {
            return FALSE;
        }

        // Return Forum Category object(s)
        return $response;
    }

    /**
     * Update an existing Forum Category.
     *
     * Request URL: domain_URL/categories/[category_id].xml
     * Request method: PUT
     *
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum-category>
     *       <name>Test</name>                                <!--- (Optional) --->
     *       <description>New testing category</description>  <!--- (Optional) --->
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

    # TODO: More meaningful key names once types are determined
    public static $TYPE = array(
        'TYPE_1' => 1,
        'TYPE_2' => 2
    );
    # TODO: More meaningful key names once visibility is determined
    public static $VISIBILITY = array(
        'VIS_1' => 1
    );

    public function __construct($base_url, $username, $password)
    {
        FreshdeskAPI::__construct($base_url, $username, $password);
        $this->ForumCategory = new FreshdeskForumCategory($this->base_url, $this->username, $this->password);
    }

    /**
     * Create a new Forum.
     *
     * Request URL: domain_URL/categories/[category_id]/forums.xml
     * Request method: POST
     *
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum>
     *       <name>Announcement</name>                   <!--- (Mandatory) --->
     *       <forum-visibility>1</forum-visibility>      <!--- (Mandatory) --->
     *       <forum-type>2</forum-type>                  <!--- (Mandatory) --->
     *       <description>Testing for API</description>  <!--- (Optional) --->
     *     </forum>
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum>
     *       <description>Testing for API</description>
     *       <description-html><p>Testing for <span class="caps">API</span></p>
     *       </description-html>
     *       <forum-category-id type="integer">3</forum-category-id>
     *       <forum-type type="integer">2</forum-type>
     *       <forum-visibility type="integer">1</forum-visibility>
     *       <id type="integer">5</id>
     *       <name>Announcement</name>
     *       <position type="integer">1</position>
     *       <posts-count type="integer">0</posts-count>
     *       <topics-count type="integer">0</topics-count>
     *     </forum>
     *
     * @link http://freshdesk.com/api/forums/forum#create-a-forum
     *
     * @todo   Determine avilable type/visibility options.
     * @todo   Determine commonly default type/visibility option.
     *
     * @param  string $name        Forum Name
     * @param  string $type        Forum Type
     * @param  string $visibility  Forum Visibility
     * @param  string $description Forum Description
     * @return object              Forum object
     */
    public function create($category_id, $name, $type, $visibility, $description = '')
    {
        // Determine type and visibility
        $type = is_string($type) ? @self::$TYPE[$type] : $type;
        $visibility = is_string($visibility) ? @self::$VISIBILITY[$visibility] : $visibility;

        // Build array of request data
        $data = array(
            'forum' => array(
                'name' => $name,
                'forum-type' => $type,
                'forum-visibility' => $visibility,
                'description' => $description
            )
        );

        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums.xml", 'POST', $data))
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
    public function getAll($category_id)
    {
        return $this->ForumCategory->get($category_id);
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
     * @return array    Array of Forum Category Objects
     */
    public function getAll()
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
     * View all Forums.
     *
     * Request URL: domain_URL/categories/[category_id]/forums/[forum_id].xml
     * Request method: POST
     *
     * Response:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum>
     *       <description>Customers can voice their ideas here.</description>
     *       <description-html>
     *         <p>Customers can voice their ideas here.</p>
     *       </description-html>
     *       <forum-category-id type="integer">2</forum-category-id>
     *       <forum-type type="integer">2</forum-type>
     *       <id type="integer">6</id>
     *       <name>Feature Requests</name>
     *       <position type="integer">6</position>
     *       <posts-count type="integer">11</posts-count>
     *       <topics-count type="integer">7</topics-count>
     *       <forum-category>
     *         <created-at type="datetime">2011-03-15T02:23:15-07:00</created-at>
     *         <description>Welcome to the Freshdesk community forums</description>
     *         <id type="integer">2</id>
     *         <name>Freshdesk Forums</name>
     *         <updated-at type="datetime">2011-03-21T02:42:58-07:00</updated-at>
     *       </forum-category>
     *       <topics type="array">
     *         <topic>
     *           <created-at type="datetime">2011-04-06T07:38:15-07:00</created-at>
     *           <delta type="boolean">false</delta>
     *           <forum-id type="integer">6</forum-id>
     *           <hits type="integer">4</hits>
     *           <id type="integer">41</id>
     *           <last-post-id type="integer">66</last-post-id>
     *           <locked type="boolean">false</locked>
     *           <posts-count type="integer">0</posts-count>
     *           <replied-at type="datetime">2011-04-06T07:38:15-07:00</replied-at>
     *           <replied-by type="integer">464</replied-by>
     *           <stamp-type type="integer" nil="true"></stamp-type>
     *           <sticky type="integer">0</sticky>
     *           <title>Group tickets by type</title>
     *           <updated-at type="datetime">2011-04-06T07:38:15-07:00</updated-at>
     *           <user-id type="integer">464</user-id>
     *         </topic>
     *         ...
     *       </topics>
     *     </forum>
     *
     * @link   http://freshdesk.com/api/forums/forum#view-topics-in-a-forum
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
            return $this->getAll($category_id);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("domain_URL/categories/{$category_id}/forums/{$forum_id}.xml", 'POST'))
        {
            return FALSE;
        }

        // Return Forum object(s)
        return $response;
    }

    /**
     * Update an existing Forum.
     *
     * Request URL: domain_URL/categories/[category_id]/forums/[forum_id].xml
     * Request method: PUT
     *
     * Request:
     *     <?xml version="1.0" encoding="UTF-8"?>
     *     <forum>
     *       <name>Announcement</name>                   <!--- (Optional) --->
     *       <forum-visibility>1</forum-visibility>      <!--- (Optional) --->
     *       <forum-type>2</forum-type>                  <!--- (Optional) --->
     *       <description>Testing for API</description>  <!--- (Optional) --->
     *     </forum>
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/forums/forum#update-a-forum
     *
     * @todo   Determine avilable type/visibility options.
     * @todo   Determine commonly default type/visibility option.
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @param  string  $name        Forum Name
     * @param  string  $type        Forum Type
     * @param  string  $visibility  Forum Visibility
     * @param  string  $description Forum Description
     * @return integer              HTTP response code
     */
    public function update($category_id, $forum_id, $name = '', $type = '', $visibility = '', $description = '')
    {
        // Determine type and visibility
        $type = $type and is_string($type) ? @self::$TYPE[$type] : $type;
        $visibility = $visibility and is_string($visibility) ? @self::$VISIBILITY[$visibility] : $visibility;

        // Build array of request data
        $data = array(
            'forum' => array(
                'name' => $name,
                'forum-type' => $type,
                'forum-visibility' => $visibility,
                'description' => $description
            )
        );

       // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}.xml", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }

    /**
     * Delete an existing Forum.
     *
     * Request URL: domain_URL/categories/[category_id]/forums/[forum_id].xml
     * Request method: DELETE
     *
     *  Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/users#delete-a-user
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @return integer              HTTP response code
     */
    public function delete($category_id, $forum_id)
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("categories/{$category_id}/forums/{$forum_id}.xml", 'DELETE'))
        {
            return FALSE;
        }

        // Return Forum Category object
        return $response;
    }
}

/**
 * Freshdesk Topic
 *
 * Create, View, Update, Delete and Monitor Topics.
 *
 * @link http://freshdesk.com/api/forums/forum-topic
 */
class FreshdeskTopic extends FreshdeskAPI
{
    public function create() {}
    public function getAll() {}
    public function get() {}
    public function update() {}
    public function delete() {}
    public function monitor() {}
    public function unmonitor() {}
}

/**
 * Freshdesk Post
 *
 * Create, View, Update, and Delete Posts.
 *
 * @link http://freshdesk.com/api/forums/forum-topic
 */
class FreshdeskPost extends FreshdeskAPI
{
    public function create() {}
    public function getAll() {}
    public function get() {}
    public function update() {}
}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

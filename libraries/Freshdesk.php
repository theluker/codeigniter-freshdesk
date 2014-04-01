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
        $this->params['base_url'] = $base_url;
        $this->params['username'] = $username;
        $this->params['password'] = $password;

        // Build a list of API accessors
        $this->accessors = ['User'];

        // Instantiate API accessors
        foreach ($this->accessors as $accessor)
        {
            $class = "Freshdesk{$accessor}";
            $this->$accessor = new $class($this->params['base_url'], $this->params['username'], $this->params['password']);
        }
    }

    public function __call($name, $args)
    {
        // Dynamically load and return wrapped API accessor
        if (in_array($name, $this->accessors))
        {
            $class = "_Freshdesk{$name}";
            return new $class($this->params, $args);
        }
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
        if (in_array($info['http_code'], [404, 406, 302]) and $error = $data)
        {
            log_message('error', var_dump($error));
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
 * Freshdesk User
 *
 * Create, View, Update, and Delete Users.
 *
 * @link http://freshdesk.com/api/users
 */
class FreshdeskUser extends FreshdeskAPI
{
    # TODO: More meaningful key names once roles are determined
    public static $ROLE = array(
        'ROLE_1' => 1,
        'ROLE_2' => 2,
        'ROLE_3' => 3
    );

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

    public function get_all($state = 'verified')
    {
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts.json?state={$state}"))
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

    public function get($user_id = NULL)
    {
        // Return all users if no Category ID was passed
        if ( ! $user_id or is_string($user_id))
        {
            return $this->get_all($user_id);
        }
        // Return FALSE if we've failed to get a request response
        if ( ! $response = $this->_request("contacts/{$user_id}.json"))
        {
            return FALSE;
        }

        // Return User object(s)
        return $response;
    }

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
        if ( ! $response = $this->_request("contacts/{$user_id}.xml", 'PUT', $data))
        {
            return FALSE;
        }

        // Return HTTP response
        return $response;
    }

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
 * Wrapped Freshdesk User
 *
 * Allows various class values to be set at instantiation.
 */
class _FreshDeskUser extends FreshdeskUser
{
    private $user_id;

    public function __construct($params, $args)
    {
        $this->user_id = @$args[0];
        $this->data = @$args[1];
        FreshdeskUser::__construct($params['base_url'], $params['username'], $params['password']);
    }

    public function create()
    {
        return FreshdeskUser::create($this->user_id);
    }

    public function get()
    {
        return FreshdeskUser::get($this->user_id);
    }

    public function update($data = NULL)
    {
        return FreshdeskUser::update($this->user_id, $data ?: $this->data);
    }

    public function delete()
    {
        return FreshdeskUser::delete($this->user_id);
    }
}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

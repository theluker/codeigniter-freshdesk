<?php



/**
 * FreshDesk Forum
 */
// class FreshDeskForum extends FreshDeskAPI
// {
//     public function __construct()
//     {
//     }

//     protected function topics($category_id, $forum_id) {}

//     protected function create() {}
//     protected function update() {}
//     protected function delete() {}
// }











/**
 * FreshDesk API
 */
class FreshDeskAPI
{
    private $api_key;
    protected $base_url;    

    public function __construct($base_url, $api_key)
    {
        $this->base_url = $base_url;
        $this->api_key  = $api_key;
    }

    protected function _request($endpoint, $method = 'GET', $data = null)
    {        
        $debug = false;

        $ch = curl_init ("{$this->base_url}/{$endpoint}");        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->api_key}:X");   
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        # TODO: post data to json endpoint
        # curl_setopt($ch, );
        
        # TODO: enable CI
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        # log_message('debug', var_dump($info));
        if ($debug) echo 'debug, ' . print_r($info, TRUE);
        # log_message('debug', var_dump($data));
        if ($debug) echo 'debug, ' . print_r($data, TRUE);
        if (curl_errno($ch) and $error = curl_error($ch)) 
        {
            # log_message('error', var_dump($error));
            echo 'error, ' . print_r($error, TRUE);
            curl_close($ch);
            return FALSE;
        }
        if (in_array($info['http_code'], array(404)) and $error = $data) 
        {
            # log_message('error', var_dump($error));
            echo 'error, ' . print_r($error, TRUE);
            curl_close($ch);
            return FALSE;   
        }
        $data = json_decode($data);
        curl_close($ch);

        return $data;
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
     * View Forums in a Category.
     *
     * Request URL: domain_URL/categories/{id}.json
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
        if (!$category_id) return $this->get_all();
        if (!$category = $this->_request("categories/{$category_id}.json")) return FALSE;
        return $category->forum_category;
    }

    /**
     * View all Categories.
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
        if (!$categories = $this->_request("categories.json")) return FALSE;

        $_categories = array();
        foreach ($categories as $category)
        {
            $_categories[] = $category->forum_category;
        }
        return $_categories;
    }
















    /**
     * Create a new Forum Category.
     * 
     * Request URL: domain_URL/categories.json
     * Request method: POST
     * 
     * Request:
     *     array = ('forum-category' => array(
     *         'name'        => 'Test',                # required
     *         'description' => 'New testing category' # optional
     *     ));
     * Response:
     *     obj(
     *         'created_at':  '2012-12-05T16:04:12+05:30',
     *         'description': 'New testing category',
     *         'id':          2,
     *         'name':        'Test',
     *         'position':    2,
     *         'updated_at':  '2012-12-05T16:04:12+05:30'
     *     );
     *
     * @link http://freshdesk.com/api/forums/forum-category#create-a-forum-category
     * 
     * @param  string $name        Forum Category Name
     * @param  string $description Forum Category Description
     * @return array               API Response
     */
    // protected function create($name, $description = '')
    // {
    //     $data = array(
    //         'name' => $name,
    //         'description' => $description
    //     );        
    //     return $this->_request("categories.json", 'POST', $data);
    // }

//     protected function update($category_id, $name, $description = '')
//     {
//         $data = array(
//             'name' => $name,
//             'description' => $description,
//         );
//         return $this->_request("categories/{$category_id}.json", 'POST', $data);
//     }

//     protected function delete($category_id)
//     {
//         return $this->_request("categories/{$category_id}.json", 'DELETE');
//     }
}

/**
 * FreshDesk Library
 */
class FreshDesk
{
    private $CI;
    public $Category;

    public function __construct($base_url, $api_key)
    {
        # TODO: enable CI
        # $this->CI =& get_instance();

        $this->base_url = $base_url;
        $this->api_key  = $api_key;      

        $this->Category = new FreshDeskForumCategory($this->base_url, $this->api_key);
    }
}

# TODO: temporary
list($base_url, $api_key) = array_map('trim', explode(':', file_get_contents('config.txt')));

$freshdesk = new FreshDesk($base_url, $api_key);

$categories = $freshdesk->Category->get_all();

foreach ($categories as $category) {
    $summary = $category;
    $details = $freshdesk->Category->get($category->id);
    var_dump($summary, $details);
}
?>

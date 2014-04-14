<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * All documentation Copyright © Freshdesk Inc. (http://freshdesk.com/api)
 */

/**
 * Freshdesk Library
 */
class Freshdesk
{}

/**
 * Freshdesk API Transport
 *
 * Performs HTTP calls to the Freshdesk web service.
 */
class FreshdeskTransport
{
    /**
     * Perform an API request.
     *
     * @param  string $resource Freshdesk API resource
     * @param  string $method   HTTP request method
     * @param  array  $data     HTTP PUT/POST data
     * @return mixed            JSON object or HTTP response code
     */
    protected function _request($resource, $method = 'GET', $data = NULL)
    {}
}

/**
 * Freshdesk Base API
 *
 * Provides common create, get, update, and delete methods.
 */
class FreshdeskAPI extends FreshdeskTransport
{
    /**
     * Create a resource
     * @param  string $endpoint  API Endpoint
     * @param  array  $data      Array of resource data
     * @return mixed             JSON object or FALSE
     */
    public function create($endpoint, $data)
    {}

    /**
     * Retrieve a resource
     *
     * @param  string $endpoint  API Endpoint
     * @return mixed             JSON object or FALSE
     */
    public function get($endpoint)
    {}

    /**
     * Retrieve all resources
     *
     * @param  string $endpoint  API Endpoint
     * @return mixed             JSON object or FALSE
     */
    public function get_all($endpoint)
    {}

    /**
     * Update a resource
     *
     * @param  string $endpoint  API Endpoint
     * @param  array  $data      Array of resource data
     * @return mixed             JSON object or FALSE
     */
    public function update($endpoint, $data)
    {}

    /**
     * Delete a resource
     *
     * @param  string $endpoint  API Endpoint
     * @return boolean           TRUE or FALSE
     */
    public function delete($endpoint)
    {}
}

/**
 * Freshdesk Agent API
 */
class FreshdeskAgent extends FreshdeskAPI
{
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
    {}

    public function get_all()
    {}

    public function update($agent_id, $data)
    {}

    public function delete($agent_id)
    {}
}

/**
 * Freshdesk User API
 *
 * Create, View, Update, and Delete Users.
 *
 * @link http://freshdesk.com/api/#user
 */
class FreshdeskUser extends FreshdeskAPI
{
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
     * @param  array $data  Array of User data
     * @return object       JSON User object
     */
    public function create($data)
    {}

    /**
     * Retrieve a User
     *
     * Request URL: /contacts/[user_id].json
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
    {}

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
     * @param  string $query Filter query
     * @return array         Array of JSON User objects
     */
    public function get_all($state = '', $query = '')
    {}

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
     * @param  integer $user_id  User ID
     * @param  array   $data     User data
     * @return integer           HTTP response code
     */
    public function update($user_id, $data)
    {}

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
     * @param  integer $user_id  User ID
     * @return integer           HTTP response code
     */
    public function delete($user_id)
    {}
}

/**
 * Freshdesk Forum Category API
 *
 * Create, View, Update, and Delete Forum Categories.
 *
 * @link http://freshdesk.com/api/#forum-category
 */
class FreshdeskForumCategory extends FreshdeskAPI
{
    public $Forum;

    public static $SCHEMA = array(
        'id'          => 'numeric',  // Unique id of the forum category Read-Only
        'name'        => 'string',   // Name of the forum category Mandatory
        'description' => 'string',   // Description of the forum category
        'position'    => 'numeric'   // The rank of the category in the category listing
    );

    /**
     * Create a new Forum Category.
     *
     * Request URL:  /categories.json
     * Request method: POST
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST
     *      -d '{ "forum_category": { "name":"How to", "description":"Getting Started" }}' http://domain.freshdesk.com/categories.json
     *
     * Request:
     *    {"forum_category": {
     *         "name":"How to",
     *         "description":"Queries on How to ?"
     *      }}
     * Response:
     *     {"forum_category":{
     *         "created_at":"2014-01-08T06:38:11+05:30",
     *         "description":"Getting Started",
     *         "id":3,
     *         "name":"How to",
     *         "position":3,
     *         "updated_at":"2014-01-08T06:38:11+05:30"
     *      }}
     *
     *
     * @link http://freshdesk.com/api/#create_forum_category
     *
     * @param  JSON object $data Forum Category Data '{ "forum_category": { "name":"How to", "description":"Getting Started" }}'
     * @return JSON object              Forum Category JSON object
     */
    public function create($data)
    {}

    /**
     * View Forums in a Category.
     *
     * Request URL: /categories/[id].json
     * Request method: GET
     *
     * Response:
     *      {"forum_category":{
     *          "created_at":"2014-01-08T06:38:11+05:30",
     *          "description":"Recently Changed",
     *          "id":2,
     *          "name":"Latest Updates",
     *          "position":4,
     *          "updated_at":"2014-01-08T06:38:11+05:30"
     *      }}
     *
     *
     * @link   http://freshdesk.com/api/#view_forum_category
     *
     * @param  integer $category_id Forum Category ID
     * @return Object  JSON Forum Category Object
     */
    public function get($category_id = NULL)
    {}

    /**
     * View all Forum Categories.
     *
     * Request URL: categories.json
     * Request method: GET
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"
     *      -X GET http://domain.freshdesk.com/categories.json
     * Response:
     *      {"forum_category":{
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
    {}

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
     * @return bool    TRUE         Return TRUE if HTTP 200 OK
     */
    public function update($category_id, $data)
    {}

    /**
     * Delete an existing Forum Category.
     *
     * Request URL: categories/[id].json
     * Request method: DELETE
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"
     *      -X DELETE http://domain.freshdesk.com/categories/3.json
     * Response:
     *      {"forum_category":{
     *          "created_at":"2014-01-08T06:38:11+05:30",
     *          "description":"How to Queries",
     *          "id":3,
     *          "name":"How and What?",
     *          "position":null,
     *          "updated_at":"2014-01-08T07:13:56+05:30"
     *     }}
     *
     * @link   http://freshdesk.com/api/#delete_forum_category
     *
     * @param  integer  $category_id    Forum Category ID
     * @return bool     TRUE            Returns TRUE if HTTP Response: 200 OK
     */
    public function delete($category_id)
    {}
}

/**
 * Freshdesk Forum API
 *
 * Create, View, Update, and Delete Forums
 *
 * @link http://freshdesk.com/api/#forum
 */
class FreshdeskForum extends FreshdeskAPI
{
    public $ForumCategory;

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

    /**
     * Create a new Forum.
     *
     * Request URL: categories/[id]/forums.json
     * Request method: POST
     *
     * CURL:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST
     *     -d '{ "forum": { "description": "Ticket related functions", "forum_type":2, "forum_visibility":1, "name":"Ticket Operations" }}'
     *     http://domain.freshdesk.com/categories/1/forums.json
     *
     * Request:
     *        {"forum": {
     *            "description":"Ticket related functions",
     *            "forum_type":2,
     *            "forum_visibility":1,
     *            "name":"Ticket Operations"
     *        }}
     *
     * Response:
     *       {"forum":{
     *           "description":"Ticket related functions",
     *           "description_html":"\u003Cp\u003ETicket related functions\u003C/p\u003E",
     *           "forum_category_id":1,
     *           "forum_type":2,
     *           "forum_visibility":1,
     *           "id":2,
     *           "name":"Ticket Operations",
     *           "position":5,
     *           "posts_count":0,
     *           "topics_count":0
     *       }}
     *
     * @link http://freshdesk.com/api/#create_forum
     *
     *
     * @param  string $name        Forum Name
     * @param  object $data        Forum JSON object
     * @return object              Forum JSON object
     */
    public function create($category_id, $data)
    {}

    /**
     * View Forum
     *
     * Request URL: categories/[id]/forums/[id].json
     * Request method: GET
     *
     * Response:
     *    {"forum":{
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
     * @link   http://freshdesk.com/api/#view_forum
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @return mixed                Array or single Forum Object
     */
    public function get($category_id, $forum_id = NULL)
    {}

    /**
     * View Forums in a Category.
     *
     * Request URL: domain_URL/categories/[category_id].json
     * Request method: GET
     *
     * Response:
     *     #TODO
     *
     * @link   #TODO
     *
     * @param  integer $category_id Forum Category ID
     * @return mixed                Array or single Forum Category Object
     */
    public function get_all($category_id)
    {}

    /**
     * Update an existing Forum.
     *
     * Request URL: categories/[id]/forums/[id].json
     * Request method: PUT
     *
     * Request:
     *     {"forum": {
     *         "forum_type":2,
     *         "description":"Tickets and Ticket fields related queries",
     *         "forum_visibility":1
     *      }}
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
     * @param  object  $data        Forum JSON Object
     * @return integer              HTTP response code
     */
    public function update($category_id, $forum_id, $data)
    {}

    /**
     * Delete an existing Forum.
     *
     * Request URL: categories/[id]/forums/[id].json
     * Request method: DELETE
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE
     *      http://domain.freshdesk.com/categories/1/forums/2.json
     * Response:
     *      HTTP Status: 200 OK
     * @link   http://freshdesk.com/api/#delete_forum
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @return integer              HTTP response code
     */
    public function delete($category_id, $forum_id)
    {}
}

/**
 * Freshdesk Forum Topic
 *
 * Create, View, Update, and Delete Forum Topics
 * @link http://freshdesk.com/api/#topic
 */
class FreshdeskTopic extends FreshdeskTransport
{
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

    /**
     * Create a new Forum Topic
     *
     * Request URL: /categories/[id]/forums/[id]/topics.json
     * Request method: POST
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST
     *      -d '{ "topic": { "sticky":0, "locked":0, "title":"how to create a custom field", "body_html":"Can someone give me the steps ..." }}'
     *       http://domain.freshdesk.com/categories/1/forums/1/topics.json
     *
     * Request:
     *    {"topic": {
     *        "sticky":0,
     *        "locked":0,
     *        "title":"how to create a custom field",
     *        "body_html":"Can someone give me the steps..."
     *    }}
     *
     * Response:
     *    {"topic":{
     *        "account_id":1,
     *        "created_at":"2014-01-08T08:54:01+05:30",
     *        "delta":true,
     *        "forum_id":5,
     *        "hits":0,
     *        "id":3,
     *        "import_id":null,
     *        "last_post_id":null,
     *        "locked":false,
     *        "posts_count":0,
     *        "replied_at":"2014-01-08T08:54:01+05:30",
     *        "replied_by":null,
     *        "stamp_type":null,
     *        "sticky":0,
     *        "title":"how to create a custom field",
     *        "updated_at":"2014-01-08T08:54:01+05:30",
     *        "user_id":1,
     *        "user_votes":0
     *    }}
     *
     * @link http://freshdesk.com/api/#create_topic
     *
     * @todo   Determine avilable type/visibility options.
     * @todo   Determine commonly default type/visibility option.
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  object   $data        Forum Topic JSON object
     * @return object                Forum Topic JSON object
     */
    public function create($category_id = '', $forum_id = '', $data = '')
    {}

    /**
     * View all conversations in a forum Topic
     *
     * Request URL: domain_URL/categories/[id]/forums/[id]/topics/[id].json
     * Request method: GET
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"
     *      -X GET http://domain.freshdesk.com/categories/1/forums/1/topics/3.json
     *
     * Response:
     *      {
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
     * @param  integer  $topic_id    Forum Topic ID
     * @param  object   $data        Forum Topic JSON object
     * @return object                Forum Topic JSON Object
     */
    public function get($category_id = '', $forum_id = '', $topic_id = '')
    {}

    public function get_all()
    {}

    /**
     * Update an existing forum Topic
     *
     * Request URL: domain_URL/categories/[id]/forums/[id]/topics/[id].json
     * Request method: PUT
     *
     * CURL:
     *    curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT
     *    -d '{ "topic": { "sticky":0, "locked":0, "title":"How to create a new ticket field", "body_html":"Steps: Go to Admin tab ..." }}'
     *    http://domain.freshdesk.com/categories/1/forums/1/topics/3.json
     * Request:
     *    {"topic":{
     *        "sticky":0,
     *        "locked":0,
     *        "title":"How to create a new ticket field",
     *        "body_html": "Steps: Go to Admin tab ..."
     *      }}
     * Response:
     *    HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#update_topic
     *
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
     * @param  object   $data        Forum Topic JSON object
     * @return integer               HTTP Status: 200 OK
     */
    public function update($category_id = '', $forum_id = '', $topic_id ='',  $data = '')
    {}

    /**
     * Delete Topic
     *
     * Request URL: domain_URL/categories/[id]/forums/[id]/topics/[id].json
     * Request method: DELETE
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"
     *      -X DELETE http://domain.freshdesk.com/categories/1/forums/1/topics/1.json
     * Response:
     *      TRUE if HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#delete_topic
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
     * @return bool     TRUE         Return TRUE if HTTP Status: 200 OK
     */
    public function delete($category_id = '', $forum_id = '', $topic_id = '')
   {}
}

/**
 * Freshdesk Forum Post
 *
 * Create, View, Update, and Delete Forum Posts
 *
 * Data:
 *     {'post': {
 *          'id'          (number)    Unique ID of the post or comment      // Read-Only
 *          'body'        (string)    Content of the post in plaintext
 *          'body_html'   (string)    Content of the post in HTML.          // Mandatory
 *                                    (You can pass either body or body_html)
 *          'forum_id'    (number)    ID of the forum where the comment was posted
 *          'topic_id'    (number)    ID of the topic where the comment was posted
 *          'user_id'     (number)    ID of the user who posted the comment
 *     }}
 *
 * @link http://freshdesk.com/api/#post
 */
class FreshdeskPost extends FreshdeskTransport
{
    /**
     * Create a new Forum Post
     *
     * Request URL: /posts.json
     * Request method: POST
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST
     *      -d '{ "post": { "body_html":"What type of ticket field you are creating" }}'
     *      http://domain.freshdesk.com/posts.json?forum_id=1&category_id=1&topic_id=2
     * Request:
     *      {"post": {
     *          "body_html":"What type of ticket field you are creating"
     *       }
     * Response:
     *      {"post": {
     *          "answer": false,
     *          "body": "What type of ticket field you are creating",
     *          "body_html": "What type of ticket field you are creating",
     *          "created_at": "2014-02-07T12:32:34+05:30",
     *          "forum_id": 1,
     *          "id": 12,
     *          "topic_id": 2,
     *          "updated_at": "2014-02-07T12:32:34+05:30",
     *          "user_id": 1
     *      }}
     *
     * @link http://freshdesk.com/api/#create_post
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
     * @param  object   $data        Forum POST JSON object
     * @return object                Forum POST JSON object
     */
    public function create($category_id = '', $forum_id = '', $topic_id = '', $data)
    {}

    /**
     * Update an existing post
     *
     * Request URL: /posts/[id].json
     * Request method: PUT
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT
     *      -d '{ "post": { "body_html": "Ticket field have different types ..." }}'
     *      http:/2domain.freshdesk.com/posts/1.json?forum_id=1&category_id=1&topic_id=2
     * Request:
     *       {"post": {
     *           "body_html":"What type of ticket field you are creating"
     *       }}
     * Response:
     *      TRUE if HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#update_post
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
     * @param  object   $data        Forum POST JSON object
     * @return object                Forum POST JSON object
     */
    public function update($category_id = '', $forum_id = '', $topic_id = '', $data)
    {}

    public function get()
    {}

    public function get_all()
    {}

    /**
     * Update an existing post
     *
     * Request URL: /posts/[id].json
     * Request method: PUT
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X PUT
     *      -d '{ "post": { "body_html": "Ticket field have different types ..." }}'
     *      http:/2domain.freshdesk.com/posts/1.json?forum_id=1&category_id=1&topic_id=2
     * Request:
     *       {"post": {
     *           "body_html":"What type of ticket field you are creating"
     *       }}
     * Response:
     *      TRUE if HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#update_post
     *
     * @param  integer  $category_id Forum Category ID
     * @param  integer  $forum_id    Forum ID
     * @param  integer  $topic_id    Forum Topic ID
     * @param  object   $data        Forum POST JSON object
     * @return object                Forum POST JSON object
     */
    public function update($category_id = '', $forum_id = '', $topic_id = '', $data)
    {}

    /**
     * Delete an existing post
     *
     * Request URL: /posts/[id].json
     * Request method: DELETE
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE
     *      http://domain.freshdesk.com/posts/1.json?forum_id=1&category_id=1&topic_id=1
     * Response:
     *      TRUE if HTTP Status: 200 OK
     *
     * @link http://freshdesk.com/api/#delete_post
     *
     * @param    integer    $category_id    Forum Category ID
     * @param    integer    $forum_id       Forum ID
     * @param    integer    $topic_id       Forum Topic ID
     * @return   bool                       TRUE if HTTP Status: 200 OK
     */
    public function delete($category_id = '', $forum_id = '', $topic_id='', $post_id = '')
    {}
}

/**
 * Freshdesk Monitor
 *
 * Monitor, Un-Monitor, Check Monitoring Status, and get User Monitored Topics
 *
 * @link http://freshdesk.com/api/#monitor
 */
class FreshDeskMonitor extends FreshdeskTransport
{
    /**
     * Get a user's Monitored Topics
     *
     * Request URL: /support/discussions/user_monitored?user_id=[id]
     * Request method: GET
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X GET
     *     "http://domain.freshdesk.com/support/discussions/user_monitored?user_id=1218912"
     *
     * Response:
     *    {"topic": {
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
    {}

    /**
     * Monitoring Status
     *
     * Request URL: /support/discussions/topics/[id]/check_monitor.json?user_id=[id]
     * Request method: GET
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X GET
     *     "http://domain.freshdesk.com/support/discussions/user_monitored?user_id=1218912"
     *
     * Response:
     *  {"monitorship": {
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
    {}

    /**
     * Monitor Topic
     *
     * Request URL: /categories/[id]/forums/[id]/topics/[id]/monitorship.json
     * Request method: POST
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X POST
     *     "http://domain.freshdesk.com/categories/1/forums/2/topics/3/monitorship.json"
     *
     * Response:
     *    HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#monitor_topic
     *
     * @param  string $category_id Freshdesk Category ID
     * @param  string $forum_id Freshdesk Forum ID
     * @param  string $topic_id Freshdesk Topic ID
     * @return TRUE if HTTP Status: 200 OK
     */
    public function monitor($category_id = '', $forum_id = '', $topic_id = '')
    {}

    /**
     * Un-Monitor Topic
     *
     * Request URL: /categories/[id]/forums/[id]/topics/[id]/monitorship.json
     * Request method: DELETE
     *
     * Curl:
     *     curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE
     *     "http://domain.freshdesk.com/discussions/topic/1/subscriptions/unfollow.json"
     *
     * Response:
     *    HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#unmonitor_topic
     *
     * @param  string $category_id Freshdesk Category ID
     * @param  string $forum_id Freshdesk Forum ID
     * @param  string $topic_id Freshdesk Topic ID
     * @return TRUE if HTTP Status: 200 OK
     */
    public function unmonitor($category_id = '', $forum_id = '', $topic_id = '')
    {}
}

/**
 * Freshdesk Tickets
 *
 * Create, View, Update, and Delete Helpdesk Tickets
 *
 *
 *
 * @link http://freshdesk.com/api/#ticket
 */
class FreshdeskTicket extends FreshdeskTransport
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

    /**
     * View a Ticket
     *
     * Request URL: /helpdesk/tickets/[id].json
     * Request method: GET
     *
     * Curl:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"  -X GET http://domain.freshdesk.com/helpdesk/tickets/1.json
     *
     * Response:
     *   {"helpdesk_ticket":{
     *         "cc_email":{
     *             "cc_emails":[
     *                 "superman@marvel.com",
     *                 "avengers@marvel.com"
     *             ],
     *             "fwd_emails":[]
     *         },
     *         "created_at":"2014-01-07T14:57:55+05:30",
     *         "deleted":false,
     *         "delta":true,
     *         "description":"Details on the issue ...",
     *         "description_html":"\u003Cdiv\u003EDetails on the issue ...\u003C/div\u003E",
     *         "display_id":138,
     *         "due_by":"2014-01-10T14:57:55+05:30",
     *         "email_config_id":null,
     *         "frDueBy":"2014-01-08T14:57:55+05:30",
     *         "fr_escalated":false,
     *         "group_id":null,
     *         "id":138,
     *         "isescalated":false,
     *         "notes":[],
     *         "owner_id":null,
     *         "priority":1,
     *         "requester_id":17,
     *         "responder_id":null,
     *         "source":2,
     *         "spam":false,
     *         "status":2,
     *         "subject":"Support Needed...",
     *         "ticket_type":"Problem",
     *         "to_email":null,
     *         "trained":false,
     *         "updated_at":"2014-01-07T15:53:21+05:30",
     *         "urgent":false,
     *         "status_name":"Open",
     *         "requester_status_name":"Being Processed",
     *         "priority_name":"Low",
     *         "source_name":"Portal",
     *         "requester_name":"Test",
     *         "responder_name":"No Agent",
     *         "to_emails":null,
     *         "custom_field":{
     *            "weapon_1":"Laser Gun"
     *         },
     *         "attachments":[]
     *       }
     *   }
     *
     * @link   http://freshdesk.com/api/#view_a_ticket
     *
     * @param  string $ticket_id Freshdesk Helpdesk Ticket string
     * @return object JSON Ticket object
     */
    public function get($ticket_id)
    {}

    /**
     * View all Tickets
     *
     * Request URL: /helpdesk/tickets/[id].json
     * Request method: GET
     *
     * Curl:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json"  -X GET http://domain.freshdesk.com/helpdesk/tickets/1.json
     *
     * Response:
     *   {"helpdesk_ticket":{
     *         "cc_email":{
     *             "cc_emails":[
     *                 "superman@marvel.com",
     *                 "avengers@marvel.com"
     *             ],
     *             "fwd_emails":[]
     *         },
     *         "created_at":"2014-01-07T14:57:55+05:30",
     *         "deleted":false,
     *         "delta":true,
     *         "description":"Details on the issue ...",
     *         "description_html":"\u003Cdiv\u003EDetails on the issue ...\u003C/div\u003E",
     *         "display_id":138,
     *         "due_by":"2014-01-10T14:57:55+05:30",
     *         "email_config_id":null,
     *         "frDueBy":"2014-01-08T14:57:55+05:30",
     *         "fr_escalated":false,
     *         "group_id":null,
     *         "id":138,
     *         "isescalated":false,
     *         "notes":[],
     *         "owner_id":null,
     *         "priority":1,
     *         "requester_id":17,
     *         "responder_id":null,
     *         "source":2,
     *         "spam":false,
     *         "status":2,
     *         "subject":"Support Needed...",
     *         "ticket_type":"Problem",
     *         "to_email":null,
     *         "trained":false,
     *         "updated_at":"2014-01-07T15:53:21+05:30",
     *         "urgent":false,
     *         "status_name":"Open",
     *         "requester_status_name":"Being Processed",
     *         "priority_name":"Low",
     *           "source_name":"Portal",
     *         "requester_name":"Test",
     *         "responder_name":"No Agent",
     *         "to_emails":null,
     *         "custom_field":{
     *            "weapon_1":"Laser Gun"
     *         },
     *         "attachments":[]
     *       }
     *   }
     *
     * @link   http://freshdesk.com/api/#view_all_ticket
     *
     * @param  string $ticket_id Freshdesk Helpdesk Ticket string
     * @return object JSON Ticket object
     */
    public function get_all($ticket_id = '', $filter = '', $data = '')
    {}

    public function update() {}
    public function pick() {}
    public function delete() {}
    public function restore() {}
    public function assign() {}
    public function get_all_ticket_fields() {}
    public function add_note() {}
}

/**
 * Wrapped Freshdesk Class
 *
 * Allows `id` and `args` to be passed at instantiation.
 *
 * Returns an object that can be used similar to a Model.
 */
class FreshdeskWrapper extends FreshdeskTransport
{}

/**
 * Wrapped Freshdesk Classes
 */
class FreshdeskAgentWrapper extends FreshdeskWrapper {}
class FreshdeskUserWrapper extends FreshdeskWrapper {}
class FreshdeskForumCategoryWrapper extends FreshdeskWrapper {}
class FreshdeskForumWrapper extends FreshdeskWrapper {}
class FreshdeskTopicWrapper extends FreshdeskWrapper {}
class FreshdeskPostWrapper extends FreshdeskWrapper {}
class FreshdeskMonitorWrapper extends FreshdeskWrapper {}

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

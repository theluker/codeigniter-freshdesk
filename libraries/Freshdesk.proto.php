<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * All documentation Copyright © Freshdesk Inc. (http://freshdesk.com/api)
 */

class FreshdeskForum extends FreshdeskAPI
{
    /**
     * Delete a Forum
     *
     * Request URL: categories/[category_id]/forums/[forum_id].json
     * Request method: DELETE
     *
     * CURL:
     *      curl -u user@yourcompany.com:test -H "Content-Type: application/json" -X DELETE \
     *          http://domain.freshdesk.com/categories/1/forums/2.json
     *
     * Response:
     *      HTTP Status: 200 OK
     *
     * @link   http://freshdesk.com/api/#delete_forum
     *
     * @param  integer $category_id Forum Category ID
     * @param  integer $forum_id    Forum ID
     * @return boolean              TRUE if HTTP 200 else FALSE
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
     * Request URL: /categories/[category_id]/forums/[forum_id]/topics.json
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
     * Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id].json
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
     * Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id].json
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
     * Request URL: domain_URL/categories/[category_id]/forums/[forum_id]/topics/[topic_id].json
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
     * Request URL: /posts/[post_id].json
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
     * Request URL: /posts/[post_id].json
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
     * Request URL: /posts/[post_id].json
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
     * Request URL: /support/discussions/user_monitored?user_id=[post_id]
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
     * Request URL: /support/discussions/topics/[topic_id]/check_monitor.json?user_id=[user_id]
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
     * Request URL: /categories/[category_id]/forums/[forum_id]/topics/[topic_id]/monitorship.json
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
     * Request URL: /categories/[category_id]/forums/[forum_id]/topics/[topic_id]/monitorship.json
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

/* End of file Freshdesk.php */
/* Location: ./application/libraries/Freshdesk.php */

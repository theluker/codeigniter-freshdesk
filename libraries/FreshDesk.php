<?php

/**
 * FreshDesk API
 */
class FreshDeskAPI
{
    protected function _request($url, $method = 'GET', $data = null)
    {
        
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
     * Create a new Forum Category.
     * 
     * Request URL: domain_URL/categories.json
     * Request Method: POST
     * 
     * Request:
     *     array = ('forum-category' => array(
     *         'name'        => 'Test',                # required
     *         'description' => 'New testing category' # optional
     *     ));
     * Response:
     *     array = ('forum-category' => array(
     *         'created-at'  => '2012-12-05T16:04:12+05:30',
     *         'description' => 'New testing category',
     *         'id'          => 2,
     *         'name'        => 'Test',
     *         'position'    => 2,
     *         'updated-at'  => '2012-12-05T16:04:12+05:30'
     *     ));
     *
     * @link http://freshdesk.com/api/forums/forum-category#create-a-forum-category
     * 
     * @param  string $name        Forum Category Name
     * @param  string $description Forum Category Description
     * @return array               API Response
     */
    protected function create($name, $description = '')
    {
        $data = array(
            'name' => $name,
            'description' => $description
        );        
        return $this->_request("categories.json", 'POST', $data);
    }

    protected function get($category_id = null)
    {
        if (intval($category_id)) $category_id = "/{$category_id}";
        return $this->_request("categories{$category_id}.json")
    }

    protected function update($category_id, $name, $description = '')
    {
        $data = array(
            'name' => $name,
            'description' => $description,
        );
        return $this->_request("categories/{$category_id}.json", 'POST', $data);
    }

    protected function delete($category_id)
    {
        return $this->_request("categories/{$category_id}.json", 'DELETE');
    }
}

/**
 * FreshDesk Forum
 */
class FreshDeskForum extends FreshDeskAPI
{
    public function __construct()
    {
        $this->Category = new FreshDeskForumCategory();
    }

    protected function 
    protected function topics($category_id, $forum_id)

    protected function create();
    protected function update();
    protected function delete();
}

/**
 * FreshDesk Library
 */
class FreshDesk extends FreshDeskAPI
{

    private $CI;
    protected $Forum

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->Forum = new FreshDeskForum();
    }
}

?>

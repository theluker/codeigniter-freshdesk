<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UnitTest extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['freshdesk', 'unit_test']);

        $this->user_id = 1001694391;
    }


    /* BEGIN Helper Methods */
    private function __test_schema($name, $class, $result)
    {
        $class = get_class($class);
        $schema = get_class_vars($class)['SCHEMA'];
        foreach ($schema as $property => $type)
        {
            @$value = $result->{$property};

            foreach (get_object_vars($this->freshdesk) as $_class)
            {
                if (get_class($_class) == $type)
                {
                    $this->{"_test_{$property}"}($name, $value);
                    break 2;
                }
            }

            $exists = property_exists($result, $property);
            $this->unit->run($exists, TRUE, $name, "isset({$class}::\${$property})");
            if ($exists and $value)
            {
                $this->unit->run($value, "is_{$type}", $name, "{$class}::\${$property} == {$type}");
            }
        }

        foreach (array_keys(get_object_vars($result)) as $property)
        {
            if ( ! in_array($property, array_keys($schema)))
            {
                $this->unit->run(FALSE, TRUE, $name, "!exists({$class}::{$property})");
            }
        }
    }
    /* END Helper Methods */


    /* BEGIN Main Controllers */
    public function index()
    {
        $this->test_all(TRUE, FALSE);
        echo $this->unit->report();
    }
    public function test_all($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_agent(TRUE, FALSE);
        $this->test_user(TRUE, FALSE);
        $this->test_category(TRUE, FALSE);
        $this->test_forum(TRUE, FALSE);
        $this->test_topic(TRUE, FALSE);
        $this->test_post(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_create($sqelch = FALSE, $recurse = TRUE) {
        $this->test_agent_create(TRUE, FALSE);
        $this->test_user_create(TRUE, FALSE);
        $this->test_category_create(TRUE, FALSE);
        $this->test_forum_create(TRUE, FALSE);
        $this->test_topic_create(TRUE, FALSE);
        $this->test_post_create(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_get($sqelch = FALSE, $recurse = TRUE) {
        $this->test_agent_get(TRUE, FALSE);
        $this->test_user_get(TRUE, FALSE);
        $this->tecategoryser_get(TRUE, FALSE);
        $this->test_forum_get(TRUE, FALSE);
        $this->test_topic_get(TRUE, FALSE);
        $this->test_post_get(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_get_all($sqelch = FALSE, $recurse = TRUE) {
        $this->test_agent_get_all(TRUE, FALSE);
        $this->test_user_get_all(TRUE, FALSE);
        $this->test_ucategoryget_all(TRUE, FALSE);
        $this->test_forum_get_all(TRUE, FALSE);
        $this->test_topic_get_all(TRUE, FALSE);
        $this->test_post_get_all(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_update($sqelch = FALSE, $recurse = TRUE) {
        $this->test_agent_update(TRUE, FALSE);
        $this->test_user_update(TRUE, FALSE);
        $this->test_category_update(TRUE, FALSE);
        $this->test_forum_update(TRUE, FALSE);
        $this->test_topic_update(TRUE, FALSE);
        $this->test_post_update(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_delete($sqelch = FALSE, $recurse = TRUE) {
        $this->test_agent_delete(TRUE, FALSE);
        $this->test_user_delete(TRUE, FALSE);
        $this->test_category_delete(TRUE, FALSE);
        $this->test_forum_delete(TRUE, FALSE);
        $this->test_topic_delete(TRUE, FALSE);
        $this->test_post_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_misc($sqelch = FALSE, $recurse = TRUE) {
        $this->test_agent_misc(TRUE, FALSE);
        $this->test_user_misc(TRUE, FALSE);
        $this->tescategoryer_misc(TRUE, FALSE);
        $this->test_forum_misc(TRUE, FALSE);
        $this->test_topic_misc(TRUE, FALSE);
        $this->test_post_misc(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    /* END Main Controllers */


    /* BEGIN Agent Tests */
    private function _test_agent($trace, $result)
    {
        $this->__test_schema("TestAgent({$trace})", $this->freshdesk->Agent, $result);
    }
    public function test_agent($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_agent_create(TRUE, FALSE);
        $this->test_agent_get(TRUE, FALSE);
        $this->test_agent_get_all(TRUE, FALSE);
        $this->test_agent_update(TRUE, FALSE);
        $this->test_agent_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_agent_create($sqelch = FALSE, $resurse = TRUE)
    {
        $name = "Agent::create()";

        $test = "Agent->create()";
        $data = array();
        $result = $this->freshdesk->Agent->create($data);
        $this->unit->run($result, FALSE, $name, $test);
    }
    public function test_agent_get($sqelch = FALSE, $resurse = TRUE)
    {
        $name = "Agent::get()";
        $test = "\$this->freshdesk->Agent->get()";
        $result = $this->freshdesk->Agent->get();
        $this->unit->run($result, 'is_array', $name, $test);
        if ($resurse and $agent = @$result[0]) $this->_test_agent($test, $agent);
        if ($agent_id = @$agent->id)
        {
            $test = "\$this->freshdesk->Agent->get(\$agent_id)";
            $agent = $this->freshdesk->Agent->get($agent_id);
            $this->unit->run($agent, 'is_object', $name, $test);
            if ($resurse and $agent = $agent->agent) $this->_test_agent($test, $agent);
        }
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_agent_get_all($sqelch = FALSE, $recurse = TRUE)
    {
        $name = "Agent::get_all()";
        $test = "Agent->get_all()";
        $result = $this->freshdesk->Agent->get_all();
        $this->unit->run($result, 'is_array', $name, $test);
        if ($recurse and $agent = @$result[0]) $this->_test_agent($test, $agent);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_agent_update($sqelch = FALSE, $resurse = TRUE)
    {
        $name = "Agent::update()";
        $test = "Agent->update()";
        $data = array();
        $result = $this->freshdesk->Agent->update(0, $data);
        $this->unit->run($result, FALSE, $name, $test);
    }
    public function test_agent_delete($sqelch = FALSE, $resurse = TRUE)
    {
        $name = "Agent::delete()";
        $test = "Agent->delete()";
        $result = $this->freshdesk->Agent->delete(0);
        $this->unit->run($result, FALSE, $name, $test);
    }
    public function test_agent_misc($sqelch = FALSE, $resurse = TRUE) {}
    /* END Agent Tests */


    /* BEGIN User Tests */
    public function _test_user($trace, $result)
    {
        $this->__test_schema("TestUser({$trace})", $this->freshdesk->User, $result);
    }
    public function test_user($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_user_create(TRUE, FALSE);
        $this->test_user_get(TRUE, FALSE);
        $this->test_user_get_all(TRUE, FALSE);
        $this->test_user_update(TRUE, FALSE);
        $this->test_user_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_user_create($sqelch = FALSE, $resurse = TRUE) {}
    public function test_user_get($sqelch = FALSE, $resurse = TRUE) {}
    public function test_user_get_all($sqelch = FALSE, $recurse = TRUE) {}
    public function test_user_update($sqelch = FALSE, $resurse = TRUE) {}
    public function test_user_delete($sqelch = FALSE, $resurse = TRUE) {}
    public function test_user_misc($sqelch = FALSE, $resurse = TRUE) {}
    /* END User Test */


    /* BEGIN ForumCategory Tests */
    public function _test_category($trace, $result)
    {
        $this->__test_schema("TestForumCategory({$trace})", $this->freshdesk->ForumCategory, $result);
    }
    public function test_category($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_category_create(TRUE, FALSE);
        $this->test_category_get(TRUE, FALSE);
        $this->test_category_get_all(TRUE, FALSE);
        $this->test_category_update(TRUE, FALSE);
        $this->test_category_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_category_create($sqelch = FALSE, $resurse = TRUE) {}
    public function test_category_get($sqelch = FALSE, $resurse = TRUE) {}
    public function test_category_get_all($sqelch = FALSE, $recurse = TRUE) {}
    public function test_category_update($sqelch = FALSE, $resurse = TRUE) {}
    public function test_category_delete($sqelch = FALSE, $resurse = TRUE) {}
    public function test_category_misc($sqelch = FALSE, $resurse = TRUE) {}
    /* END ForumCategory Test */


    /* BEGIN Forum Tests */
    public function _test_forum($trace, $result)
    {
        $this->__test_schema("TestForum({$trace})", $this->freshdesk->Forum, $result);
    }
    public function test_forum($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_forum_create(TRUE, FALSE);
        $this->test_forum_get(TRUE, FALSE);
        $this->test_forum_get_all(TRUE, FALSE);
        $this->test_forum_update(TRUE, FALSE);
        $this->test_forum_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_forum_create($sqelch = FALSE, $resurse = TRUE) {}
    public function test_forum_get($sqelch = FALSE, $resurse = TRUE) {}
    public function test_forum_get_all($sqelch = FALSE, $recurse = TRUE) {}
    public function test_forum_update($sqelch = FALSE, $resurse = TRUE) {}
    public function test_forum_delete($sqelch = FALSE, $resurse = TRUE) {}
    public function test_forum_misc($sqelch = FALSE, $resurse = TRUE) {}
    /* END Forum Test */


    /* BEGIN Forum Topic Tests */
    public function _test_topic($trace, $result)
    {
        $this->__test_schema("TestTopic({$trace})", $this->freshdesk->Topic, $result);
    }
    public function test_topic($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_topic_create(TRUE, FALSE);
        $this->test_topic_get(TRUE, FALSE);
        $this->test_topic_get_all(TRUE, FALSE);
        $this->test_topic_update(TRUE, FALSE);
        $this->test_topic_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_topic_create($sqelch = FALSE, $resurse = TRUE) {}
    public function test_topic_get($sqelch = FALSE, $resurse = TRUE) {}
    public function test_topic_get_all($sqelch = FALSE, $recurse = TRUE) {}
    public function test_topic_update($sqelch = FALSE, $resurse = TRUE) {}
    public function test_topic_delete($sqelch = FALSE, $resurse = TRUE) {}
    public function test_topic_misc($sqelch = FALSE, $resurse = TRUE) {}
    /* END Forum Topic Test */


    /* BEGIN Topic Post Tests */
    public function _test_post($trace, $result)
    {
        $this->__test_schema("TestPost({$trace})", $this->freshdesk->Post, $result);
    }
    public function test_post($sqelch = FALSE, $recurse = TRUE)
    {
        $this->test_post_create(TRUE, FALSE);
        $this->test_post_get(TRUE, FALSE);
        $this->test_post_get_all(TRUE, FALSE);
        $this->test_post_update(TRUE, FALSE);
        $this->test_post_delete(TRUE, FALSE);
        if ( ! $sqelch) echo $this->unit->report();
    }
    public function test_post_create($sqelch = FALSE, $resurse = TRUE) {}
    public function test_post_get($sqelch = FALSE, $resurse = TRUE) {}
    public function test_post_get_all($sqelch = FALSE, $recurse = TRUE) {}
    public function test_post_update($sqelch = FALSE, $resurse = TRUE) {}
    public function test_post_delete($sqelch = FALSE, $resurse = TRUE) {}
    public function test_post_misc($sqelch = FALSE, $resurse = TRUE) {}
    /* END Topic Post Test */
}

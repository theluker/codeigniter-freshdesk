<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UnitTest extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['freshdesk', 'unit_test']);

        $this->user_id = 1001694391;
    }

    private function __test_schema($name, $result, $class)
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
                    $this->{"_test_{$property}"}($value);
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

    public function index()
    {
        $this->test_all();
    }

    public function test_all()
    {
        $this->test_agent();
    }

    public function test_agent()
    {
        $this->_test_agent_get();
        $this->_test_agent_get_all();

        echo $this->unit->report();
    }

    private function _test_agent($result)
    {
        $this->__test_schema("TestAgent", $result, $this->freshdesk->Agent);
    }

    private function _test_agent_get()
    {
        $name = "Agent::get()";

        $test = "\$this->freshdesk->Agent->get()";
        $result = $this->freshdesk->Agent->get();
        $this->unit->run($result, 'is_array', $name, $test);

        if ($agent = @$result[0])
        {
            $this->_test_agent($agent);
        }

        $test = "\$this->freshdesk->Agent()->get()";
        $result = $this->freshdesk->Agent()->get();
        $this->unit->run($result, FALSE, $name, $test);

        if ($agent_id = @$agent->id)
        {
            $test = "\$this->freshdesk->Agent->get(\$agent_id)";
            $result = $this->freshdesk->Agent->get($agent_id);
            $this->unit->run($result, 'is_object', $name, $test);

            $test = "\$this->freshdesk->Agent(\$agent_id)->get()";
            $result = $this->freshdesk->Agent($agent_id)->get();
            $this->unit->run($result, 'is_object', $name, $test);

            $test = "\$this->freshdesk->Agent()->get(\$agent_id)";
            $result = $this->freshdesk->Agent()->get($agent_id);
            $this->unit->run($result, FALSE, $name, $test);
        }
    }

    private function _test_agent_get_all()
    {
        $name = "Agent::get_all()";

        $test = "Agent->get_all()";
        $result = $this->freshdesk->Agent->get_all();
        $this->unit->run($result, 'is_array', $name, $test);
    }

    public function _test_user($result)
    {
        $this->__test_schema("TestUser", $result, $this->freshdesk->User);
    }

    public function test_user()
    {
        echo $this->unit->report();
    }
}

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UnitTest extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['freshdesk', 'unit_test']);

        $this->agent_id = 1000045851;
        $this->user_id = 1001694391;
    }

    public function index()
    {
        $this->test_all();
    }

    private function test_all()
    {
        $this->test_agent();
    }

    private function test_agent()
    {
        $this->_test_agent_get();
        $this->_test_agent_get_all();

        echo $this->unit->report();
    }

    private function _test_agent_get()
    {
        $name = "Agent::get()";

        $test = "Agent->get()";
        $result = $this->freshdesk->Agent->get();
        $this->unit->run($result, 'is_array', $name, $test);

        $test = "Agent()->get()";
        $result = $this->freshdesk->Agent()->get();
        $this->unit->run($result, FALSE, $name, $test);

        $test = "Agent->get(\$agent_id)";
        $result = $this->freshdesk->Agent->get($this->agent_id);
        $this->unit->run($result, 'is_object', $name, $test);

        $test = "Agent(\$agent_id)->get()";
        $result = $this->freshdesk->Agent($this->agent_id)->get();
        $this->unit->run($result, 'is_object', $name, $test);

        $test = "Agent()->get(\$agent_id)";
        $result = $this->freshdesk->Agent()->get($this->agent_id);
        $this->unit->run($result, FALSE, $name, $test);
    }

    private function _test_agent_get_all()
    {
        $name = "Agent::get_all()";

        $test = "Agent->get_all()";
        $result = $this->freshdesk->Agent->get_all();
        $this->unit->run($result, 'is_array', $name, $test);
    }
}

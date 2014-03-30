# codeigniter-freshdesk

A Freshdesk Library for CodeIgniter.

### Using The Library

From within any of your [Controller][controller] functions you can initialize the library using the standard:
```php
$this->load->library('freshdesk');
```

[controller]:http://ellislab.com/codeigniter/user-guide/general/controllers.html

### Passing Parameters When Initializing The Library

In the library loading function you can dynamically pass data as an array via the second parameter and it will be passed to the library class constructor:
```php
$params = array(
    'base_url' => 'your_helpdesk_domain_name',

    'api_key' => 'your_helpdesk_api_key',
    // OR
    'username' => 'your_helpdesk_username',
    'password' => 'your_helpdesk_password'
);

$this->load->library('freshdesk', $params);
```

You can also pass parameters stored in a config file.

 Note that if you dynamically pass parameters as described above, the config file option will not be available.

Note that if you pass the `api_key` parameter, the `username` and `password` options will be ignored.

### Accessing the API

The library provides access to the following APIs:

`User`, `ForumCategory`, `Forum`, `Topic`, `Post`

Limited access is provided to the (currently undocumented) `Agent` API.

API methods can be accessed via a standardized scheme:
```php
$this->freshdesk->{$api}->{$method}();
```

Common methods provided are:
`create()`, `getAll()`, `get()`, `update()`, and `delete()`.

### Examples
Retrieve a list of Forums:
```php
$forums = $this->freshdesk->Forum->getAll();

foreach ($forums as $forum)
{
    $name = $forum->name;
    $posts = $forum->{'posts-count'};
    $topics = $forum->{'topics-count'};

    echo "Forum '{$name}' has {$posts} posts in {$topics} topics.";
}
```

### License
Freshdesk documentation Copyright &copy; Freshdesk Inc. (http://freshdesk.com/api)

CodeIgniter documentation Copyright &copy; EllisLab, Inc. (http://ellislab.com/codeigniter/user-guide)

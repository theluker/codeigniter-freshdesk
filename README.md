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
    'api_key' => 'your_helpdesk_api_key',
    'base_url' => 'your_helpdesk_domain_name'
);

# OR

$params = array(
    'username' => 'your_helpdesk_username',
    'password' => 'your_helpdesk_password',
    'base_url' => 'your_helpdesk_domain_name'
);

$this->load->library('freshdesk', $params);
```

You can also pass parameters stored in a config file. Note that if you dynamically pass parameters as described above, the config file option will not be available.

Note that if you pass the `api_key` parameter, the `username` and `password` options will be ignored.

### Accessing the API
 * The library provides access to the following APIs: `User`
 * Limited access is provided to the (currently undocumented) `Agent` API.

API methods can be accessed via a standardized scheme:
```php
$this->freshdesk->{$resource}->{$method}();
```
Common methods provided are: `create()`, `get_all()`, `get()`, `update()`, and `delete()`.

### Examples
The following examples demonstrate various methods of utilizing the library.

#### Users
```php
# Create a User
$data = array('name' => 'Name', 'email' => 'user@domain.com');
$user = $this->freshdesk->User->create($data);

# Update a User
$this->freshdesk->User->update(12345, ['name' => 'New Name']);

# Delete a User
$this->freshdesk->User->delete(12345);

# Retrieve a User
$user = $this->freshdesk->User->get(12345);

# Retrieve a list of Users
foreach ($this->freshdesk->User->get_all() as $user)
{
    $name = $user->name;
    $email = $user->email;
    $created = $user->created_at;

    echo "User '{$name}' ({$email}) was created {$created}.";
}

# Create a a Post
$this->freshdesk->Post->create($category_id, $forum_id, $topic_id, ['body' => 'My first post!']);
```

### License
 * The MIT License (MIT) (http://opensource.org/licenses/MIT)
 * Freshdesk documentation Copyright &copy; Freshdesk Inc. (http://freshdesk.com/api)
 * CodeIgniter documentation Copyright &copy; EllisLab, Inc. (http://ellislab.com/codeigniter/user-guide)

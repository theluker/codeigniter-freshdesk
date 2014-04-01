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

You can also pass parameters stored in a config file. Note that if you dynamically pass parameters as described above, the config file option will not be available.

Note that if you pass the `api_key` parameter, the `username` and `password` options will be ignored.

### Accessing the API

The library provides access to the following APIs: `User`
Limited access is provided to the (currently undocumented) `Agent` API.

API methods can be accessed via a standardized scheme:
```php
$this->freshdesk->{$resource}->{$method}();
```
Common methods provided are: `create()`, `getAll()`, `get()`, `update()`, and `delete()`.

### Examples
The following examples demonstrate various methods of utilizing the library.

#### Users
```php
# Create a User

$data = array('name' => 'Name', 'email' => 'user@domain.com');
$user = $this->freshdesk->User->create($data);      // method 1
$user = $this->freshdesk->User($data)->create();    // method 2

# Update a User

$user_id = 12345;
$data = array('name' => 'New Name');
$this->freshdesk->User->update($user_id, $data);    // method 1
$this->freshdesk->User($user_id)->update($data);    // method 2
$this->freshdesk->User($user_id, $data)->update();  // method 3

# Delete a User

$user_id = 12345;
$this->freshdesk->User->delete($user_id);           // method 1
$this->freshdesk->User($user_id)->delete();         // method 2

# Retrieve a User

$user_id = 12345;
$user = $this->freshdesk->User->get($user_id);      // method 1
$user = $this->freshdesk->User($user_id)->get();    // method 2

# Retrieve a list of Users

$users = $this->freshdesk->User->get();             // method 1
$users = $this->freshdesk->User->get_all();         // method 2

foreach ($users as $user)
{
    $name = $user->name;
    $email = $user->email;
    $created = $user->{'created-at'};

    echo "User '{$name}' ({$email}) was created {$created}.";
}
```

### License
Freshdesk documentation Copyright &copy; Freshdesk Inc. (http://freshdesk.com/api)
CodeIgniter documentation Copyright &copy; EllisLab, Inc. (http://ellislab.com/codeigniter/user-guide)

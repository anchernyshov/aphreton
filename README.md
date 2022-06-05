# aphreton
A minimalist PHP JSON API framework

### Request validation:
- Method: POST
- HTTP Content-type header: application/json
- HTTP Authorization header (for all endpoints except Auth.login): Bearer {token}
- Body: valid JSON, not empty, endpoint validation with JSON schema

### Basic usage
1. Add project database to 'databases' section of config.php
```
'databases' => [
    'main' => [
        'dsn' => 'sqlite:main.sqlite3',
        'user' => '',
        'password' => '',
    ]
]
```
2. Create data model class in directory /models representing database table row:
```
class ExampleModel extends \Aphreton\Model {
  public $field = null;
  ...
  public function __construct() {
      parent::__construct();
      $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('main');
      $this->source_name = 'EXAMPLE_TABLE';
  }
}
```
3. Create API route class in directory /routes and define endpoint (class method) with required user level and JSON schema for request validation:
```
class ExampleRoute extends \Aphreton\APIRoute {
  public function __construct($parent) {
    $this->setJSONSchemaForEndpoint(
      'example_endpoint', [
        'type' => 'object',
        'properties' => [
          'id' => [
            'type' => 'string'
          ]
        ],
        'required' => ['id']
      ]
    );
    $this->setRequiredUserLevelForEndpoint('example_endpoint', 1);
    ...
  }
  
  public function example_endpoint($params) {
    $example_model = \Aphreton\Models\Author::getOne(['_id' => $params->id]);
    ...
  }
}
```
4. Make POST request to 'auth' route 'login' endpoint to obtain JWT. By default user 'test' with password 'qwerty' will be created on database initialization.
5. Now you can make POST request to the ExampleRoute.example_endpoint

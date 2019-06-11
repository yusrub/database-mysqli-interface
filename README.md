# MySqli Interface

MySqli Interface is a php code which use to do basic CRUD operation.

## Installation

Use the composer to install database-mysqli-interface.

```bash
composer require yusrub/database-mysqli-interface
```

## Usage

```use Yusrub\DatabaseMysqliInterface\Database;

require 'vendor/autoload.php';

$database = new Database('host', 'dbname', 'username', 'password'); # connect to database

# select all records from table
$database->select('tableName');
# apply where condition while selecting records from table where condtion can be string/array
$database->select('tableName', 'id = 5');
#multiple condition
$database->select('tableName', array(id => 5, age => 23));
#limit the records
$database->select('tableName', 'id = 5', $limit=5);
#insert the records
$database->insert('table',array(field1=>value1, field2=>value2))
#update the records
$database->update('table',array(field1=>value1, field2=>value2), 'condition_field = 2')
#delete the records
$database->delete('table', 'condition_field = 2')
$database->delete('table', array('condition_field => [1,2]))


```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)

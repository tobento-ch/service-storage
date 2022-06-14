# Storage Service

The Storage Service comes with a query builder for storing and fetching items.

## Table of Contents

- [Getting started](#getting-started)
	- [Requirements](#requirements)
	- [Highlights](#highlights)
	- [Simple Example](#simple-example)
- [Documentation](#documentation)
    - [Storages](#storages)
        - [Pdo MySql Storage](#pdo-mysql-storage)
        - [Json File Storage](#json-file-storage)
        - [In Memory Storage](#in-memory-storage)
    - [Queries](#queries)
        - [Select Statements](#select-statements)
            - [Retrieving Methods](#retrieving-methods)
            - [Where Clauses](#where-clauses)
            - [JSON Where Clauses](#json-where-clauses)
            - [Join Clauses](#join-clauses)
            - [Group Clauses](#group-clauses)
            - [Select Columns](#select-columns)
            - [Index Column](#index-column)
            - [Ordering](#order)
            - [Limit](#limit)
        - [Insert Statements](#insert-statements)
        - [Update Statements](#update-statements)
        - [Delete Statements](#delete-statements)
        - [Transactions](#transactions)
        - [Debugging](#debugging)
    - [Item Interface](#item-interface)
    - [Items Interface](#items-interface)
    - [Result Interface](#result-interface)
    - [Tables](#tables)
- [Credits](#credits)
___

# Getting started

Add the latest version of the Storage service running this command.

```
composer require tobento/service-storage
```

## Requirements

- PHP 8.0 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design
- Query Builder
- PDO MySql Storage
- Json File Storage
- In Memory Storage

## Simple Example

Here is a simple example of how to use the Storage service.

```php
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\ItemInterface;

$tables = new Tables();
$tables->add('products', ['id', 'sku', 'price'], 'id');

$storage = new JsonFileStorage(
    dir: 'home/private/storage/',
    tables: $tables
);

$inserted = $storage
    ->table('products')
    ->insert([
        'id' => 1,
        'sku' => 'pencil',
        'price' => 1.29,
    ]);

$item = $storage->table('products')->find(1);

var_dump($item instanceof ItemInterface);
// bool(true)
```

# Documentation

## Storages

### Pdo MySql Storage

```php
use Tobento\Service\Database\PdoDatabaseFactory;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Storage\StorageInterface;
use PDO;

$pdo = (new PdoDatabaseFactory())->createPdo(
    name: 'mysql',
    config: [
        'dsn' => 'mysql:host=localhost;dbname=db_name',
        'username' => 'root',
        'password' => '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
);

$tables = new Tables();
$tables->add('products', ['id', 'sku', 'price'], 'id');
$tables->add('users', ['id', 'firstname', 'lastname', 'email'], 'id');

$storage = new PdoMySqlStorage($pdo, $tables);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

### Json File Storage

```php
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\StorageInterface;

$tables = new Tables();
$tables->add('products', ['id', 'sku', 'price'], 'id');
$tables->add('users', ['id', 'firstname', 'lastname', 'email'], 'id');

$storage = new JsonFileStorage(
    dir: 'home/private/storage/',
    tables: $tables
);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

### In Memory Storage

```php
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Storage\StorageInterface;

$tables = new Tables();
$tables->add('products', ['id', 'sku', 'price'], 'id');
$tables->add('products_lg', ['product_id', 'language_id', 'title']);
$tables->add('users', ['id', 'firstname', 'lastname', 'email'], 'id');

$storage = new InMemoryStorage([
    'products' => [
        1 => ['id' => 1, 'sku' => 'paper', 'price' => 1.2],
        2 => ['id' => 2, 'sku' => 'pen', 'price' => 1.56],
    ],
    'products_lg' => [
        ['product_id' => 1, 'language_id' => 1, 'title' => 'Papier'],
        ['product_id' => 1, 'language_id' => 2, 'title' => 'Paper'],
        ['product_id' => 2, 'language_id' => 1, 'title' => 'Stift'],
    ],    
    'users' => [
        1 => ['id' => 1, 'firstname' => 'Erika', 'lastname' => 'Mustermann', 'email' => 'erika.mustermann@example.com'],
        2 => ['id' => 2, 'firstname' => 'Mustermann', 'lastname' => 'Mustermann', 'email' => 'mustermann@example.com'],
    ],    
], $tables);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

## Queries

### Select Statements

#### Retrieving methods

**get**

Retrieve items.

```php
use Tobento\Service\Storage\ItemsInterface;

$products = $storage->table('products')->get();

var_dump($products instanceof ItemsInterface);
// bool(true)

$products->all();
/*Array
(
    [1] => Array
        (
            [id] => 1
            [sku] => paper
            [price] => 1.2
        )

    [2] => Array
        (
            [id] => 2
            [sku] => pen
            [price] => 1.56
        )

)*/
```

Check out [Items Interface](#items-interface) to learn more about it.

**column**

```php
use Tobento\Service\Storage\ItemInterface;

$column = $storage->table('products')->column('price');

var_dump($column instanceof ItemInterface);
// bool(true)

$column->all();
/*Array
(
    [0] => 1.2
    [1] => 1.56
)*/
```

Index by a certain column:

```php
$storage->table('products')->column('price', 'sku')->all();
/*Array
(
    [paper] => 1.2
    [pen] => 1.56
)*/
```

Check out [Item Interface](#item-interface) to learn more about it.

**first**

Returns the first found item or NULL.

```php
use Tobento\Service\Storage\ItemInterface;

$product = $storage->table('products')->first();

var_dump($product instanceof ItemInterface);
// bool(true)

$product->all();
/*Array
(
    [id] => 1
    [sku] => paper
    [price] => 1.2
)*/
```

Check out [Item Interface](#item-interface) to learn more about it.

**find**

Returns a single item by id or NULL.

```php
use Tobento\Service\Storage\ItemInterface;

$product = $storage->table('products')->find(2);

var_dump($product instanceof ItemInterface);
// bool(true)

$product->all();
/*Array
(
    [id] => 2
    [sku] => pen
    [price] => 1.56
)*/
```

Check out [Item Interface](#item-interface) to learn more about it.

**value**

Get a single column's value from the first item found.

```php
$value = $storage->table('products')->value('sku');

var_dump($value);
// string(5) "paper"
```

**count**

Get the items count.

```php
$count = $storage->table('products')->count();

var_dump($count);
// int(2)
```

#### Where Clauses

**where / orWhere**

```php
$products = $storage->table('products')
    ->where('price', '>', 1.3)
    ->get();
    
$products = $storage->table('products')
    ->where('price', '>', 1.2)
    ->orWhere(function($query) {
        $query->where('price', '>', 1.2)
              ->where('sku', '=', 'pen');
    })
    ->get();
    
$products = $storage->table('products')
    ->where(function($query) {
        $query->where('price', '>', 1.2)
              ->orWhere('sku', '=', 'pen');
    })
    ->get();

// Finds any values that start with "a"
$products = $storage->table('products')
    ->where('sku', 'like', 'a%')
    ->get();
    
// Finds any values that end with "a"
$products = $storage->table('products')
    ->where('sku', 'like', '%a')
    ->get();
    
// Finds any values that have "a" in any position
$products = $storage->table('products')
    ->where('sku', 'like', '%a%')
    ->get();    
```

Supported operators: =, !=, >, <, >=, <=, <>, <=>, like, not like

**whereIn / whereNotIn / orWhereIn / orWhereNotIn**

```php
$products = $storage->table('products')
    ->whereIn('id', [2, 3])
    ->get();
    
$products = $storage->table('products')
    ->whereNotIn('id', [2, 3])
    ->get();
```

**whereNull / whereNotNull / orWhereNull / orWhereNotNull**

```php
$products = $storage->table('products')
    ->whereNull('price')
    ->get();
    
$products = $storage->table('products')
    ->whereNotNull('price')
    ->get();    
```

**whereBetween / whereNotBetween / orWhereBetween / orWhereNotBetween**

```php
$products = $storage->table('products')
    ->whereBetween('price', [1.2, 15])
    ->get();
    
$products = $storage->table('products')
    ->whereNotBetween('price', [1.2, 15])
    ->get();
```

**whereColumn**

```php
$users = $storage->table('users')
    ->whereColumn('firstname', '=', 'lastname')
    ->get();
```

Supported operators: =, !=, >, <, >=, <=, <>, <=>

#### JSON Where Clauses

**whereJsonContains**

```php
$products = $storage->table('products')
    ->whereJsonContains('options->color', 'blue')
    ->get();
    
$products = $storage->table('products')
    ->whereJsonContains('options->color', ['blue', 'red'])
    ->get();
```

**whereJsonLength**

```php
$products = $storage->table('products')
    ->whereJsonLength('options->color', '>', 2)
    ->get();
```

Supported operators: =, !=, >, <, >=, <=, <>, <=>

#### Join Clauses

**join**

```php
$products = $storage->table('products')
    ->join('products_lg', 'id', '=', 'product_id')
    ->get();
```

**leftJoin / rightJoin**

```php
$products = $storage->table('products')
    ->leftJoin('products_lg', 'id', '=', 'product_id')
    ->get();
    
$products = $storage->table('products')
    ->rightJoin('products_lg', 'id', '=', 'product_id')
    ->get();
```

**Advanced join clauses**

```php
$products = $storage->table('products')
    ->join('products_lg', function($join) {
        $join->on('id', '=', 'product_id')
             ->orOn('id', '=', 'language_id');         
    })
    ->get();
    
$products = $storage->table('products')
    ->join('products_lg', function($join) {
        $join->on('id', '=', 'product_id')
             ->where('product_id', '>', 2);
    })
    ->get();
```

#### Group Clauses

```php
$products = $storage->table('products')
    ->groupBy('price')
    ->having('price', '>', 2)
    ->get();
    
$products = $storage->table('products')
    ->groupBy('price')
    ->havingBetween('price', [1, 4])
    ->get();
```

#### Select Columns

You may select just specific columns.

```php
$products = $storage->table('products')
    ->select('id', 'sku')
    ->get();
    
$product = $storage->table('products')
    ->select('id', 'sku')
    ->first();
```

#### Index Column

Specify the column you want the items to be indexed.

```php
$products = $storage->table('products')
    ->index('sku')
    ->get();
```

#### Ordering

```php
$products = $storage->table('products')
    ->order('sku', 'ASC')
    ->get();
    
$products = $storage->table('products')
    ->order('sku', 'DESC')
    ->get();    
```

#### Limit

```php
$products = $storage->table('products')
    ->limit(number: 2, offset: 10)
    ->get();
```

### Insert Statements

```php
use Tobento\Service\Storage\ResultInterface;
use Tobento\Service\Storage\ItemInterface;

$inserted = $storage
    ->table('products')
    ->insert([
        'sku' => 'glue',
        'price' => 4.55,
    ]);
    
var_dump($inserted instanceof ResultInterface);
// bool(true)

var_dump($inserted->item() instanceof ItemInterface);
// bool(true)
```

Check out [Result Interface](#result-interface) to learn more about it.

Check out [Item Interface](#item-interface) to learn more about it.

### Update Statements

You may constrain the update query using where clauses.

```php
use Tobento\Service\Storage\ResultInterface;
use Tobento\Service\Storage\ItemsInterface;

$updated = $storage
    ->table('products')
    ->where('id', '=', 2)
    ->update([
        'price' => 4.55,
    ]);

var_dump($updated instanceof ResultInterface);
// bool(true)

var_dump($updated->items() instanceof ItemsInterface);
// bool(true)

var_dump($updated->itemsCount());
// int(1)
```

Check out [Result Interface](#result-interface) to learn more about it.

Check out [Items Interface](#items-interface) to learn more about it.

**updateOrInsert**

```php
use Tobento\Service\Storage\ResultInterface;
use Tobento\Service\Storage\ItemInterface;

$result = $storage->table('products')->updateOrInsert(
    ['id' => 3], // where clauses
    ['sku' => 'glue', 'price' => 3.48]
);

var_dump($result instanceof ResultInterface);
// bool(true)

var_dump($result->item() instanceof ItemInterface);
// bool(true)

var_dump($result->action());
// string(6) "insert"
```

Check out [Result Interface](#result-interface) to learn more about it.

Check out [Item Interface](#item-interface) to learn more about it.

#### Updating JSON Columns

```php
$updated = $storage
    ->table('products')
    ->where('id', 2)
    ->update([
        'options->color' => ['red'],
        'options->active' => true,
    ]);
```

### Delete Statements

```php
use Tobento\Service\Storage\ResultInterface;
use Tobento\Service\Storage\ItemsInterface;

$deleted = $storage->table('products')
    ->where('price', 1.33, '>')
    ->delete();

var_dump($deleted instanceof ResultInterface);
// bool(true)

var_dump($deleted->items() instanceof ItemsInterface);
// bool(true)

var_dump($deleted->itemsCount());
// int(1)
```

Check out [Result Interface](#result-interface) to learn more about it.

Check out [Items Interface](#items-interface) to learn more about it.

### Transactions

**commit**

```php
$storage->begin();

// your queries

$storage->commit();
```

**rollback**

```php
$storage->begin();

// your queries

$storage->rollback();
```

**transaction**

You may use the transaction method to run a set of storage operations within a transaction. If an exception is thrown within the transaction closure, the transaction will automatically be rolled back. If the closure executes successfully, the transaction will automatically be committed.

```php
use Tobento\Service\Storage\StorageInterface;

$storage->transaction(function(StorageInterface $storage) {
    // your queries  
});
```

### Debugging

```php
[$statement, $bindings] = $storage->table('products')
    ->where('id', '=', 1)
    ->getQuery();

var_dump($statement);
// string(56) "SELECT `id`,`sku`,`price` FROM `products` WHERE `id` = ?"

var_dump($bindings);
// array(1) { [0]=> int(1) }
```

**Using a Closure**

You must use a Closure if you want to debug other retrieving methods than "get" or insert, update and delete statements.

```php
[$statement, $bindings] = $storage->table('products')
    ->where('id', '=', 1)
    ->getQuery(function(StorageInterface $storage) {
        $storage->update([
            'price' => 4.55,
        ]);
    });

var_dump($statement);
// string(48) "UPDATE `products` SET `price` = ? WHERE `id` = ?"

var_dump($bindings);
// array(2) { [0]=> float(4.55) [1]=> int(1) }
```

## Item Interface

**get**

Get an item value by key.

```php
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\Item;

$item = new Item(['title' => 'Title']);

var_dump($item instanceof ItemInterface);
// bool(true)

var_dump($item->get('title'));
// string(5) "Title"

// returns the default value if the key does not exist.
var_dump($item->get('sku', 'Sku'));
// string(3) "Sku"
```

**all**

Returns all items (attributes).

```php
use Tobento\Service\Storage\Item;

$item = new Item(['title' => 'Title']);

var_dump($item->all());
// array(1) { ["title"]=> string(5) "Title" }
```

**collection**

Returns a new Collection with the attributes.

```php
use Tobento\Service\Storage\Item;
use Tobento\Service\Collection\Collection;

$item = new Item(['title' => 'Title']);

var_dump($item->collection() instanceof Collection);
// bool(true)
```

Check out the [Collection](https://github.com/tobento-ch/service-collection#collection) to learn more about it.

## Items Interface

**get**

Get an item value by key.

```php
use Tobento\Service\Storage\ItemsInterface;
use Tobento\Service\Storage\Items;

$items = new Items([
    'foo' => ['title' => 'Title'],
]);

var_dump($items instanceof ItemsInterface);
// bool(true)

var_dump($items->get('foo.title'));
// string(5) "Title"

// returns the default value if the key does not exist.
var_dump($items->get('foo.sku', 'Sku'));
// string(3) "Sku"
```

**all**

Returns all items.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    'foo' => ['title' => 'Title'],
]);

var_dump($items->all());
// array(1) { ["foo"]=> array(1) { ["title"]=> string(5) "Title" } }
```

**collection**

Returns a new Collection with the items.

```php
use Tobento\Service\Storage\Items;
use Tobento\Service\Collection\Collection;

$items = new Items([
    'foo' => ['title' => 'Title'],
]);

var_dump($items->collection() instanceof Collection);
// bool(true)
```

Check out the [Collection](https://github.com/tobento-ch/service-collection#collection) to learn more about it.

## Result Interface

```php
use Tobento\Service\Storage\ResultInterface;
use Tobento\Service\Storage\ItemsInterface;
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\Result;
use Tobento\Service\Storage\Items;
use Tobento\Service\Storage\Item;

$result = new Result(
    action: 'update',
    item: new Item(['title' => 'Title']),
    items: new Items(['foo' => ['title' => 'Title']]),
);

var_dump($result instanceof ResultInterface);
// bool(true)

var_dump($result->item() instanceof ItemInterface);
// bool(true)

var_dump($result->items() instanceof ItemsInterface);
// bool(true)

var_dump($result->itemsCount());
// int(1)
```

## Tables

Tables are used by the storages for verifying table and column names for building queries.

**add**

```php
use Tobento\Service\Storage\Tables\Tables;

$tables = new Tables();
$tables->add(
    table: 'products',
    columns: ['id', 'sku'],
    primaryKey: 'id' // or null if none
);
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
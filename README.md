# Storage Service

The Storage Service comes with a query builder for storing and fetching items.

## Table of Contents

- [Getting started](#getting-started)
	- [Requirements](#requirements)
	- [Highlights](#highlights)
	- [Simple Example](#simple-example)
- [Documentation](#documentation)
    - [Storages](#storages)
        - [Pdo MariaDb Storage](#pdo-mariadb-storage)
        - [Pdo MySql Storage](#pdo-mysql-storage)
        - [Json File Storage](#json-file-storage)
        - [In Memory Storage](#in-memory-storage)
        - [Storage Comparison](#storage-comparison)
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
        - [Mass Insert Statements](#mass-insert-statements)
            - [Item Factory](#item-factory)
            - [Json File Items](#json-file-items)
        - [Update Statements](#update-statements)
        - [Delete Statements](#delete-statements)
        - [Transactions](#transactions)
        - [Chunking Results and Inserts](#chunking-results-and-inserts)
        - [Miscellaneous](#miscellaneous)
        - [Debugging](#debugging)
    - [Item Interface](#item-interface)
    - [Items Interface](#items-interface)
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
- PDO MariaDb Storage
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

### Pdo MariaDb Storage

```php
use Tobento\Service\Database\PdoDatabaseFactory;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\PdoMariaDbStorage;
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

$storage = new PdoMariaDbStorage($pdo, $tables);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

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

### Storage Comparison

Return items columns support:

| Storage | insert() | insertMany() | update() | delete() |
| --- | --- | --- | --- | --- |
| [Pdo MariaDb Storage](#pdo-mariadb-storage) | yes | yes | no | yes |
| [Pdo MySql Storage](#pdo-mysql-storage) | yes | no | no | no |
| [Json File Storage](#json-file-storage) | yes | yes | yes | yes |
| [In Memory Storage](#in-memory-storage) | yes | yes | yes | yes |
        
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

**whereColumn / orWhereColumn**

```php
$users = $storage->table('users')
    ->whereColumn('firstname', '=', 'lastname')
    ->get();
```

Supported operators: =, !=, >, <, >=, <=, <>, <=>

#### JSON Where Clauses

**whereJsonContains / orWhereJsonContains**

```php
$products = $storage->table('products')
    ->whereJsonContains('options->color', 'blue')
    ->get();
    
$products = $storage->table('products')
    ->whereJsonContains('options->color', ['blue', 'red'])
    ->get();
```

**whereJsonContainsKey / orWhereJsonContainsKey**

```php
$products = $storage->table('products')
    ->whereJsonContainsKey('options->color')
    ->get();
```

**whereJsonLength / orWhereJsonLength**

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
use Tobento\Service\Storage\ItemInterface;

$insertedItem = $storage
    ->table('products')
    ->insert([
        'sku' => 'glue',
        'price' => 4.55,
    ]);

var_dump($insertedItem instanceof ItemInterface);
// bool(true)
```

Check out [Item Interface](#item-interface) to learn more about it.

**return specific columns**

```php
$insertedItem = $storage
    ->table('products')
    ->insert(
        item: ['sku' => 'glue'],
        return: ['id']
    );

var_dump($insertedItem->all());
```

**return null**

```php
$insertedItem = $storage
    ->table('products')
    ->insert(
        item: ['sku' => 'glue'],
        return: null
    );

var_dump($insertedItem->all());
// array(0) { }
```

### Mass Insert Statements

```php
use Tobento\Service\Storage\ItemsInterface;

$insertedItems = $storage
    ->table('products')
    ->insertItems([
        ['sku' => 'glue', 'price' => 4.55],
        ['sku' => 'pencil', 'price' => 1.99],
    ]);

var_dump($insertedItems instanceof ItemsInterface);
// bool(true)
```

Check out [Items Interface](#items-interface) to learn more about it.

**return specific columns**

```php
$insertedItems = $storage
    ->table('products')
    ->insertItems(
        items: [
            ['sku' => 'glue', 'price' => 4.55],
            ['sku' => 'pencil', 'price' => 1.99],
        ],
        return: ['id']
    );

var_dump($insertedItems->all());
// array(2) { [0]=> array(1) { ["id"]=> int(3) } [1]=> array(1) { ["id"]=> int(4) } }
```

**return null**

```php
$insertedItems = $storage
    ->table('products')
    ->insertItems(
        items: [
            ['sku' => 'glue', 'price' => 4.55],
            ['sku' => 'pencil', 'price' => 1.99],
        ],
        return: null
    );

var_dump($insertedItems->all());
// array(0) { }
```

##### Item Factory

You may use the item factory iterator to seed items and use the [Seeder Service](https://github.com/tobento-ch/service-seeder) to generate fake data.

```php
use Tobento\Service\Iterable\ItemFactoryIterator;
use Tobento\Service\Seeder\Str;
use Tobento\Service\Seeder\Num;

$insertedItems = $storage->table('products')
    ->chunk(length: 20000)
    ->insertItems(
        items: new ItemFactoryIterator(
            function() {
                return [
                    'sku' => Str::string(10),
                    'price' => Num::float(min: 1.5, max: 55.5),
                ];
            },
            create: 1000000 // create 1 million items
        )
    );
    
foreach($insertedItems as $product) {}
```

##### Json File Items

```php
use Tobento\Service\Iterable\JsonFileIterator;
use Tobento\Service\Iterable\ModifyIterator;

$iterator = new JsonFileIterator(
    file: 'private/src/products.json',
);

// you may use the modify iterator:
$iterator = new ModifyIterator(
    iterable: $iterator,
    modifier: function(array $item): array {
        return [
          'sku' => $item['sku'] ?? '',
          'price' => $item['price'] ?? '',
        ];
    }
);
        
$insertedItems = $storage->table('products')
    ->chunk(length: 20000)
    ->insertItems($iterator);
    
foreach($insertedItems as $product) {}
```

### Update Statements

You may constrain the update query using where clauses.

```php
use Tobento\Service\Storage\ItemsInterface;

$updatedItems = $storage
    ->table('products')
    ->where('id', '=', 2)
    ->update([
        'price' => 4.55,
    ]);

var_dump($updatedItems instanceof ItemsInterface);
// bool(true)
```

Check out [Items Interface](#items-interface) to learn more about it.

**return specific columns**

```php
$updatedItems = $storage
    ->table('products')
    ->where('price', '>', 1.5)
    ->update(
        item: ['price' => 4.55],
        return: ['id']
    );

var_dump($updatedItems->all());
// array(2) { [0]=> array(1) { ["id"]=> int(2) } }
```

> :warning: **[Pdo MySql Storage](#pdo-mysql-storage) does not return the items as returning statements are not supported.**

You may get the count though:

```php
var_dump($updatedItems->count());
// int(1)
```

**return null**

```php
$updatedItems = $storage
    ->table('products')
    ->where('price', '>', 1.5)
    ->update(
        item: ['price' => 4.55],
        return: null
    );

var_dump($updatedItems->all());
// array(0) { }
```
**updateOrInsert**

```php
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\ItemsInterface;

$items = $storage->table('products')->updateOrInsert(
    ['id' => 2], // where clauses
    ['sku' => 'glue', 'price' => 3.48],
    return: ['id']
);

// if updated:
var_dump($items instanceof ItemsInterface);
// bool(true)

// if inserted:
var_dump($items instanceof ItemInterface);
// bool(true)
```

Check out [Item Interface](#item-interface) to learn more about it.

Check out [Items Interface](#items-interface) to learn more about it.

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
use Tobento\Service\Storage\ItemsInterface;

$deletedItems = $storage->table('products')
    ->where('price', '>', 1.33)
    ->delete();

var_dump($deletedItems instanceof ItemsInterface);
// bool(true)
```

Check out [Items Interface](#items-interface) to learn more about it.

**return specific columns**

```php
$deletedItems = $storage->table('products')
    ->where('id', '=', 2)
    ->delete(return: ['sku']);

var_dump($deletedItems->all());
// array(1) { [0]=> array(1) { ["sku"]=> string(3) "pen" } }
```

**return null**

```php
$deletedItems = $storage->table('products')
    ->where('id', '=', 2)
    ->delete(return: null);

var_dump($deletedItems->all());
// array(0) { }
```

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

### Chunking Results and Inserts

You may use the chunk method if you need to work with thousands or even millions of item(s).

**column**

Returns the column in generator mode.

```php
$column = $storage->table('products')
    ->chunk(length: 2000)
    ->column('price');

foreach($column as $price) {
    var_dump($price);
    // float(1.2)
}
```

**get**

Returns the items in generator mode.

```php
$products = $storage->table('products')
    ->chunk(length: 2000)
    ->get();

foreach($products as $product) {
    var_dump($product['sku']);
    // string(5) "paper"
}
```

**insertItems**

Returns the inserted items in generator mode.

```php
$insertedItems = $storage
    ->table('products')
    ->chunk(length: 10000)
    ->insertItems([
        ['sku' => 'glue', 'price' => 4.55],
        ['sku' => 'pencil', 'price' => 1.99],
        // ...
    ]);
    
foreach($insertedItems as $product) {
    var_dump($product['id']);
    // int(3)
}
```

If you set the return parameter to null it will immediately insert the items but not return them in generator mode. You may be able to get the number of items created though.

```php
$insertedItems = $storage
    ->table('products')
    ->chunk(length: 10000)
    ->insertItems([
        ['sku' => 'glue', 'price' => 4.55],
        ['sku' => 'pencil', 'price' => 1.99],
        // ...
    ], return: null);

var_dump($insertedItems->count());
// int(2)
```

### Miscellaneous

**new**

The new method will return a new storage instance.

```php
$newStorage = $storage->new();
```

**fetchItems**

The fetchItems method will return all table items.

```php
$iterable = $storage->fetchItems(table: 'products');
```

**storeItems**

The storeItems method will store the items to the table which will be truncated first.

```php
$storedItems = $storage->storeItems(
    table: 'products',
    items: [] // iterable
);
```

**deleteTable**

The deleteTable method will delete the table completely.

```php
$storage->deleteTable('products');
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

Iterating item attributes:

```php
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\Item;

$item = new Item(['title' => 'Title']);

var_dump($item instanceof ItemInterface);
// bool(true)

foreach($item as $attr) {
    var_dump($attr);
    // string(5) "Title"
}
```

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

**count**

Returns the number of the attributes.

```php
use Tobento\Service\Storage\Item;

$item = new Item(['title' => 'Title']);

var_dump($item->count());
// int(1)
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

Iterating items:

```php
use Tobento\Service\Storage\ItemsInterface;
use Tobento\Service\Storage\Items;

$items = new Items([
    'foo' => ['title' => 'Title'],
]);

var_dump($items instanceof ItemsInterface);
// bool(true)

foreach($items as $item) {
    var_dump($item['title']);
    // string(5) "Title"
}
```

**get**

Get an item value by key.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    'foo' => ['title' => 'Title'],
]);

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

**first**

Returns the first item, otherwise null.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    ['foo' => 'Foo'],
    ['bar' => 'Bar'],
]);

var_dump($items->first());
// array(1) { ["foo"]=> string(3) "Foo" }
```

**column**

Returns the column of the items.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    ['name' => 'Foo', 'id' => 3],
    ['name' => 'Bar', 'id' => 5],
]);

var_dump($items->column(column: 'name'));
// array(2) { [0]=> string(3) "Foo" [1]=> string(3) "Bar" }

// with index
var_dump($items->column(column: 'name', index: 'id'));
// array(2) { [3]=> string(3) "Foo" [5]=> string(3) "Bar" }
```

**map**

Map over each of the items returning a new instance.

```php
use Tobento\Service\Storage\Items;
use Tobento\Service\Storage\Item;

$items = new Items([
    ['foo' => 'Foo'],
    ['bar' => 'Bar'],
]);

$itemsNew = $items->map(function(array $item): object {
    return new Item($item);
});

var_dump($itemsNew->first());
// object(Tobento\Service\Storage\Item)#8 ...
```

**groupBy**

Returns a new instance with the grouped items.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    ['name' => 'bear', 'group' => 'animals'],
    ['name' => 'audi', 'group' => 'cars'],
    ['name' => 'ant', 'group' => 'animals'],
]);

$groups = $items->groupBy(groupBy: 'group')->all();
/*Array
(
    [animals] => Array
        (
            [0] => Array
                (
                    [name] => bear
                    [group] => animals
                )
            [2] => Array
                (
                    [name] => ant
                    [group] => animals
                )
        )
    [cars] => Array
        (
            [1] => Array
                (
                    [name] => audi
                    [group] => cars
                )
        )
)*/

// using a callable:
$groups = $items->groupBy(
    groupBy: fn ($item) => $item['group'],
);

// using a callable for grouping:
$groups = $items->groupBy(
    groupBy: 'group',
    groupAs: fn (array $group) => (object) $group,
);

// without preserving keys:
$groups = $items->groupBy(
    groupBy: 'group',
    preserveKeys: false,
);
```

**reindex**

Reindex items returning a new instance.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    ['sku' => 'foo', 'name' => 'Foo'],
    ['sku' => 'bar', 'name' => 'Bar'],
]);

$itemsNew = $items->reindex(function(array $item): int|string {
    return $item['sku'];
});

var_dump(array_keys($itemsNew->all()));
// array(2) {[0]=> string(3) "foo" [1]=> string(3) "bar"}
```

**count**

Returns the number of the items.

```php
use Tobento\Service\Storage\Items;

$items = new Items([
    'foo' => ['title' => 'Title'],
]);

var_dump($items->count());
// int(1)
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
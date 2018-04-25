Nothing
-------

Still in progress.
You SHOULD NOT use this package until release 1.0.

This is something close to a DataMapper, with very high flexibility but need more code than other libraries to be used.

### Run a query

Nothing new, it's the [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/)

```php
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$connection = DriverManager::getConnection(['url' => 'mysql://...'], new Configuration());
$queryBuilder = $connection->createQueryBuilder();
$queryBuilder
    ->select('id', 'name')
    ->from('user');

$rows = $queryBuilder->execute();

foreach ($rows as $row) {
    print_r($row);
}
```

Output is
```php
Array
(
    [id] => 1
    [name] => Sylvain
)
```

### How to hydrate (without class)

Nothing to learn, it's pure PHP, do how you want.

Assuming you have this class
```php
class User
{
    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

You can do
```php
// ... from previous example
$rows = $queryBuilder->execute();
$rowsHydrated = array_map(
    function ($row) {
        return new User($row['id'], $row['name']);
    },
    $rows->fetchAll()
);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

Output is
```php
User Object
(
    [id] => 1
    [name] => Sylvain
)
```

### How to hydrate (with class)

Nothing to learn, it's pure PHP, do how you want.

Assuming you have this class
```php
class UserMapper
{
    public function map(iterable $rows): \ArrayObject
    {
        $collection = new \ArrayObject();

        foreach ($rows as $row) {
            $collection->append(new User($row['id'], $row['name']));
        }

        return $collection;
    }
}
```

You can do
```php
// ... from previous example
$rows = $queryBuilder->execute();

$userMapper = new UserMapper();
$rowsHydrated = $userMapper->map($rows);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

Output is
```php
User Object
(
    [id] => 1
    [name] => Sylvain
)
```

### Type mapping

Almost nothing new, it's [Doctrine DBAL Types](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html) to convert SQL types to PHP types
But, you have a TypeConverter that's a wrapper to convert your type more easily.

Assuming you have this class
```php
class User
{
    public function __construct(int $id, string $name, DateTime $lastUpdated)
    {
        $this->id          = $id;
        $this->name        = $name;
        $this->lastUpdated = $lastUpdated;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastUpdated(): DateTime
    {
        return $this->lastUpdated;
    }
}
```

You can do
```php
use Blackprism\Nothing\TypeConverter;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$typeConverter = new TypeConverter($connection->getDatabasePlatform());

$rowsHydrated = array_map(
    function ($row) use ($typeConverter) {
        return new User(
            $row['id'],
            $row['name'],
            $typeConverter->convertToPHP($row['last_updated'], 'datetime')
        );
    },
    $rows->fetchAll()
);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

Output is
```php
User Object
(
    [id] => 1
    [name] => Sylvain
    [lastUpdated] => DateTime Object
        (
            [date] => 2018-04-16 03:02:01.000000
            [timezone_type] => 3
            [timezone] => UTC
        )

)
```

### Custom type

Assuming you have this class
```php
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class PrefixStringType extends StringType
{
    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return 'prefixed ' . $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'prefixed_string';
    }
}
```

You can do
```php
use Blackprism\Nothing\TypeConverter;
use Doctrine\DBAL\Types\Type;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

Type::addType('prefixed_string', PrefixStringType::class);
$typeConverter = new TypeConverter($connection->getDatabasePlatform());

$rowsHydrated = array_map(
    function ($row) use ($typeConverter) {
        return new User(
            $row['id'],
            $typeConverter->convertToPHP($row['name'], 'prefixed_string'),
            $typeConverter->convertToPHP($row['last_updated'], 'datetime')
        );
    },
    $rows->fetchAll()
);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

Output is
```php
User Object
(
    [id] => 1
    [name] => prefixed Sylvain
    [lastUpdated] => DateTime Object
        (
            [date] => 2018-04-16 03:02:01.000000
            [timezone_type] => 3
            [timezone] => UTC
        )

)
```

### AutoMapping

Assuming you have this code
```php
use Blackprism\Nothing\EntityMapping;

$userMapping = new EntityMapping(
    User::class,
    [
       'id'           => 'integer',
       'name'         => 'string',
       'last_updated' => 'datetime',
    ]
);
```

You can do
```php
use Blackprism\Nothing\AutoMapping;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$autoMapping = new AutoMapping($connection->getDatabasePlatform(), [$userMapping]);
$rowsHydrated = $autoMapping->map($rows);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

Output is
```php
Array
(
    [User] => User Object
        (
            [id] => 2
            [name] => François
            [lastUpdated] => DateTime Object
                (
                    [date] => 2018-04-23 00:00:00.000000
                    [timezone_type] => 3
                    [timezone] => UTC
                )
        )
)
```

#### You can prefix the column
```php
use Blackprism\Nothing\AutoMapping;

// ... from previous example
$queryBuilder
    ->select('id as user_id', 'name as user_name', 'last_updated as user_last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$autoMapping = new AutoMapping($connection->getDatabasePlatform(), ['user_' => $userMapping]);
$rowsHydrated = $autoMapping->map($rows);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

#### You can embed object into an other
```php
$authorMapping = new EntityMapping(
    Author::class,
    [
        'id'   => 'integer',
        'name' => 'string'
    ]
);

$bookMapping = new EntityMapping(
    Book::class,
    [
       'id'   => 'integer',
       'name' => 'string',
       Author::class => AutoMapping::SUB_OBJECT
    ]
);
```

#### You can add named constructor
```php

$bookMapping = new EntityMapping(
    Book::class,
    [
       'id'   => 'integer',
       'name' => 'string'
    ]
);

$bookMapping->buildWith(
    'withAuthor',
    [
        'id'   => 'integer',
        'name' => 'string',
        Author::class => AutoMapping::SUB_OBJECT
    ]
);
```

AutoMapping will do :
```php
Book::withAuthor($row['id'], $row['name'], new Author(...
```

#### AutoAlias helper
When you build your query you'll often use alias like this :
```php
$queryBuilder->select('book.id as book_id', 'book.name as book_name', 'author.id as author_id', 'author.name as author_name')
```

AutoAlias help you to make this easier :
```php
$queryBuilder->select((new AutoAlias)('book.id', 'book.name', 'author.id', 'author.name'))
```

### Sample

https://github.com/blackprism/nothing-sample

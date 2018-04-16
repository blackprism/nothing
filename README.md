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

### Hydrator Callable

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
use Blackprism\Nothing\HydratorCallable;

// ... from previous example
$rows = $queryBuilder->execute();

$hydrator = new HydratorCallable();
$rowsHydrated = $hydrator->map($rows, [] /* $data */, function ($row, $data) {
    $data[$row['id']] = new User($row['id'], $row['name']);

    return $data;
});

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

### Hydrator

Assuming you have this class
```php
use Blackprism\Nothing\Hydrator\Mapper;

class UserMapper implements Mapper
{
    public function map(array $row, $data)
    {
        $data[$row['id']] = new User($row['id'], $row['name']);

        return $data;
    }
}
```

You can do
```php
use Blackprism\Nothing\Hydrator;

// ... from previous example
$rows = $queryBuilder->execute();

$hydrator = new Hydrator();
$rowsHydrated = $hydrator->map($rows, [] /* $data */, new UserMapper());

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

### RowConverter (Type mapping)

RowConverter allows you to use [Doctrine DBAL Types](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html) to convert SQL types to PHP types

Assuming you have this class
```php
use Blackprism\Nothing\RowConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class UserRowConverter
{
    public function getRowConverter(AbstractPlatform $connection): RowConverter
    {
        $rowConverter = new RowConverter($connection);
        $rowConverter->registerType('last_updated', 'datetime'); // datetime is a type already in Doctrine DBAL Types 

        return $rowConverter;
    }
}
```

and
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
use Blackprism\Nothing\HydratorCallable;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$rowConverter = (new UserRowConverter())->getRowConverter($connection->getDatabasePlatform());

$hydrator = new HydratorCallable();
$rowsHydrated = $hydrator->map(
    $rows,
    [] /* $data */,
    function ($row, $data) {
        $data[$row['id']] = new User($row['id'], $row['name'], $row['last_updated']);

        return $data;
    },
    $rowConverter
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

### RowConverter (with custom type)

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

and
```php
use Blackprism\Nothing\RowConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class UserRowConverter
{
    public function __construct()
    {
        Type::addType('prefixed_string', PrefixStringType::class);
    }

    public function getRowConverter(AbstractPlatform $connection): RowConverter
    {
        $rowConverter = new RowConverter($connection);
        $rowConverter->registerType('name', 'prefixed_string');
        $rowConverter->registerType('last_updated', 'datetime'); // datetime is a type already in Doctrine DBAL Types 

        return $rowConverter;
    }
}
```

You can do
```php
use Blackprism\Nothing\HydratorCallable;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$rowConverter = (new UserRowConverter())->getRowConverter($connection->getDatabasePlatform());

$hydrator = new HydratorCallable();
$rowsHydrated = $hydrator->map(
    $rows,
    [] /* $data */,
    function ($row, $data) {
        $data[$row['id']] = new User($row['id'], $row['name'], $row['last_updated']);

        return $data;
    },
    $rowConverter
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

### Sample

https://github.com/blackprism/nothing-sample

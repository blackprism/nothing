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
    public function map(iterable $row, iterable $data): iterable
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
use Blackprism\Nothing\HydratorCallable;
use Blackprism\Nothing\TypeConverter;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$typeConverter = new TypeConverter($connection->getDatabasePlatform());

$hydrator = new HydratorCallable();
$rowsHydrated = $hydrator->map(
    $rows,
    [] /* $data */,
    function ($row, $data) use ($typeConverter) {
        $data[$row['id']] = new User(
            $row['id'],
            $row['name'],
            $typeConverter->convertToPHP($row['last_updated'], 'datetime')
        );

        return $data;
    }
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
use Blackprism\Nothing\HydratorCallable;
use Doctrine\DBAL\Types\Type;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

Type::addType('prefixed_string', PrefixStringType::class);

$platform = $connection->getDatabasePlatform();
$hydrator = new HydratorCallable();
$rowsHydrated = $hydrator->map(
    $rows,
    [] /* $data */,
    function ($row, $data) use ($platform) {
        $data[$row['id']] = new User(
            $row['id'],
            Type::getType('prefixed_string')->convertToPHPValue($row['name'], $platform),
            Type::getType('datetime')->convertToPHPValue($row['last_updated'], $platform)
        );
        return $data;
    }
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
use Blackprism\Nothing\Hydrator;

// ... from previous example
$queryBuilder
    ->select('id', 'name', 'last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$hydrator = new Hydrator();
$rowsHydrated = $hydrator->map(
    $rows,
    [] /* $data */,
   new AutoMapping($connection->getDatabasePlatform(), [$userMapping])
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
)
```

#### You can prefix the column
```php
use Blackprism\Nothing\AutoMapping;
use Blackprism\Nothing\Hydrator;

// ... from previous example
$queryBuilder
    ->select('id as user_id', 'name as user_name', 'last_updated as user_last_updated')
    ->from('user');
$rows = $queryBuilder->execute();

$hydrator = new Hydrator();
$rowsHydrated = $hydrator->map(
    $rows,
    [] /* $data */,
   new AutoMapping($connection->getDatabasePlatform(), ['user_' => $user])
);

foreach ($rowsHydrated as $userId => $user) {
    print_r($user);
}
```

#### You can embed object into an other
```php
$author = new EntityMapping(
    Author::class,
    [
        'id'   => 'integer',
        'name' => 'string'
    ]
);

$book = new EntityMapping(
    Book::class,
    [
       'id'   => 'integer',
       'name' => 'string',
       Author::class => AutoMapping::SUB_OBJECT
    ]
);
```

### Sample

https://github.com/blackprism/nothing-sample

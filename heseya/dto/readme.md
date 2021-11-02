# Heseya Dto
Simple Dto system with optional parameters

## Get started
To create a new Dto you need to extend base `Heseya\Dto\Dto` class as such:
```php
use Heseya\Dto\Dto;

class ExampleDto extends Dto {
    private string $property1;
    private string $property2;
}
```
`Heseya\Dto\Dto` base class provides constructor automatically handling all property initialization.

To create a new Dto use `PHP 8` named arguments:
```php
$dto = new ExampleDto(property1: 'value1', property2: 'value2');
```
Or use an associative array:
```php
$array = ['property1' => 'value1', 'property2' => 'value2'];

$dto = new ExampleDto($array);
```
The built-in `toArray()` method automatically returns Dto properties in the associative array format:

```php
$dto->toArray(); // ['property1' => 'value1', 'property2' => 'value2']
```

## Optional properties
To make your Dto properties optional you need to use `Heseya\Dto\Missing` type as such:
```php
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class OptionalDto extends Dto {
    private string $required;
    private string|Missing $optional;
}
```

Optional properties when not initialized will automatically be hidden in `toArray()` method result:
```php
$dto = new OptionalDto(required: 'required value')

$dto->toArray(); // ['required' => 'required value']
```

## Hidden properties
Sometimes you want to hide your internal properties from appearing in the `toArray()` method result

You can hide those properties using `Heseya\Dto\Hidden` attribute:
```php
use Heseya\Dto\Dto;
use Heseya\Dto\Hidden;

class HiddenDto extends Dto {
    private string $visible;
    #[Hidden]
    private string $hidden;
}

$dto = new HiddenDto(visible: 'visible value', hidden: 'hidden value');

$dto->toArray(); // [visible => 'visible value']
```

## Exceptions
Non-optional properties need to be set explicitly in the constructor,
otherwise a `Heseya\Dto\DtoException` will be raised:
```php
use Heseya\Dto\Dto;

class NonOptionalDto extends Dto {
    private ?string $param;
}

$dto = new NonOptionalDto(); // DtoException("Property NonOptionalDto::$param is required")
```
Nullable properties such as `private ?string $param` will not be automatically initialized as null
and need to be set explicitly as well.

Optional properties using `Heseya\Dto\Missing` type will be automatically initialized
as `new Missing()` class instance when not set explicitly.

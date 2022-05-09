# Coercive Utility Cache

Simple cache helpers.

## Get

```
composer require coercive/cache
```

## JSON

```php
use Coercive\Utility\Cache

# Load your cache instance for a defined project
$json = new Json('/temp/my_project_directory')

# Set a global default expire delay
$json = new Json('/temp/my_project_directory', 'P1D')
# OR
$json->setExpireDelay('P1D')

# Retrieve your data
$data = $json->get('data-key')

# Save your data
$json->set('data-key', ['data1', 'data2'])

# Save your data with specific expire delay
$json->set('data-key', ['data1', 'data2'], 'PT15M')

# Delete specific data
$json->delete('data-key')

# Empty cache
$json->clear()

# You can enable/disable the cache
$json->disable()
$json->enable()
$json->setState(bool)

# If cache disable
$json->get('data-key') => return null
```

## PHP

```php
use Coercive\Utility\Cache

# Load your cache instance for a defined project
$php = new Php('/temp/my_project_directory')

# Set a global default expire delay
$php = new Php('/temp/my_project_directory', 'P1D')
# OR
$php->setExpireDelay('P1D')

# Retrieve your data
$data = $php->get('data-key')

# Save your data
$php->set('data-key', ['data1', 'data2'])

# Save your data with specific expire delay
$php->set('data-key', ['data1', 'data2'], 'PT15M')

# Delete specific data
$php->delete('data-key')

# Empty cache
$php->clear()

# You can enable/disable the cache
$php->disable()
$php->enable()
$php->setState(bool)

# If cache disable
$php->get('data-key') => return null
```

## Global

```php
use Coercive\Utility\Cache

# Use debug method to handle exceptions
$cache->debug(function($e) {
    /** @var Exception $e */
    echo $e->getMessage();
    // do something
});

# You can also expose all exceptions
var_dump($cache->getExceptions());
```
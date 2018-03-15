Coercive Utility Cache
======================

Simple cache helpers.

Get
---
```
composer require coercive/cache
```

Redis
----
```php
use Coercive\Utility\Cache

# Load your redis instance for a defined project
$redis = new Redis('myproject')

# You can test if redis is ok
if($redis->isConnected()) { ... }

# Set a global default expire delay
$redis->setExpireDelay('P1D')

# Retrieve your data
$data = $redis->get('data-key')

# Save your data
$redis->set('date-name', ['data1', 'data2'])

# Save your data with specific expire delay
$redis->set('date-name', ['data1', 'data2'], 'PT15M')

# Empty cache
$redis->clear()

# You can check if there is an error
if($redis->isError()) { ... }

# You can enable/disable the cache
$redis->disable()
$redis->enable()
$redis->setState(bool)

# If cache disable
$redis->get('data-key') => return null
```

JSON
----
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

# You can check if there is an error
if($json->isError()) { ... }

# You can enable/disable the cache
$json->disable()
$json->enable()
$json->setState(bool)

# If cache disable
$json->get('data-key') => return null
```

PHP
----
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

# You can check if there is an error
if($php->isError()) { ... }

# You can enable/disable the cache
$php->disable()
$php->enable()
$php->setState(bool)

# If cache disable
$php->get('data-key') => return null
```
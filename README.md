Coercive Utility Cache
======================

BETA simple cache helpers ...

Get
---
```
composer require coercive/cache
```

Load
----
```php
use Coercive\Utility\Cache

$oRedis = new Redis('myproject')

$mDatas = $oRedis->get('date-name')

$oRedis->setExpireDelay('P1D')

$oRedis->set('date-name', ['data1', 'data2'])

if($oRedis->isConnected()) { ... }
if($oRedis->isError()) { ... }

ETC...

```
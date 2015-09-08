This bundle is the wrapper for Symfony and is able to work as http-cache

### Usage

```
public function registerBundles()
     {
         if (in_array($this->getEnvironment(), array('test', 'prod'))) {
             $bundles[] = new Snc\RedisBundle\SncRedisBundle();
         }

         return $bundles;
     }
```

composer.json
```
{
  "require": {
    "snc/redis-bundle": "~1.1",
    "predis/predis": "~1.0"
  },
```

Your `app.php` should look like:
```
require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
$kernel = new AppCache($kernel);
```

Extend your `AppCache` class in app/AppCache.php
```
<?php

require_once __DIR__.'/AppKernel.php';

use Odesk\Bundle\CacheBundle\RedisCache;

class AppCache extends RedisCache
{
}
```

config.yml
```
snc_redis:
  clients:
    default:
      type: predis
      alias: default
      dsn: %redis_dsn%
```

parameters.yml
```
    redis_dsn: redis://yourredisserver
    redis_prefix: prefix
```
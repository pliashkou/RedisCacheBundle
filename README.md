This bundle is the wrapper for Symfony and is able to work as http-cache

### Usage

Update your `app/AppKernel.php`
```
public function registerBundles()
     {
         if (in_array($this->getEnvironment(), array('test', 'prod'))) {
             $bundles[] = new Snc\RedisBundle\SncRedisBundle();
         }

         return $bundles;
     }
```

Update your `composer.json`
```
{
  "require": {
    "snc/redis-bundle": "~1.1",
    "predis/predis": "~1.0"
  },
```

Ensure, that your `app.php` looks like:
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

Update your `config.yml`
```
snc_redis:
  clients:
    default:
      type: predis
      alias: default
      dsn: %redis_dsn%
```

Updated your `parameters.yml`
```
    redis_dsn: redis://yourredisserver
    redis_prefix: prefix
```

Run `composer install`
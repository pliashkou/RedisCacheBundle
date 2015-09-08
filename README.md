This bundle is the wrapper for Symfony and is able to work as http cache or as service.

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

Update your `services.yml`
```yml
acme.redis_wrapper.class: CacheBundle\\Client\\RedisClient

acme.redis:
        class: %acme.redis_wrapper.class%
        arguments: ['@service_container']
```

License
----

Released under the terms of MIT License:

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

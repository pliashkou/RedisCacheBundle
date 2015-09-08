<?php

namespace Client;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class RedisClient
{
    private $container;
    private $redis;
    private $redisExists;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->redisExists = $this->container->has('snc_redis.default');
        $this->redis = $this->redisExists ? $this->container->get('snc_redis.default') : false;
    }

    function __call($method, $args) {
        if ($this->redisExists) {
            try {
                return call_user_func_array(array($this->redis, $method), $args);
            } catch (\Predis\ServerException $ex) {
                return false;
            }
        }
        else {
            return false;
        }
    }
}

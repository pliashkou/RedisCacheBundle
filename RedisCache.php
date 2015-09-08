<?php

use Store\RedisStore;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RedisCache extends HttpCache
{
    private $prefix;

    public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
    {
        $kernel->boot();

        parent::__construct($kernel, $cacheDir);
    }

    public function createStore()
    {
        $this->prefix = $this->kernel->getContainer()->getParameter('redis_prefix');

        return new RedisStore(
            $this->kernel->getContainer()->get('snc_redis.default'),
            $this->getDigestKeyPrefix(),
            $this->getLockKey(),
            $this->getMetadataKeyPrefix()
        );
    }

    public function getConnectionParams()
    {
        return array('host' => $this->kernel->getContainer()->getParameter('redis_dsn'));
    }

    public function getDigestKeyPrefix()
    {
        return $this->prefix.'d';
    }

    public function getLockKey()
    {
        return $this->prefix.'l';
    }

    public function getMetadataKeyPrefix()
    {
        return $this->prefix.'m';
    }
}

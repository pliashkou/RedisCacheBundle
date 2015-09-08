<?php

namespace Test\Store;


use Odesk\Bundle\CacheBundle\Store\RedisStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedisStoreTest extends \PHPUnit_Framework_TestCase
{
    protected $store;
    protected $redis;
    protected $request;

    protected function setUp()
    {
        $this->redis = $this->getMock('Predis\Client', array('get', 'set', 'setex', 'del', 'setnx', 'hSetNx', 'hdel'));
        $this->store = new RedisStore($this->redis, 'digest', 'lock', 'metadata');
        $this->request = Request::create('/');
    }

    public function testLookup()
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->store->lookup($this->request);
    }

    public function testWrite()
    {
        $response = Response::create();

        $this->redis
            ->expects($this->at(0))
            ->method('set')
            ->willReturn(true);

        $this->store->write($this->request, $response);
    }

    public function testInvalidate()
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn('a:0:{}');

        $this->store->invalidate($this->request);

    }

    public function testPurge()
    {
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->willReturn(1);

        $result = $this->store->purge('/');

        $this->assertTrue($result);
    }

    public function testCleanup()
    {
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->willReturn(1);

        $result = $this->store->cleanup();

        $this->assertTrue($result);
    }
}

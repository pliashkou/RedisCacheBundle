<?php

namespace Store;

use Client\RedisClient as Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class RedisStore implements StoreInterface
{
    protected $client;
    protected $digest_key_prefix;
    protected $metadata_key_prefix;
    protected $lock_key;
    protected $keyCache;

    public function __construct($client, $digest_key_prefix, $lock_key, $metadata_key_prefix)
    {
        $this->client = $client;
        $this->digest_key_prefix = $digest_key_prefix;
        $this->lock_key = $lock_key;
        $this->metadata_key_prefix = $metadata_key_prefix;
        $this->keyCache = new \SplObjectStorage();
    }

    /**
     * Locates a cached Response for the Request provided.
     *
     * @param Request $request A Request instance
     *
     * @return Response|null A Response instance, or null if no cache entry was found
     */
    public function lookup(Request $request)
    {
        $key = $this->getMetadataKey($request);
        $response = $this->client->get($key);
        if ($response) {
            return unserialize($response);
        }
        return null;
    }

    /**
     * Writes a cache entry to the store for the given Request and Response.
     *
     * Existing entries are read and any that match the response are removed. This
     * method calls write with the new list of cache entries.
     *
     * @param Request $request A Request instance
     * @param Response $response A Response instance
     *
     * @return string The key under which the response is stored
     */
    public function write(Request $request, Response $response)
    {
        // write the response body to the entity store if this is the original response
        $metadataKey = $this->getMetadataKey($request);
        $this->save($metadataKey, serialize($response));

        return $metadataKey;
    }

    /**
     * Invalidates all cache entries that match the request.
     *
     * @param Request $request A Request instance
     */
    public function invalidate(Request $request)
    {
        $modified = false;
        $newEntries = array();
        $key = $this->getMetadataKey($request);
        $metadata = $this->getMetadata($key);

        if ($metadata) {
            foreach ($metadata as $entry) {
                //We pass an empty body we only care about headers.
                $response = $this->recreateResponse($entry[1], null);
                if ($response->isFresh()) {
                    $response->expire();
                    $modified = true;
                    $newEntries[] = array($entry[0], $this->getResponseHeaders($response));
                } else {
                    $entries[] = $entry;
                }
            }
        }

        if ($modified) {
            if (false === $this->save($key, serialize($newEntries))) {
                throw new \RuntimeException('Unable to store the metadata.');
            }
        }
    }

    /**
     * Locks the cache for a given Request.
     *
     * @param Request $request A Request instance
     *
     * @return Boolean|string true if the lock is acquired, the path to the current lock otherwise
     */
    public function lock(Request $request)
    {
        return true;
    }

    /**
     * Releases the lock for the given Request.
     *
     * @param Request $request A Request instance
     *
     * @return Boolean False if the lock file does not exist or cannot be unlocked, true otherwise
     */
    public function unlock(Request $request)
    {
        return true;
    }

    /**
     * Returns whether or not a lock exists.
     *
     * @param Request $request A Request instance
     *
     * @return Boolean true if lock exists, false otherwise
     */
    public function isLocked(Request $request)
    {
        $metadataKey = $this->getMetadataKey($request);
        $result = $this->client->hget($this->lock_key, $metadataKey);
        return $result == 1;
    }

    /**
     * Purges data for the given URL.
     *
     * @param string $url A URL
     *
     * @return Boolean true if the URL exists and has been purged, false otherwise
     */
    public function purge($url)
    {
        $metadataKey = $this->getMetadataKey(Request::create($url));
        $result = $this->client->del($metadataKey);
        return $result == 1;
    }

    /**
     * Cleanups storage.
     */
    public function cleanup()
    {
        $result = $this->client->del($this->lock_key);
        return $result == 1;
    }

    /**
     * Returns a cache key for the given Request.
     *
     * @param Request $request A Request instance
     *
     * @return string A key for the given Request
     */
    private function getMetadataKey(Request $request)
    {
        if (isset($this->keyCache[$request])) {
            return $this->keyCache[$request];
        }
        return $this->keyCache[$request] = $this->metadata_key_prefix . sha1($request->getUri());
    }

    /**
     * Persists the Request HTTP headers.
     *
     * @param Request $request A Request instance
     *
     * @return array An array of HTTP headers
     */
    private function getRequestHeaders(Request $request)
    {
        return $request->headers->all();
    }

    /**
     * Returns content digest for $response.
     *
     * @param Response $response
     *
     * @return string
     */
    protected function generateContentDigestKey(Response $response)
    {
        return $this->digest_key_prefix . sha1($response->getContent());
    }

    private function save($key, $data)
    {
        return $this->client->set($key, $data);
    }

    /**
     * Gets all data associated with the given key.
     *
     * Use this method only if you know what you are doing.
     *
     * @param string $key The store key
     *
     * @return array An array of data associated with the key
     */
    private function getMetadata($key)
    {
        if (null === $entries = $this->load($key)) {
            return array();
        }
        return unserialize($entries);
    }

    /**
     * Loads data for the given key.
     *
     * @param string $key The store key
     *
     * @return string The data associated with the key
     */
    private function load($key)
    {
        $values = $this->client->get($key);
        return $values;
    }

    /**
     * Determines whether two Request HTTP header sets are non-varying based on
     * the vary response header value provided.
     *
     * @param string $vary A Response vary header
     * @param array $env1 A Request HTTP header array
     * @param array $env2 A Request HTTP header array
     *
     * @return Boolean true if the two environments match, false otherwise
     */
    private function requestsMatch($vary, $env1, $env2)
    {
        if (empty($vary)) {
            return true;
        }
        foreach (preg_split('/[\s,]+/', $vary) as $header) {
            $key = strtr(strtolower($header), '_', '-');
            $v1 = isset($env1[$key]) ? $env1[$key] : null;
            $v2 = isset($env2[$key]) ? $env2[$key] : null;
            if ($v1 !== $v2) {
                return false;
            }
        }
        return true;
    }

    /**
     * Persists the Response HTTP headers.
     *
     * @param Response $response A Response instance
     *
     * @return array An array of HTTP headers
     */
    private function getResponseHeaders(Response $response)
    {
        $headers = $response->headers->all();
        $headers['X-Status'] = array($response->getStatusCode());
        return $headers;
    }

    private function recreateResponse($headers, $body)
    {
        $status = $headers['X-Status'][0];
        unset($headers['X-Status']);
        return new Response($body, $status, $headers);
    }
}

<?php namespace MilkyThinking\CacheableEloquent;

use Illuminate\Cache\StoreInterface;
use Illuminate\Support\Facades\Cache as IlluminateCache;

class Cache implements StoreInterface
{
    protected static $instance;

    /**
     * @return Cache
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return IlluminateCache::get($key);
    }

    public function getMulti($keys)
    {
        $items = array();
        foreach ($keys as $key) {
            $item = $this->get($key);
            if ($item) {
                $items[$key] = $item;
            }
        }

        return $items;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        IlluminateCache::put($key, $value, $minutes);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return IlluminateCache::increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return IlluminateCache::decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        IlluminateCache::forever($key, $value);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        IlluminateCache::forget($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        IlluminateCache::flush();
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return IlluminateCache::getPrefix();
    }

}

<?php namespace MilkyThinking\CacheableEloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;

class Model extends IlluminateModel
{
    protected static $cacheable = [];

    protected static $cacheableDefault = [
        'prefix' => 'cacheable',
        'enable' => true,
        'version' => 1,
        'minutes' => 60,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function save(array $options = array())
    {
        $result = parent::save($options);
        if ($result && static::isCacheable()) {
            $identifyCacheKey = static::getIdentifyCacheKey(get_class($this), $this->getKey());
            Cache::getInstance()->put($identifyCacheKey, $this, static::getCacheable('minutes'));
        }

        return $result;
    }

    public function delete()
    {
        $result = parent::delete();
        if ($result && static::isCacheable()) {
            $identifyCacheKey = static::getIdentifyCacheKey(get_class($this), $this->getKey());
            Cache::getInstance()->forget($identifyCacheKey);
        }

        return $result;
    }

    public static function getIdentifyCacheKeys($modelClass, $ids)
    {
        $keys = array();
        foreach ($ids as $id) {
            $keys[$id] = static::getIdentifyCacheKey($modelClass, $id);
        }

        return $keys;
    }

    public static function getIdentifyCacheKey($modelClass, $id)
    {
        $cacheable = self::getCacheable();
        return implode(':', array($cacheable['prefix'], $modelClass, $cacheable['version'], $id));
    }

    public static function getCacheable($name = null)
    {
        $cacheable = array_merge(self::$cacheableDefault, static::$cacheable);
        if ($name) {
            return isset($cacheable[$name]) ? $cacheable[$name] : null;
        }

        return $cacheable;
    }

    public static function isCacheable()
    {
        return self::getCacheable('enable');
    }
}

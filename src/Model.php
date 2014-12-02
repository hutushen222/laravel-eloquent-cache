<?php namespace MilkyThinking\CacheableEloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;

class Model extends IlluminateModel
{

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

        $identifyCacheKey = static::getIdentifyCacheKey(get_class($this), $this->getKey());
        Cache::getInstance()->put($identifyCacheKey, $this, 60);

        return $result;
    }

    public function delete()
    {
        $result = parent::delete();

        if ($result) {
            $identifyCacheKey = static::getIdentifyCacheKey(get_class($this), $this->getKey());
            Cache::getInstance()->forget($identifyCacheKey);
        }

        return $result;
    }

    public static function getIdentifyCacheKeys($modelClass, $ids, $prefix = 'Cacheable')
    {
        $keys = array();
        foreach ($ids as $id) {
            $keys[$id] = static::getIdentifyCacheKey($modelClass, $id, $prefix);
        }

        return $keys;
    }

    public static function getIdentifyCacheKey($modelClass, $id, $prefix = 'Cacheable')
    {
        return implode(':', array($prefix, $modelClass, $id));
    }
}

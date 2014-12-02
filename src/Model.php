<?php namespace MilkyThinking\CacheableEloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Support\Facades\Cache;

class Model extends IlluminateModel
{

    public static function find($id, $columns = array('*'))
    {
        $modelClass = get_called_class();

        if ($columns === array('*')) {
            if (is_array($id)) {
                $result = new \Illuminate\Database\Eloquent\Collection();
                $identifyCacheKeys = static::identifyCacheKeys($modelClass, $id);
                $missIdentifyCacheKeys = array();
                foreach ($identifyCacheKeys as $modelId => $identifyCacheKey) {
                    $item = Cache::get($identifyCacheKey);
                    if (!$item) {
                        $missIdentifyCacheKeys[$modelId] = $identifyCacheKey;
                    } else {
                        $result[$modelId] = $item;
                    }
                }

                if ($missIdentifyCacheKeys) {
                    $missItems = parent::find(array_keys($missIdentifyCacheKeys));
                    foreach ($missItems as $missItem) {
                        $primaryKey = $missItem->primaryKey;
                        $identifyCacheKey  = $missIdentifyCacheKeys[$missItem->$primaryKey];
                        Cache::put($identifyCacheKey, $missItem, 60);
                    }
                    $result = $result->merge($missItems);
                }
            } else {
                $identifyCacheKey = static::identifyCacheKey($modelClass, $id);

                if (!($result = Cache::get($identifyCacheKey))) {
                    $result = parent::find($id, $columns);
                    if ($result) {
                        Cache::put($identifyCacheKey, $result, 60);
                    }
                }
            }
        } else {
            $result = parent::find($id, $columns);
        }

        return $result;
    }

    public function save(array $options = array())
    {
        $result = parent::save($options);

        $primaryKey = $this->primaryKey;
        $modelClass = get_class($this);
        $identifyCacheKey = static::identifyCacheKey($modelClass, $this->$primaryKey);
        Cache::put($identifyCacheKey, $this, 60);

        return $result;
    }

    protected static function identifyCacheKeys($modelClass, $ids, $prefix = 'CE')
    {
        $keys = array();
        foreach ($ids as $id) {
            $keys[$id] = static::identifyCacheKey($modelClass, $id, $prefix);
        }

        return $keys;
    }

    protected static function identifyCacheKey($modelClass, $id, $prefix = 'CE')
    {
        return implode(':', array($prefix, $modelClass, $id));
    }
}

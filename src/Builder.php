<?php namespace MilkyThinking\CacheableEloquent;

use Illuminate\Database\Eloquent\Builder as IlluminateBuilder;

class Builder extends IlluminateBuilder
{

    public function first($columns = array('*'))
    {
        $modelClass = $this->getModelClass();
        if (
            $modelClass::isCacheable()
            && $columns === array('*')
            && (is_null($this->query->columns) || $this->query->columns === array('*'))
            && count($this->query->wheres) === 1
            && $this->query->wheres[0]['type'] === 'Basic'
            && ($this->query->wheres[0]['column'] === $this->model->getKeyName() || $this->query->wheres[0]['column'] === $this->model->getQualifiedKeyName())
            && $this->query->wheres[0]['operator'] === '='
            && is_null($this->query->groups)
            && is_null($this->query->havings)
            && is_null($this->query->unions)
        ) {
            $identifyCacheKey = $modelClass::getIdentifyCacheKey($modelClass, $this->query->wheres[0]['value']);
            $model = Cache::getInstance()->get($identifyCacheKey);
            if (!$model) {
                $model = parent::first($columns);
                if ($model) {
                    Cache::getInstance()->put($identifyCacheKey, $model, $modelClass::getCacheable('minutes'));
                }
            }
        } else {
            $model = parent::first($columns);
        }

        return $model;
    }

    /**
     * Find multi-models by its primary keys.
     *
     * @param  array  $ids
     * @param  array  $columns
     *
     * @return \Illuminate\Database\Eloquent\Model|Collection|static
     */
    public function findMany($ids, $columns = array('*'))
    {
        if (empty($ids)) {
            return $this->model->newCollection();
        }

        $modelClass = $this->getModelClass();
        if (
            $modelClass::isCacheable()
            && $columns === array('*')
            && (is_null($this->query->columns) || $this->query->columns === array('*'))
        ) {
            $models = $this->model->newCollection();

            $identifyCacheKeys = $modelClass::getIdentifyCacheKeys($modelClass, $ids);
            $missIdentifyCacheKeys = array();

            $hitModels = Cache::getInstance()->getMulti($identifyCacheKeys);
            foreach ($identifyCacheKeys as $id => $identifyCacheKey) {
                if (isset($hitModels[$identifyCacheKey])) {
                    $models->add($hitModels[$identifyCacheKey]);
                } else {
                    $missIdentifyCacheKeys[$id] = $identifyCacheKey;
                }
            }

            if ($missIdentifyCacheKeys) {
                $missModels = parent::findMany(array_keys($missIdentifyCacheKeys));
                foreach ($missModels as $missModel) {
                    $identifyCacheKey = $missIdentifyCacheKeys[$missModel->getKey()];
                    Cache::getInstance()->put($identifyCacheKey, $missModel, $modelClass::getCacheable('minutes'));
                }
                $models = $models->merge($missModels);
            }
        } else {
            $models = parent::findMany($ids, $columns);
        }
        return $models;
    }

    protected function getModelClass()
    {
        return get_class($this->model);
    }
}

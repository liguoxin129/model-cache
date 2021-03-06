<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\EagerLoad;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Liguoxin129\ModelCache\CacheableInterface;
use Illuminate\Support\Arr;

class EagerLoaderBuilder extends Builder
{
    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        // First we will "back up" the existing where conditions on the query so we can
        // add our eager constraints. Then we will merge the wheres that were on the
        // query back to it in order that any where conditions might be specified.
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        $constraints($relation);

        // Once we have the results, we just match those back up to their parent models
        // using the relationship instance. Then we just return the finished arrays
        // of models which have been eagerly hydrated and are readied for return.
        return $relation->match(
            $relation->initRelation($models, $name),
            $this->getEagerModels($relation),
            $name
        );
    }

    protected function getEagerModels(Relation $relation)
    {
        $wheres = $relation->getQuery()
            ->getQuery()->wheres;
        $model = $relation->getModel();
        $column = sprintf('%s.%s', $model->getTable(), $model->getKeyName());

        if ($model instanceof CacheableInterface && $this->couldUseEagerLoad($wheres, $column)) {
            return $model::findManyFromCache($wheres[0]['values'] ?? []);
        }

        return $relation->getEager();
    }

    protected function couldUseEagerLoad(array $wheres, string $column): bool
    {
        return count($wheres) === 1
            && in_array(Arr::get($wheres[0], 'type'), [
                'In',
                'InRaw',
            ], true)
            && Arr::get($wheres[0], 'column') === $column
            && $this->isValidValues($wheres[0]['values'] ?? []);
    }

    protected function isValidValues(array $values): bool
    {
        foreach ($values as $value) {
            if (! is_int($value) && ! is_string($value)) {
                return false;
            }
        }
        return true;
    }
}

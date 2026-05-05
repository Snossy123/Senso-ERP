<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

trait Loggable
{
    public static function bootLoggable()
    {
        static::created(function (Model $model) {
            Activity::log(
                'crud',
                'created',
                'Created '.class_basename($model)." #{$model->id}",
                [],
                $model,
                'info',
                null,
                $model->toArray()
            );
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            // Don't log if only timestamps changed
            unset($changes['updated_at']);
            if (empty($changes)) {
                return;
            }

            $before = array_intersect_key($model->getOriginal(), $changes);

            Activity::log(
                'crud',
                'updated',
                'Updated '.class_basename($model)." #{$model->id}",
                ['changes' => array_keys($changes)],
                $model,
                'info',
                $before,
                $changes
            );
        });

        static::deleted(function (Model $model) {
            Activity::log(
                'crud',
                'deleted',
                'Deleted '.class_basename($model)." #{$model->id}",
                [],
                $model,
                'warning',
                $model->toArray(),
                null
            );
        });
    }
}

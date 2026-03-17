<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseBusinessRepository extends BaseRepository
{
    abstract protected function modelClass(): string;

    public function getModel()
    {
        return $this->modelClass();
    }

    public function queryForBusiness(int $businessId, array $with = []): Builder
    {
        return $this->modelClass()::query()
            ->with($with)
            ->where('business_id', $businessId);
    }

    public function findForBusiness(int $businessId, int $id, array $with = []): Model
    {
        return $this->queryForBusiness($businessId, $with)->whereKey($id)->firstOrFail();
    }

    public function createForBusiness(int $businessId, array $attributes): Model
    {
        return $this->modelClass()::query()->create(array_merge($attributes, [
            'business_id' => $businessId,
        ]));
    }

    public function updateRecord(Model $record, array $attributes): Model
    {
        $record->update($attributes);

        return $record;
    }

    public function deleteRecord(Model $record): void
    {
        $record->delete();
    }
}

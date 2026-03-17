<?php

namespace App\Services;

use App\Repositories\BaseBusinessRepository;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

abstract class BaseBusinessCrudService
{
    protected BaseBusinessRepository $repository;

    protected array $with = [];

    protected array $searchable = [];

    public function __construct(protected BusinessContext $businessContext)
    {
    }

    public function paginate(array $filters): array
    {
        $businessId = $this->resolveBusinessId($filters);
        $query = $this->repository->queryForBusiness($businessId, $this->with);

        foreach ($this->searchable as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'like', '%'.$filters[$field].'%');
            }
        }

        return [$businessId, $query];
    }

    public function searchableFilters(): array
    {
        return $this->searchable;
    }

    public function show(int $id, array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return $this->repository->findForBusiness($businessId, $id, $this->with);
    }

    public function create(array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);

        return $this->repository->createForBusiness($businessId, $this->payloadForCreate($data, $businessId))
            ->load($this->with);
    }

    public function update(int $id, array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);
        $record = $this->repository->findForBusiness($businessId, $id, $this->with);

        return $this->repository->updateRecord($record, $this->payloadForUpdate($data, $businessId, $record))
            ->refresh()
            ->load($this->with);
    }

    public function delete(int $id, array $data): Model
    {
        $businessId = $this->resolveBusinessId($data);
        $record = $this->repository->findForBusiness($businessId, $id, $this->with);
        $this->repository->deleteRecord($record);

        return $record;
    }

    protected function resolveBusinessId(array $data): int
    {
        return $this->businessContext->resolveBusinessId(isset($data['business_id']) ? (int) $data['business_id'] : null);
    }

    protected function currentUserId(): ?int
    {
        return $this->businessContext->currentUser()?->id;
    }

    protected function assertBelongsToBusiness(string $modelClass, int $businessId, ?int $id, string $field): void
    {
        if ($id === null) {
            return;
        }

        $exists = $modelClass::query()
            ->where('business_id', $businessId)
            ->whereKey($id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => 'The selected value is invalid for the current business.',
            ]);
        }
    }

    protected function payloadForCreate(array $data, int $businessId): array
    {
        unset($data['business_id']);

        return $data;
    }

    protected function payloadForUpdate(array $data, int $businessId, Model $record): array
    {
        unset($data['business_id']);

        return $data;
    }

    protected function nextDocumentNumber(string $modelClass, int $businessId, string $numberColumn, string $prefix): string
    {
        $sequence = $modelClass::query()
            ->where('business_id', $businessId)
            ->count() + 1;

        do {
            $candidate = sprintf('%s-%04d', $prefix, $sequence);
            $exists = $modelClass::query()
                ->where('business_id', $businessId)
                ->where($numberColumn, $candidate)
                ->exists();
            $sequence++;
        } while ($exists);

        return $candidate;
    }
}

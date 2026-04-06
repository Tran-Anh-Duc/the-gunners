<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Support\BusinessContext;

class CategoryService extends BaseBusinessCrudService
{
    protected array $searchable = ['name'];

    protected array $slugSearchable = ['name'];

    public function __construct(BusinessContext $businessContext, CategoryRepository $repository)
    {
        parent::__construct($businessContext);
        $this->repository = $repository;
    }

    public function paginate(array $filters): array
    {
        [$businessId, $query] = parent::paginate($filters);

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return [$businessId, $query];
    }

    protected function payloadForCreate(array $data, int $businessId): array
    {
        return array_merge(parent::payloadForCreate($data, $businessId), [
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}

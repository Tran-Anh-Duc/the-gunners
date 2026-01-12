<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait HasApiPagination
{
    /**
     * Phân trang + sort động theo request.
     *
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @param TransformerAbstract|null $transformer
     * @param array{
     *     column: string,
     *     order: string
     * } $defaultSort
     * @param int $defaultPerPage
     *
     * @return array{
     *     items: Collection|array,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int
     * }
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function paginate(Builder|\Illuminate\Database\Query\Builder $query, ?TransformerAbstract $transformer = null, array $defaultSort = ['column' => 'id', 'order' => 'asc'], int $defaultPerPage = 3): array
    {
        // merge $defaultSort  nếu như ko truyền , hoạc truyền la array
        $defaultSort = array_merge(
            ['column' => 'id', 'order' => 'asc'],
            $defaultSort
        );
        // Lấy param từ FE (request)
        $perPage = request()->get('per_page', $defaultPerPage);
        $page = request()->get('page', 1);
        $sortBy = request()->get('sort_by', $defaultSort['column']);
        $sortOrder = request()->get('sort_order', $defaultSort['order']);

        // Sort nếu có
        if ($sortBy) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginate
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection();


        if ($transformer) {
            $items = collect(
                $this->transformData($items, $transformer)['data']
            );
        }

        // Trả về chuẩn FE
        return [
            'items' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}

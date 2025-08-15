<?php

namespace App\Traits;

trait HasApiPagination
{
    /**
     * Phân trang, sort động dựa vào request
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param array $defaultSort ['column' => 'id', 'order' => 'asc']
     * @param int $defaultPerPage
     * @return array
     */
    public function paginate($query, array $defaultSort = ['column' => 'id', 'order' => 'asc'], int $defaultPerPage = 3): array
    {
        //dd(request()->all());
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

        // Trả về chuẩn FE
        return [
            'items' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}

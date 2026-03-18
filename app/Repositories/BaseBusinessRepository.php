<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseBusinessRepository extends BaseRepository
{
    /**
     * Trả về model class mà repository này quản lý.
     *
     * Các repository con chỉ cần khai báo class model, còn lớp nền sẽ lo
     * phần query và thao tác CRUD theo phạm vi business.
     */
    abstract protected function modelClass(): string;

    public function getModel()
    {
        return $this->modelClass();
    }

    public function queryForBusiness(int $businessId, array $with = []): Builder
    {
        // Đây là cổng vào chuẩn để mọi truy vấn đều bị khóa theo `business_id`.
        return $this->modelClass()::query()
            ->with($with)
            ->where('business_id', $businessId);
    }

    public function findForBusiness(int $businessId, int $id, array $with = []): Model
    {
        // Tìm theo ID nhưng vẫn nằm trong business hiện tại; sai scope sẽ trả 404.
        return $this->queryForBusiness($businessId, $with)->whereKey($id)->firstOrFail();
    }

    public function createForBusiness(int $businessId, array $attributes): Model
    {
        // Gắn `business_id` ở đây để controller/service không thể bỏ sót tenant scope.
        return $this->modelClass()::query()->create(array_merge($attributes, [
            'business_id' => $businessId,
        ]));
    }

    public function updateRecord(Model $record, array $attributes): Model
    {
        // Tách riêng hàm update để service có thể dùng thống nhất và dễ mở rộng về sau.
        $record->update($attributes);

        return $record;
    }

    public function deleteRecord(Model $record): void
    {
        // Hành vi xóa thực tế phụ thuộc model có SoftDeletes hay không.
        $record->delete();
    }
}

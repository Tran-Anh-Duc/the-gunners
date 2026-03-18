<?php


namespace App\Repositories;


abstract class BaseRepository
{
    const DESC = 'DESC';
    const ASC = 'ASC';

    /**
     * Model Eloquent mà repository đang thao tác.
     *
     * Đây là điểm chung cho toàn bộ hàm CRUD cơ bản trong lớp nền.
     */
    protected $model;

    public function __construct()
    {
        $this->setModel();
    }

    abstract public function getModel();

    /**
     * Khởi tạo model theo class mà repository con khai báo.
     *
     * Việc resolve qua container giúp repository dùng đúng instance model
     * và vẫn tương thích với cơ chế dependency injection của Laravel.
     */
    public function setModel()
    {
        $this->model = app()->make(
            $this->getModel()
        );
    }

    public function getAll()
    {
        // Trả toàn bộ dữ liệu, phù hợp cho các tác vụ nội bộ hoặc seed đơn giản.
        return $this->model->all();
    }

    public function find($id)
    {
        // Lấy theo khóa chính, trả về null nếu không tồn tại.
        $result = $this->model->find($id);

        return $result;
    }

    public function create($attributes = [])
    {
        // Tạo mới trực tiếp từ mảng thuộc tính đã được service/request chuẩn hóa.
        return $this->model->create($attributes);
    }

    public function update($id, $attributes = [])
    {
        // Chỉ cập nhật khi bản ghi tồn tại để tránh exception ở lớp repository nền.
        $result = $this->find($id);
        if ($result) {
            $result->update($attributes);
            return $result;
        }

        return false;
    }

    public function createOrupdate($id = null, $attributes = [])
    {
        // Hỗ trợ nhánh "có thì cập nhật, chưa có thì tạo mới" cho các flow cũ.
        $result = null;
        if ($id) {
            $result = $this->find($id);
        }

        if ($result) {
            $result->update($attributes);
            return $result;
        } else {
            return $this->create($attributes);
        }

        return false;
    }

    public function delete($id)
    {
        // Xóa mềm hay xóa cứng sẽ phụ thuộc vào model có dùng SoftDeletes hay không.
        $result = $this->find($id);
        if ($result) {
            $result->delete();

            return true;
        }

        return false;
    }

    public function findOne($condition)
    {
        // Duyệt tập điều kiện đơn giản dạng key/value để lấy bản ghi đầu tiên khớp.
        $query = $this->model;
        foreach ($condition as $key => $value) {
            $query = $query->where($key, $value);
        }
        return $query->first();
    }

    public function findMany($condition)
    {
        // Trả danh sách theo điều kiện và mặc định ưu tiên bản ghi mới nhất.
        $query = $this->model;
        foreach ($condition as $key => $value) {
            $query = $query->where($key, $value);
        }
        return $query
            ->orderBy('created_at', self::DESC)
            ->get();
    }

    public function where_has($relationship, $column, $value)
    {
        // Hỗ trợ truy vấn theo quan hệ mà không cần lặp lại `whereHas` ở nhiều nơi.
        $model = $this->model;
        $model = $model->whereHas($relationship, function ($query) use ($column, $value) {
            $query->where($column, $value);
        });
        return $model;
    }

    public function findOneDesc($condition)
    {
        // Giống `findOne()` nhưng ưu tiên bản ghi mới nhất theo `created_at`.
        $query = $this->model;
        foreach ($condition as $key => $value) {
            $query = $query->where($key, $value);
        }
        return $query
            ->orderBy('created_at', self::DESC)
            ->first();
    }

    public function findManySortColumn($condition, $colum, $sort)
    {
        // Trả danh sách theo điều kiện với cột sắp xếp được truyền động từ bên ngoài.
        $query = $this->model;
        foreach ($condition as $key => $value) {
            $query = $query->where($key, $value);
        }
        return $query
            ->orderBy($colum, $sort)
            ->get();
    }

    public function delete_field($field, $value)
    {
        // Xóa theo field dùng cho các tác vụ dọn dữ liệu hàng loạt.
        DB::beginTransaction();
        try {
            $this->model->where($field, $value)->delete();
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            return false;
        }
        return;
    }
}

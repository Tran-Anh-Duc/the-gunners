<?php


namespace App\Repositories;


abstract class BaseRepository
{
    const DESC = 'DESC';
    const ASC = 'ASC';
    protected $model;

    public function __construct()
    {
        $this->setModel();
    }

    abstract public function getModel();

    /**
     * Set model
     */
    public function setModel()
    {
        $this->model = app()->make(
            $this->getModel()
        );
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        $result = $this->model->find($id);

        return $result;
    }

    public function create($attributes = [])
    {
        return $this->model->create($attributes);
    }

    public function update($id, $attributes = [])
    {
        $result = $this->find($id);
        if ($result) {
            $result->update($attributes);
            return $result;
        }

        return false;
    }

    public function createOrupdate($id = null, $attributes = [])
    {
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
        $result = $this->find($id);
        if ($result) {
            $result->delete();

            return true;
        }

        return false;
    }

    public function findOne($condition)
    {
        $query = $this->model;
        foreach ($condition as $key => $value) {
            $query = $query->where($key, $value);
        }
        return $query->first();
    }

    public function findMany($condition)
    {
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
        $model = $this->model;
        $model = $model->whereHas($relationship, function ($query) use ($column, $value) {
            $query->where($column, $value);
        });
        return $model;
    }

    public function findOneDesc($condition)
    {
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

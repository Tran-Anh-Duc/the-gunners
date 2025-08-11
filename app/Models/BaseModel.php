<?php


namespace App\Models;



abstract class BaseModel extends Model
{
    const COLUMN_ID = 'id';
    const COLUMN_CREATED_AT = 'created_at';
    const COLUMN_CREATED_BY = 'created_by';
    const COLUMN_UPDATED_AT = 'updated_at';
    const COLUMN_UPDATED_BY = 'updated_by';

    protected static function boot()
    {
        parent::boot();
    }

    public function getTableColumns()
    {
        $result = [];
        $table_columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());

        foreach ($table_columns as $index => $column_name) {
            $column = new \stdClass();
            $column->column_name = $column_name;
            $result[$index] = $column;
        }

        return $result;
    }
}

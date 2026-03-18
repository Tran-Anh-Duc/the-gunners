<?php

namespace App\Http\Requests;

/**
 * Request dùng chung cho các API danh sách.
 *
 * Cung cấp bộ rule cơ bản cho:
 * - business scope
 * - phân trang
 * - sort
 *
 * Các module cần filter đặc thù sẽ merge thêm rule riêng ở request hoặc xử lý tiếp ở service.
 */
class BusinessIndexRequest extends BaseBusinessRequest
{
    /**
     * Rule cơ bản cho các API list.
     */
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }
}

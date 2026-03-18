<?php

namespace App\Http\Requests;

/**
 * Request tối giản cho các thao tác như show hoặc delete.
 *
 * Không validate thêm field nghiệp vụ; mục tiêu duy nhất là bảo đảm
 * request luôn có business scope rõ ràng trước khi vào service.
 */
class BusinessActionRequest extends BaseBusinessRequest
{
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
        ];
    }
}

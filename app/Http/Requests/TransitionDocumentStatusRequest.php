<?php

namespace App\Http\Requests;

/**
 * Request dùng chung cho các endpoint confirm hoặc cancel chứng từ.
 *
 * Các endpoint này thường không cần payload nghiệp vụ,
 * nhưng vẫn cần business scope để service tác động đúng document.
 */
class TransitionDocumentStatusRequest extends BaseBusinessRequest
{
    /**
     * Rule tối thiểu cho thao tác đổi trạng thái chứng từ.
     */
    public function rules(): array
    {
        return [
            'business_id' => $this->businessRules(),
        ];
    }
}

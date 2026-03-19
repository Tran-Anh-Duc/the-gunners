<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

abstract class BaseBusinessRequest extends BaseFormRequest
{
    protected bool $businessIdWasProvidedByClient = false;

    protected bool $businessIdResolvedFromTrustedContext = false;

    /**
     * Mọi request business-scoped đều cho phép đi tiếp ở tầng request.
     *
     * Phân quyền chi tiết đang được xử lý ở middleware JWT và permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Chuẩn hóa `business_id` trước khi bộ rule validate chạy.
     *
     * Mục tiêu:
     * - frontend có thể gửi qua body, query hoặc không gửi;
     * - request vẫn có `business_id` thống nhất để service xử lý đúng tenant.
     */
    protected function prepareForValidation(): void
    {
        /**
         * Chuẩn hóa `business_id` trước khi validate.
         *
         * Request có thể lấy `business_id` từ:
         * - body JSON
         * - query string
         * - membership hiện tại của user đang đăng nhập
         *
         * Nhiều API của MVP không bắt frontend truyền `business_id` ở mọi lần gọi,
         * nên bước merge này giúp request nào cũng có tenant scope rõ ràng.
         */
        // Tự bổ sung `business_id` để service và repository luôn làm việc trong cùng một phạm vi business.
        $businessId = $this->input('business_id', $this->query('business_id'));
        $this->businessIdWasProvidedByClient = $businessId !== null;

        if ($businessId === null) {
            if (app()->bound('jwt_business_id')) {
                $businessId = app('jwt_business_id');
                $this->businessIdResolvedFromTrustedContext = true;
            } elseif (app()->bound('jwt_active_membership')) {
                $businessId = app('jwt_active_membership')->business_id;
                $this->businessIdResolvedFromTrustedContext = true;
            } elseif (app()->bound('jwt_user')) {
                $businessId = app('jwt_user')->activeBusinessMemberships()->value('business_id');
                $this->businessIdResolvedFromTrustedContext = $businessId !== null;
            }
        }

        if ($businessId !== null) {
            $this->merge(['business_id' => (int) $businessId]);
        }
    }

    /**
     * Sinh rule validate dùng chung cho field `business_id`.
     *
     * @param  bool  $required  Có bắt buộc `business_id` phải xuất hiện trong request hay không
     * @return array<int, mixed>
     */
    protected function businessRules(bool $required = false): array
    {
        /**
         * Rule dùng chung cho `business_id`.
         *
         * Tách riêng helper này để các request con tái sử dụng,
         * tránh lặp lại cùng một đoạn validate tenant scope ở nhiều file.
         */
        // Tất cả request có phạm vi tenant/business đều nên dùng chung rule này.
        $rules = [
            $required ? 'required' : 'nullable',
            'integer',
        ];

        if ($this->businessIdWasProvidedByClient || ! $this->businessIdResolvedFromTrustedContext) {
            $rules[] = Rule::exists('businesses', 'id');
        }

        return $rules;
    }
}

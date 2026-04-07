<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessSequence;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Sinh mã tăng dần theo từng business và scope.
     *
     * Nếu scope chưa có row sequence, service sẽ tự khởi tạo row đầu tiên.
     */
    public function nextScopedCode(
        int $businessId,
        string $scope,
        string $defaultPrefix,
        int $padding = 4,
        bool $prependBusinessCode = false,
    ): string {
        return DB::transaction(function () use ($businessId, $scope, $defaultPrefix, $padding, $prependBusinessCode) {
            $sequence = $this->lockSequence($businessId, $scope, $defaultPrefix);
            $sequence->current_value++;
            $sequence->save();

            $businessCode = null;

            if ($prependBusinessCode) {
                $businessCode = Business::query()
                    ->whereKey($businessId)
                    ->value('code');
            }

            return $this->formatCode(
                $businessCode,
                $sequence->prefix,
                $sequence->current_value,
                $padding,
                $prependBusinessCode,
            );
        });
    }

    public function nextProductSku(int $businessId): string
    {
        return $this->nextScopedCode($businessId, 'product.sku', $this->randomLetters(3), 6, true);
    }

    protected function lockSequence(int $businessId, string $scope, string $defaultPrefix): BusinessSequence
    {
        $sequence = BusinessSequence::query()
            ->where('business_id', $businessId)
            ->where('scope', $scope)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            return $sequence;
        }

        // Khi scope được dùng lần đầu, khóa row business để tránh hai request cùng tạo sequence.
        Business::query()
            ->whereKey($businessId)
            ->lockForUpdate()
            ->firstOrFail();

        $sequence = BusinessSequence::query()
            ->where('business_id', $businessId)
            ->where('scope', $scope)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            return $sequence;
        }

        return BusinessSequence::query()->create([
            'business_id' => $businessId,
            'scope' => $scope,
            'prefix' => $this->normalizePrefix($defaultPrefix),
            'current_value' => 0,
        ]);
    }

    protected function formatCode(
        ?string $businessCode,
        string $prefix,
        int $currentValue,
        int $padding,
        bool $prependBusinessCode,
    ): string {
        $parts = [$this->normalizePrefix($prefix)];

        if ($prependBusinessCode) {
            array_unshift($parts, $this->normalizeBusinessCode((string) $businessCode));
        }

        $parts[] = str_pad((string) $currentValue, $padding, '0', STR_PAD_LEFT);

        return implode('-', $parts);
    }

    protected function normalizePrefix(string $prefix): string
    {
        $normalized = strtoupper(trim($prefix));
        $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?? '';

        return $normalized !== '' ? $normalized : 'SEQ';
    }

    protected function normalizeBusinessCode(string $businessCode): string
    {
        $normalized = strtoupper(trim($businessCode));
        $normalized = preg_replace('/[^A-Z0-9]+/', '-', $normalized) ?? '';
        $normalized = preg_replace('/-+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'BUS';
    }

    protected function randomLetters(int $length): string
    {
        $letters = '';

        for ($index = 0; $index < $length; $index++) {
            $letters .= chr(random_int(65, 90));
        }

        return $letters;
    }
}

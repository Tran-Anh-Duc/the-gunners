<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransform extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @param User $entry
     * @return array
     */
    public function transform(User $entry): array
    {

        return [
            'id' => $entry->id,
            'department_id' => $entry->department_id,
            'status_id' => $entry->status_id,
            'name' => $entry->name,
            'email' => $entry->email,
            'phone' => $entry->phone,
            'avatar' => $entry->avatar,
            'role' => $entry->role,
            'last_login_at' => $entry->last_login_at,
            'change_password_at' => $entry->change_password_at,
            'name_department' => $entry->department->name ?? '',
            'name_status' => $entry->status->name ?? '',
        ];
    }

}

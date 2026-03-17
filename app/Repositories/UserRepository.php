<?php

namespace App\Repositories;


use App\Helpers\JwtHelper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDepartment;
use App\Repositories\BaseRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;


class UserRepository extends BaseRepository
{
    //use CaculatePriceWareHouseTrait;
    use ApiResponse;
    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function getModel()
    {
        return User::class;
    }

    /* Cập nhật bộ phận của nhân viên */

    public function update_user_department($data,$id)
    {
        /*kiem tra tinh ton  cua ban ghi*/
        $dataRecord = UserDepartment::find($id);
        if (!$dataRecord){
            $dataResponse = [
                'status' => 422,
            ];
            return  $dataResponse;
        }

        /*kiem tra neu action_type = 2 , update */
        if (!empty($data) and $data != ''){

            if ($data['action_type'] != 2){
                $dataResponse = [
                    'status' => 422,
                ];
                return  $dataResponse;
            }

            /*kiem tra du lieu trong bang user_department $dataRecord */
            $fields = [
                'assigned_at',
                'is_main',
                'position',
                'ended_at',
            ];

            $dataUpdate = [];
            foreach ($fields as $field){
                if (isset($data[$field]) and $data[$field] != '' and $data[$field] != null){
                    $dataUpdate[$field] = $data[$field];
                }
            }

            if (array_key_exists('is_main', $dataUpdate)) {
                $dataUpdate['is_main'] = filter_var($dataUpdate['is_main'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            /*truong hop chon bo phan lam chinh is_main = 1 => se up tat ca cac bo phan cua user
                do ve 0 => roi moi update bo phan hien tai lam bo phan chinh
            */
            if (!empty($dataUpdate['is_main'])) {
                UserDepartment::query()
                    ->where('user_id', $dataRecord->user_id)
                    ->where('id', '<>', $id)
                    ->update(['is_main' => 0]);
            }

            $dataRecord->update($dataUpdate);

            $dataResponse = [
                'status' => 200,
                'data' => $dataRecord,
            ];

        }else{
            $dataResponse = [
                'status' => 422,
            ];
        }

        return $dataResponse;

    }

    public function create_user_department($data,$id)
    {
        /*kiem tra user*/
        $user = User::find($id);

        if (!$user){
            $dataResponse = [
                'status' => 422,
            ];
            return  $dataResponse;
        }


        /*kiem tra neu action_type = 1 , them moi */
        if (!empty($data) and $data != ''){

            if ($data['action_type'] != 1){
                $dataResponse = [
                    'status' => 422,
                ];
                return  $dataResponse;
            }

            $dataCreate = [
                'user_id' => $id,
                'department_id' => $data['department_id'] ?? '',
                'is_main' => filter_var($data['is_main'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                'position' => $data['position'] ?? null,
                'assigned_at' => $data['assigned_at'] ?? now(),
                'ended_at' => $data['ended_at'] ?? null,
            ];
            $resultData = UserDepartment::create($dataCreate);

            $dataResponse = [
                'status' => 200,
                'data' => $resultData,
            ];

        }else{
            $dataResponse = [
                'status' => 422,
            ];
        }

        return  $dataResponse;
    }

    public function registerAuth($data)
    {
        try {
            return  DB::transaction(function () use ($data) {
                $userData = $this->extractUserAttributes($data);
                $userData['password'] = Hash::make($userData['password']);

                $result = User::query()->create($userData);
                $token = JwtHelper::generateToken($result);
                return [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JwtHelper::ttl(),
                ];
            });
        } catch (Throwable $e) {
            return $this->errorResponse(
                __('messages.register.action_created_failed'),
                'create_failed',
                Controller::ERRORS,
                ''
            );
        }
    }

    public function loginUser($data): JsonResponse|array
    {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $queryUser = $this->model::query();

        $user = $queryUser->where('email', $email)->first();


        if (
            empty($user)
            || !Hash::check($password, $user->password)
            || ! $user->is_active
        ) {
            throw ValidationException::withMessages([
                'email' => __('messages.user.user_login_failed'),
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $token = JwtHelper::generateToken($user);
        return [
            'access_token'=>$token,
            'token_type'=>'bearer',
            'expires_in'=>JwtHelper::ttl()
        ];
    }

    public function getListAllUser($search)
    {
        try {
            $name = $search['name'] ?? '';
            $email = $search['email'] ?? '';
            $role = $search['role'] ?? '';
            $phone = $search['phone'] ?? '';

            $query = $this->model::query();

            if(!empty($name) and $name != ''){
                $query->where('name', 'like', '%'.$name.'%');
            }
            if(!empty($email) and $email != ''){
                $query->where('email', 'like', '%'.$email.'%');
            }
            if(!empty($role) and $role != ''){
                $query->where('role', 'like', '%'.$role.'%');
            }
            if(!empty($phone) and $phone != ''){
                $query->where('phone', 'like', '%'.$phone.'%');
            }

            return $query->with(
                'department:id,name',
                'status:id,name',
            );
        }catch (Throwable $e){
              return $this->errorResponse(
                  message: ('messages.action_list_failed'),
                  code:  $search,
                  httpStatus: Controller::ERRORS,
              );
        }
    }

    public function showUserById(int $id)
    {
        try {
            $query = $this->model::query();
            return $query->with('department:id,name','status:id,name')
                         ->findOrFail($id);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse(
                message: __('messages.user.user_info_failed'),
                code:'messages.action_list_failed'.$id,
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }

    public function createUser(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $userData = $this->extractUserAttributes($data, true);
                $userData['password'] = Hash::make($userData['password']);
                $userData['is_active'] = $userData['is_active'] ?? true;

                $user = User::query()->create($userData);
                $this->syncUserRoles($user, $data);

                return [
                    'status' => 200,
                    'data' => $this->loadUserRelations($user),
                ];
            });
        } catch (Throwable $e) {
            return [
                'status' => 422,
            ];
        }
    }

    public function updateUser(array $data, int $id): array
    {
        try {
            return DB::transaction(function () use ($data, $id) {
                $user = User::query()->find($id);

                if (! $user) {
                    return [
                        'status' => 404,
                    ];
                }

                $userData = $this->extractUserAttributes($data, true);

                if (array_key_exists('password', $userData)) {
                    $userData['password'] = Hash::make($userData['password']);
                }

                if ($userData !== []) {
                    $user->update($userData);
                }

                $this->syncUserRoles($user, $data);

                return [
                    'status' => 200,
                    'data' => $this->loadUserRelations($user),
                ];
            });
        } catch (Throwable $e) {
            return [
                'status' => 422,
            ];
        }
    }

    public function destroyUser(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $user = User::query()->find($id);

                if (! $user) {
                    return [
                        'status' => 404,
                    ];
                }

                $user->roles()->detach();
                $user->permissions()->detach();
                $user->delete();

                return [
                    'status' => 200,
                    'data' => $user,
                ];
            });
        } catch (Throwable $e) {
            return [
                'status' => 422,
            ];
        }
    }

    protected function extractUserAttributes(array $data, bool $allowManagementFields = false): array
    {
        $fields = [
            'name',
            'email',
            'password',
        ];

        if ($allowManagementFields) {
            $fields = array_merge($fields, [
                'phone',
                'avatar',
                'role',
                'is_active',
                'department_id',
                'status_id',
                'change_password_at',
            ]);
        }

        $userData = Arr::only($data, $fields);

        if (array_key_exists('password', $userData) && blank($userData['password'])) {
            unset($userData['password']);
        }

        return $userData;
    }

    protected function syncUserRoles(User $user, array $data): void
    {
        $resolvedRoleIds = [];

        if (! empty($data['role_ids']) && is_array($data['role_ids'])) {
            $resolvedRoleIds = Role::query()
                ->whereIn('id', $data['role_ids'])
                ->pluck('id')
                ->all();
        } elseif (! empty($data['role'])) {
            $resolvedRoleIds = Role::query()
                ->where('name', $data['role'])
                ->pluck('id')
                ->all();
        }

        if ($resolvedRoleIds === []) {
            return;
        }

        $user->roles()->sync($resolvedRoleIds);

        $primaryRoleName = Role::query()
            ->where('id', $resolvedRoleIds[0])
            ->value('name');

        if ($primaryRoleName !== null && $user->role !== $primaryRoleName) {
            $user->forceFill(['role' => $primaryRoleName])->save();
        }
    }

    protected function loadUserRelations(User $user): User
    {
        return $user->fresh(['department:id,name', 'status:id,name', 'roles:id,name']) ?? $user;
    }


}

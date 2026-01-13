<?php

namespace App\Repositories;


use App\Http\Controllers\Controller;
use App\Models\UserStatus;
use App\Repositories\BaseRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;


class UserStatusRepository extends BaseRepository
{
    use ApiResponse;

    public function __construct(UserStatus $userStatus)
    {
        $this->model = $userStatus;
    }

    public function getModel()
    {
        // TODO: Implement getModel() method.
    }

    public function getAllListUserStatus($search)
    {
        try {
            return $this->model::query()->where('slug','like', '%'.normalize_slug_search($search).'%');
        }catch (\Exception $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.list_failed'),
                code: 'list_failed',
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }

    public function storeUserStatus($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $data['slug'] = generate_unique_slug(UserStatus::class,$data['name'],'slug' );
                return $this->model::query()->create($data);
            });

        }catch (\Throwable $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.create_failed'),
                code: 'store_failed',
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }


    public function ShowUserStatusById($id)
    {
        try {
            return $this->model::query()->findOrFail($id);
        }catch (ModelNotFoundException $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.user_status_not_found'),
                code: 'users_status_not_found'.'_'.$id,
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }

    public function updateUserStatus($data,$id)
    {
        try {
            if (array_key_exists('name', $data)) {
                $data['slug'] = generate_unique_slug(UserStatus::class,$data['name'],'slug',$id);
            }
            return DB::transaction(function () use ($data,$id) {
                $query = $this->model::query()->findOrFail($id);
                $query->update($data);
                return $query->refresh();
            });
        }catch (ModelNotFoundException $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.user_status_not_found'),
                code: 'users_status_not_found'.'_'.$id,
                httpStatus: Controller::ERRORS
            );
        }catch (\Throwable $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.update_failed'),
                code: 'update_failed'.'_'.$id,
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }

    public function destroyUserStatusById($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $query = $this->model::query()->withCount('users')->findOrFail($id);

                if ($query->users_count > 0) {
                    return $this->errorResponse(
                        message: __('messages.users_status.cannot_delete_in_use'),
                        code: 'status_in_use',
                        httpStatus: Controller::ERRORS,
                    );
                }
                $query->delete();
                return $query->refresh();
            });
        }catch (ModelNotFoundException $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.user_status_not_found'),
                code: 'users_status_not_found'.'_'.$id,
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }catch (\Throwable $e){
            report($e);
            return $this->errorResponse(
                message: __('messages.users_status.delete_failed'),
                code: 'delete_failed'.'_'.$id,
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }

    public function restoreUserStatusById($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $query = $this->model->onlyTrashed()->findOrFail($id);
                $query->restore();
                return $query->refresh();
            });
        }catch (ModelNotFoundException $e){
            report($e);
            return $this->errorResponse(
              message: __('messages.users_status.user_status_not_found'),
              code: 'restore_failed'.'_'.$id,
              httpStatus: Controller::ERRORS,
              data: ''
            );
        }catch (\Throwable $e){
            report($e);
            return $this->errorResponse(
              message: __('messages.users_status.restore_failed'),
              code: 'restore_failed'.'_'.$id,
              httpStatus: Controller::ERRORS,
              data: ''
            );
        }
    }
}

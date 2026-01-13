<?php

namespace App\Repositories;


use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Role;
use App\Models\UserStatus;
use App\Repositories\BaseRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use function Termwind\ValueObjects\pr;


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

        }catch (\Exception $e){ 
            return $this->errorResponse(
                message: __('messages.user_status.create_failed'),
                code: 'store_failed',
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }


    public function ShowUserStatusByID($id)
    {
        try {
            return $this->model::query()->findOrFail($id);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse(
                message: __('messages.users_status.user_status_not_found'),
                code: 'users_status_not_found'.'_'.$id,
                httpStatus: Controller::ERRORS,
                data: ''
            );
        }
    }

    public function UpdateRoleData($data,$id)
    {
        if ((!empty($data) and $data !='') and (!empty($id) and $id !='') ){

            $allowedFields = ['name','description'];
            $dataUpdate = [];

            foreach ($allowedFields as $value){
                if (array_key_exists($value,$data)){
                    $dataUpdate[$value] = $data[$value];
                }
            }

            $findData = UserStatus::query()->find($id);
            $findData->update($dataUpdate);

            $dataResponse = [
                'status' => 200,
                'data' => $findData,
            ];

        }elseif ($data == '' || $id == '')
        {
            $dataResponse = [
                'status' => 422,
            ];
        }

        return $dataResponse;
    }



}

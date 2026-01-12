<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActionRequest;
use App\Http\Requests\UpdateActionRequest;
use App\Models\Action;
use App\Repositories\ActionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use Throwable;
use function Carbon\this;

class ActionController extends Controller
{
    use ApiResponse;
    use HasApiPagination;
    protected $actionRepository;

    public function __construct(ActionRepository $actionRepository)
    {
        $this->actionRepository = $actionRepository;
    }

    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        try {
            //$get_data = Action::query()->get()->toArray();
            $query = Action::query();

            // Chỉ cần gọi trait, truyền query builder
            $data = $this->paginate($query);
            return $this->successResponse(
                __('messages.action_list'),
                'action_list',
                Controller::HTTP_OK,
                $data,
            );
        }catch (\Exception $e)
        {
            return $this->errorResponse(
                __('messages.action_failed'),
                'action_failed',
                Controller::ERRORS
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreActionRequest $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {
            $getData = $this->actionRepository->store($data);
            if($getData['status'] == 422){
                return $this->errorResponse(
                    __('messages.action_failed'),
                    'action_failed',
                    Controller::ERRORS,

            );
            }elseif($getData['status'] == 200){
                $resultData = $getData['data'];
                DB::commit();
                return $this->successResponse(
                    __('messages.action_list'),
                    'action_list',
                    Controller::HTTP_OK,
                    $resultData,
                 );
            }
        }catch (\Exception $e){
            DB::rollBack();
            return $this->errorResponse(
                __('messages.action_failed'),
                'action_failed',
                Controller::ERRORS,
                '',
            );
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $findData = $this->actionRepository->show($id);
            if(!empty($findData) and $findData['status'] == 200){
                return $this->successResponse(
                    __('messages.action_list'),
                    'action_list',
                    Controller::HTTP_OK,
                    $findData,
                );
            }elseif(!empty($findData) and $findData['status'] != 200) {
                return $this->errorResponse(
                    __('messages.action_failed'),
                    'action_failed',
                    Controller::HTTP_UNPROCESSABLE_ENTITY,
                    '',
                 );
            }
            return $findData;
        }catch (\Exception $e)
        {
            \Log::error($e);
            return $this->errorResponse(
                $e->getMessage(),
                'action_failed',
                500,
                '',
            );
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActionRequest $request, string $id)
    {
        $data = $request->all();
        DB::beginTransaction();
        try {

            $getData = $this->actionRepository->updateAction($data,$id);
            if(!empty($getData) and $getData['status'] == 200){
                DB::commit();
                return $this->successResponse(
                    __('messages.update_success'),
                    'action_list',
                    Controller::HTTP_OK,
                    $getData,
                );
            }elseif(!empty($getData) and $getData['status'] != 200){
                return $this->errorResponse(
                    __('messages.update_failed'),
                    'action_failed',
                    Controller::HTTP_UNPROCESSABLE_ENTITY,
                    '',
                 );
            }
        }catch (\Exception $e){
            \Log::error($e);
            DB::rollBack();
            return $this->errorResponse(
                $e->getMessage(),
                'update_failed',
                500,
                '',
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {

            if (!empty($id) && $id != ''){
                $action = Action::query()->where('id',$id)->first();
                $action->delete();
                DB::commit();
                return $this->successResponse(
                    __('messages.delete_success'),
                    'delete_success',
                    Controller::HTTP_OK,
                    $action,
                );
            }else{
                return $this->errorResponse(
                    __('messages.update_failed'),
                    'delete_failed',
                    Controller::HTTP_UNPROCESSABLE_ENTITY,
                    '',
                 );
            }

        }catch (\Exception $e){
            \Log::error($e);
            DB::rollBack();
            return $this->errorResponse(
                $e->getMessage(),
                'delete_failed',
                500,
                '',
            );
        }

    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $action = Action::withTrashed()->find($id);
            if ($action) {
                $action->restore();
                DB::commit();
                return $this->successResponse(
                    __('messages.successful_recovery'),
                    'successful_recovery',
                    Controller::HTTP_OK,
                    $action,
                );
            }else{
                return $this->errorResponse(
                    __('messages.restore_failed'),
                    'restore_failed',
                    Controller::HTTP_UNPROCESSABLE_ENTITY,
                    '',
                 );
            }
        }catch (\Exception $e){
            \Log::error($e);
            DB::rollBack();
            return $this->errorResponse(
                $e->getMessage(),
                'restore_failed',
                500,
                '',
            );
        }
    }
}

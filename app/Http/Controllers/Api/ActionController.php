<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActionRequest;
use App\Models\Action;
use App\Repositories\ActionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
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
                Controller::ERRORS,
                $get_data,

            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActionRequest $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {
            $getData = $this->actionRepository->store($data);
            if($getData['status'] == 422){
                return $this->successResponse(
                    __('messages.action_failed'),
                    'action_failed',
                    Controller::ERRORS,
                    $get_data,

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

            return $findData;
        }catch (\Exception $e)
        {
            \Log::error($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 500); // HTTP 500
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

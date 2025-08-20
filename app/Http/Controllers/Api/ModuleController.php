<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Role;
use App\Repositories\ModuleRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use function Carbon\this;

class ModuleController extends Controller
{
    use ApiResponse;
    use HasApiPagination;
    protected $moduleRepository;

    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }


    public function index()
    {
        try {
            $query = $this->moduleRepository->getList();
            $data = $this->paginate($query['data']);

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
                '',
            );
        }
    }



}

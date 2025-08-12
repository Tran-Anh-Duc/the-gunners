<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Repositories\ActionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActionController extends Controller
{
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

        $get_data = Action::query()->get()->toArray();
        $response = [
            Controller::STATUS => Controller::HTTP_OK,
            Controller::MESSAGE => Controller::SUCCESS,
            Controller::DATA => $get_data,
        ];
        return response()->json($response);

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
    public function store(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {

            $getData = $this->actionRepository->store($data);
            echo '<pre>';
            print_r($getData);
            echo '</pre>';
            die();
            DB::commit();
            $response = [
                Controller::STATUS => Controller::HTTP_OK,
                Controller::MESSAGE => Controller::SUCCESS,
                Controller::DATA => $get_data,
            ];

            return response()->json($response);
        }catch (\Exception $e){
            DB::rollBack();
            $response = [
                Controller::STATUS => Controller::HTTP_UNPROCESSABLE_ENTITY,
                Controller::MESSAGE => Controller::ERRORS,
                Controller::DATA => $e->getMessage(),
            ];
        }


    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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

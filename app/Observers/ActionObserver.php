<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class ActionObserver
{
    public function created(Action $action)
    {
        try {
            $modules = Module::all();

            DB::transaction(function () use ($modules, $action) {
                foreach ($modules as $module) {
                    try {
                        // Khóa các dòng phù hợp trong DB để tránh tạo trùng
                        $permission = Permission::where('module_id', $module->id)
                            ->where('action_id', $action->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$permission) {
                            $permission = Permission::create([
                                'module_id' => $module->id,
                                'action_id' => $action->id,
                                'name'      => "{$module->name}_{$action->name}"
                            ]);
                        }

                        logger()->info('Permission handled in ActionObserver@created', [
                            'module_id'     => $module->id,
                            'action_id'     => $action->id,
                            'permission_id' => $permission->id ?? null
                        ]);

                    } catch (\Illuminate\Database\QueryException $e) {
                        // Duplicate key (unique constraint violation)
                        if ($e->getCode() == '23000') {
                            $permission = Permission::where('module_id', $module->id)
                                ->where('action_id', $action->id)
                                ->first();

                            logger()->warning('Duplicate permission handled in ActionObserver@created', [
                                'module_id'     => $module->id,
                                'action_id'     => $action->id,
                                'permission_id' => $permission->id ?? null
                            ]);
                        } else {
                            throw $e; // rollback nếu lỗi khác
                        }
                    }
                }
            });
        } catch (\Throwable $e) {

            \Log::error("Error in ActionObserver@created: " . $e->getMessage());
        }
    }

}

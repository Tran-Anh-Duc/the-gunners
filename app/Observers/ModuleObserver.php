<?php

namespace App\Observers;

use App\Models\Module;
use App\Models\Action;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class ModuleObserver
{
    public function created(Module $module)
    {
        try {
            $actions = Action::all();

            DB::transaction(function () use ($actions, $module) {
                foreach ($actions as $action) {
                    try {
                        // Khóa các dòng phù hợp trong DB để ngăn request khác tạo trùng
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

                        // Log phục vụ debug
                        logger()->info('Permission handled', [
                            'module_id'     => $module->id,
                            'action_id'     => $action->id,
                            'permission_id' => $permission->id ?? null
                        ]);

                    } catch (\Illuminate\Database\QueryException $e) {
                        // Nếu lỗi là do trùng UNIQUE constraint
                        if ($e->getCode() == '23000') {
                            $permission = Permission::where('module_id', $module->id)
                                ->where('action_id', $action->id)
                                ->first();

                            logger()->warning('Permission duplicate handled', [
                                'module_id'     => $module->id,
                                'action_id'     => $action->id,
                                'permission_id' => $permission->id ?? null
                            ]);
                        } else {
                            // Lỗi khác: ném ra để transaction rollback
                            throw $e;
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::error("Error in ModuleObserver@created: " . $e->getMessage());
            // Không throw tiếp để tránh chặn việc tạo module
        }
    }

    /**
     * Khi Module được cập nhật
     */
    public function updated(Module $module)
    {
        try {
            // Nếu tên module thay đổi, thì update lại name của permission

            if ($module->isDirty('name')) {
                $actions = \App\Models\Action::all();

                foreach ($actions as $action) {
                    $permission = Permission::where('module_id', $module->id)
                        ->where('action_id', $action->id)
                        ->first();

                    if ($permission) {
                        $permission->update([
                            'name' => "{$module->name}_{$action->name}"
                        ]);

                        logger()->info('Permission updated', [
                            'module_id'     => $module->id,
                            'action_id'     => $action->id,
                            'permission_id' => $permission->id
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error in ModuleObserver@updated: " . $e->getMessage());
        }
    }
}

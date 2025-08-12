<?php

namespace App\Observers;

use App\Models\Module;
use App\Models\Action;
use App\Models\Permission;

class ModuleObserver
{
    public function created(Module $module)
    {
        try {
            $actions = Action::all();
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    [
                        'module_id' => $module->id,
                        'action_id' => $action->id
                    ],
                    [
                        'name' => "{$module->name}_{$action->name}"
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Log::error("Error in ModuleObserver@created: " . $e->getMessage());
            // Không throw tiếp để tránh làm lỗi create module
        }
    }
}

<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Module;
use App\Models\Permission;

class ActionObserver
{
    public function created(Action $action)
    {
        try {
            $modules = Module::all();
            foreach ($modules as $module) {
                Permission::firstOrCreate(
                    [
                        'module_id' => $module->id,
                        'action_id' => $action->id,
                    ],
                    [
                        'name' => "{$module->name}_{$action->name}",
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Log::error("Error in ActionObserver@created: " . $e->getMessage());

        }
    }

}

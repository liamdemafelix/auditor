<?php

/**
 * demafelix/auditor
 * A simplified audit trail recorder for Laravel.
 *
 * @author Liam Demafelix <ldemafelix@protonmail.ch>
 * @license MIT Open Source License
 */

namespace Demafelix\Auditor\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AuditorServiceProvider extends ServiceProvider
{
    /**
     * Service provider boot() method.
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/auditor.php' => config_path('auditor.php')], 'config');
        $this->publishes([__DIR__ . '/../migrations/' => database_path('migrations')], 'migrations');

        // Loop through models and start observing for changes
        foreach (config('auditor.models') as $model) {
            if (class_exists($model)) {
                // Section: entry creation
                $model::created(function ($me) use ($model) {
                    $this->registerCreate($me, $model);
                });

                // Section: entry update
                $model::updated(function ($me) use ($model) {
                    $this->registerUpdate($me, $model);
                });

                // Section: entry deletion
                $model::deleted(function ($me) use ($model) {
                    $this->registerDelete($me, $model);
                });
            }
        }
    }

    /**
     * Service provider register() method.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/auditor.php', 'auditor');
    }

    /**
     * Strips specified data keys from the trail.
     *
     * @param $model
     * @param $data
     * @return array
     */
    protected function strip($model, $data)
    {
        // Initialize an instance of $model
        $instance = new $model();

        // Convert the data to an array
        $data = (array) $data;

        // Start stripping
        $globalDiscards = (!empty(config('auditor.global_discards'))) ? config('auditor.global_discards') : [];
        $modelDiscards = (!empty($instance->discarded)) ? $instance->discarded : [];
        foreach ($data as $key => $value) {
            // Check the model-specific discards
            if (in_array($key, $modelDiscards)) {
                unset($data[$key]);
            }

            // Check global discards
            if (!empty($globalDiscards)) {
                if (in_array($key, $globalDiscards)) {
                    unset($data[$key]);
                }
            }
        }

        // Return
        return $data;
    }

    /**
     * Generates the data to store.
     *
     * @param $action
     * @param array $old
     * @param array $new
     * @return false|string
     */
    protected function generate($action, $old = [], $new = [])
    {
        $data = [];
        switch ($action) {
            default:
                throw new \InvalidArgumentException("Unknown action `{$action}`.");
                break;

            case "create":
                // Expect new data to be filled
                if (empty($new)) {
                    throw new \ArgumentCountError("Action `create` expects new data.");
                }

                // Process
                foreach ($new as $key => $value) {
                    $data[$key] = [
                        'old' => null,
                        'new' => $value
                    ];
                }
                break;

            case "update":
                // Expect old and new data to be filled
                if (empty($new)) {
                    // Restoring a soft-deleted entry, don't fail here.
                    foreach ($old as $key => $value) {
                        $data[$key] = [
                            'old' => $old[$key],
                            'new' => '(Entry restored)'
                        ];
                    }
                } else {
                    // A real update
                    if (empty($old) || empty($new)) {
                        throw new \ArgumentCountError("Action `update` expects both old and new data.");
                    }

                    // Process only what changed
                    foreach ($new as $key => $value) {
                        $data[$key] = [
                            'old' => $old[$key],
                            'new' => $value
                        ];
                    }
                }
                break;

            case "delete":
                // Expect new data to be filled
                if (empty($old)) {
                    throw new \ArgumentCountError("Action `delete` expects new data.");
                }

                // Process
                foreach ($old as $key => $value) {
                    $data[$key] = [
                        'old' => $value,
                        'new' => null
                    ];
                }
                break;
        }

        return json_encode($data);
    }

    /**
     * Gets the current user ID, or null if guest.
     *
     * @return mixed|null
     */
    public function getUserId()
    {
        if (auth()->guest())
            return null;

        return auth()->user()->getAuthIdentifier();
    }

    /**
     * Logs a record creation.
     *
     * @param $me
     * @param $model
     */
    protected function registerCreate($me, $model)
    {
        // Generate the JSON to store
        $data = $this->generate('create', [], $this->strip($model, $me->getAttributes()));

        // Get auth (if any)
        $userId = $this->getUserId();

        // Store record
        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::table('audit_trails')->insert([
            'user_id' => $userId,
            'model_name' => $model,
            'model_entry_id' => $me->{$me->getKeyName()},
            'action' => 'create',
            'record' => $data,
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }

    /**
     * Logs a record update.
     *
     * @param $me
     * @param $model
     */
    protected function registerUpdate($me, $model)
    {
        // Strip data
        // Generate the JSON to store
        $data = $this->generate('update', $this->strip($model, $me->getOriginal()), $this->strip($model, $me->getChanges()));

        // Get auth (if any)
        $userId = $this->getUserId();

        // Store record
        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::table('audit_trails')->insert([
            'user_id' => $userId,
            'model_name' => $model,
            'model_entry_id' => $me->{$me->getKeyName()},
            'action' => 'update',
            'record' => $data,
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }

    /**
     * Logs a record deletion.
     *
     * @param $me
     * @param $model\
     */
    protected function registerDelete($me, $model)
    {
        // Generate the JSON to store
        $data = $this->generate('delete', $this->strip($model, $me->getAttributes()));

        // Get auth (if any)
        $userId = $this->getUserId();

        // Store record
        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::table('audit_trails')->insert([
            'user_id' => $userId,
            'model_name' => $model,
            'model_entry_id' => $me->{$me->getKeyName()},
            'action' => 'delete',
            'record' => $data,
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }
}

<?php

namespace App\Http\Controllers\Installer;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ActivateController extends Controller
{
    public function activate()
    {
        if (! $this->checkDatabaseConnection()) {
            return redirect()->back()->withErrors([
                'database_connection' => trans('installer_messages.environment.wizard.form.db_connection_failed'),
            ]);
        }

        return redirect()->route('Installer.final');
    }

    public function verify(Request $request)
    {
        if (! $this->checkDatabaseConnection()) {
            return redirect()->route('Installer.activate')->with('failed', trans('installer_messages.environment.wizard.form.db_connection_failed'));
        }

        return redirect()->route('Installer.final');
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

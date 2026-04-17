<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.14  |
    |              on 2025-11-27 18:50:47              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*
* Copyright (C) Incevio Systems, Inc - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Written by Munna Khan <help.zcart@gmail.com>, September 2018
*/
 namespace App\Http\Controllers\Installer; use App\Http\Controllers\Installer\Helpers\DatabaseManager; use Exception; use Illuminate\Routing\Controller; use Illuminate\Support\Facades\DB; class DatabaseController extends Controller { private $databaseManager; public function __construct(DatabaseManager $databaseManager) { $this->databaseManager = $databaseManager; } public function database() { if ($this->checkDatabaseConnection()) { goto SIVFK; } return redirect()->back()->withErrors(["\x64\x61\x74\141\142\141\163\145\x5f\x63\157\156\x6e\145\x63\164\x69\157\x6e" => trans("\151\156\x73\164\141\x6c\154\x65\162\x5f\x6d\x65\x73\x73\141\x67\x65\x73\56\x65\x6e\x76\151\162\x6f\x6e\155\145\x6e\x74\x2e\167\151\172\x61\x72\x64\56\146\x6f\162\155\x2e\144\142\137\x63\x6f\x6e\156\x65\x63\x74\x69\157\156\x5f\x66\141\151\x6c\x65\x64")]); SIVFK: ini_set("\155\141\170\137\x65\170\x65\x63\165\x74\151\x6f\x6e\x5f\x74\x69\155\x65", 600); $response = $this->databaseManager->migrateAndSeed(); return redirect()->route("\111\x6e\x73\164\141\x6c\154\x65\x72\x2e\x66\151\x6e\x61\154")->with(["\x6d\145\163\163\141\x67\145" => $response]); } private function checkDatabaseConnection() { try { DB::connection()->getPdo(); return true; } catch (Exception $e) { return false; } } }

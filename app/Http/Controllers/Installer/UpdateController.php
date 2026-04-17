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
 namespace App\Http\Controllers\Installer; use App\Http\Controllers\Installer\Helpers\DatabaseManager; use App\Http\Controllers\Installer\Helpers\InstalledFileManager; use Illuminate\Routing\Controller; class UpdateController extends Controller { use \App\Http\Controllers\Installer\Helpers\MigrationsHelper; public function welcome() { return view("\151\156\163\164\141\x6c\154\x65\x72\56\x75\160\144\141\x74\145\x2e\167\x65\154\x63\x6f\x6d\x65"); } public function overview() { $migrations = $this->getMigrations(); $dbMigrations = $this->getExecutedMigrations(); return view("\151\x6e\163\164\141\x6c\154\145\x72\56\165\x70\x64\141\164\145\x2e\157\166\x65\x72\x76\x69\x65\167", ["\x6e\x75\155\x62\145\x72\117\146\x55\x70\144\141\164\x65\163\x50\x65\156\x64\151\x6e\x67" => count($migrations) - count($dbMigrations)]); } public function database() { $databaseManager = new DatabaseManager(); $response = $databaseManager->migrateAndSeed(); return redirect()->route("\114\141\x72\x61\x76\145\154\125\160\x64\141\164\145\x72\x3a\72\146\151\156\x61\x6c")->with(["\155\145\x73\x73\141\147\145" => $response]); } public function finish(InstalledFileManager $fileManager) { $fileManager->update(); return view("\151\156\x73\164\141\154\154\145\162\56\165\x70\x64\x61\x74\145\x2e\146\x69\156\151\163\x68\145\x64"); } }

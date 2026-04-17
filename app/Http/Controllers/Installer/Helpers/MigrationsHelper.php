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
 namespace App\Http\Controllers\Installer\Helpers; use Illuminate\Support\Facades\DB; trait MigrationsHelper { public function getMigrations() { $migrations = glob(database_path() . DIRECTORY_SEPARATOR . "\x6d\x69\147\x72\141\x74\151\x6f\156\x73" . DIRECTORY_SEPARATOR . "\52\x2e\160\x68\x70"); return str_replace("\x2e\x70\150\160", '', $migrations); } public function getExecutedMigrations() { return DB::table("\155\x69\x67\x72\x61\164\x69\x6f\x6e\163")->get()->pluck("\155\151\147\162\x61\164\151\x6f\x6e"); } }

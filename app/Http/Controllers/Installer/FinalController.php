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
 namespace App\Http\Controllers\Installer; use App\Http\Controllers\Installer\Helpers\DatabaseManager; use App\Http\Controllers\Installer\Helpers\EnvironmentManager; use App\Http\Controllers\Installer\Helpers\FinalInstallManager; use App\Http\Controllers\Installer\Helpers\InstalledFileManager; use Illuminate\Routing\Controller; class FinalController extends Controller { public function final(FinalInstallManager $finalInstall, EnvironmentManager $environment) { $finalMessages = $finalInstall->runFinal(); $finalEnvFile = $environment->getEnvContent(); return view("\x69\156\x73\164\x61\154\x6c\x65\x72\56\x66\151\x6e\151\163\x68\x65\144", compact("\146\151\x6e\141\154\x4d\x65\163\163\141\x67\145\x73", "\x66\x69\156\x61\x6c\105\x6e\166\x46\x69\x6c\x65")); } public function seedDemo(DatabaseManager $databaseManager) { $response = $databaseManager->seedDemoData(); return redirect()->route("\111\156\163\164\x61\154\x6c\x65\x72\x2e\146\x69\x6e\x69\163\x68"); } public function finish(InstalledFileManager $fileManager) { $finalStatusMessage = $fileManager->update(); return redirect()->to(config("\x69\x6e\x73\x74\141\154\x6c\x65\x72\x2e\x72\x65\x64\151\x72\145\143\164\x55\x72\154"))->with("\155\x65\163\x73\x61\x67\x65", $finalStatusMessage); } }

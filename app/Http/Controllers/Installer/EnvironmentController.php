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
 namespace App\Http\Controllers\Installer; use App\Http\Controllers\Installer\Helpers\EnvironmentManager; use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Illuminate\Routing\Redirector; use Validator; class EnvironmentController extends Controller { protected $EnvironmentManager; public function __construct(EnvironmentManager $environmentManager) { $this->EnvironmentManager = $environmentManager; } public function environmentMenu() { return view("\151\156\163\x74\141\154\154\x65\x72\56\145\x6e\x76\151\162\x6f\x6e\155\145\x6e\164"); } public function environmentWizard() { } public function environmentClassic() { $envConfig = $this->EnvironmentManager->getEnvContent(); return view("\151\x6e\x73\164\141\154\x6c\x65\162\x2e\x65\x6e\166\151\x72\x6f\156\155\x65\156\164\x2d\143\154\x61\163\163\x69\143", compact("\145\156\x76\103\157\156\x66\151\147")); } public function saveClassic(Request $input, Redirector $redirect) { $message = $this->EnvironmentManager->saveFileClassic($input); return $redirect->route("\x49\156\x73\164\141\x6c\154\x65\x72\x2e\x65\x6e\166\x69\162\x6f\156\155\145\x6e\164\x43\x6c\x61\x73\x73\151\143")->with(["\155\x65\163\x73\x61\147\145" => $message]); } }

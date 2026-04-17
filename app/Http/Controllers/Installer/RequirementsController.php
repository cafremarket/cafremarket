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
 namespace App\Http\Controllers\Installer; use App\Http\Controllers\Installer\Helpers\RequirementsChecker; use Illuminate\Routing\Controller; class RequirementsController extends Controller { protected $requirements; public function __construct(RequirementsChecker $checker) { $this->requirements = $checker; } public function requirements() { $phpSupportInfo = $this->requirements->checkPHPversion(config("\x69\x6e\163\164\141\x6c\x6c\145\x72\56\x63\157\x72\145\56\x6d\151\x6e\120\150\160\126\x65\x72\x73\151\x6f\156"), config("\x69\x6e\163\x74\x61\x6c\x6c\x65\x72\56\x63\157\162\x65\x2e\x6d\x61\x78\x50\150\160\x56\145\x72\x73\151\157\156")); $requirements = $this->requirements->check(config("\151\156\x73\164\141\x6c\154\x65\x72\56\162\145\161\x75\151\162\x65\x6d\145\156\164\163")); return view("\151\156\163\164\141\154\154\145\162\56\x72\x65\161\x75\151\162\145\x6d\x65\x6e\164\x73", compact("\162\145\x71\165\151\x72\x65\155\x65\x6e\x74\163", "\160\150\x70\123\x75\x70\160\157\x72\x74\x49\x6e\x66\x6f")); } }

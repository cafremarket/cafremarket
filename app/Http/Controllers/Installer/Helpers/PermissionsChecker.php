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
 namespace App\Http\Controllers\Installer\Helpers; class PermissionsChecker { protected $results = []; public function __construct() { $this->results["\x70\x65\162\155\x69\163\x73\151\x6f\156\x73"] = []; $this->results["\x65\162\162\157\162\163"] = null; } public function check(array $folders) { foreach ($folders as $folder => $permission) { if (!($this->getPermission($folder) >= $permission)) { goto sEbdb; } $this->addFile($folder, $permission, true); goto j2ing; sEbdb: $this->addFileAndSetErrors($folder, $permission, false); j2ing: hPXB0: } KqBLc: return $this->results; } private function getPermission($folder) { return substr(sprintf("\45\x6f", fileperms(base_path($folder))), -4); } private function addFile($folder, $permission, $isSet) { array_push($this->results["\x70\x65\162\x6d\151\x73\163\x69\x6f\x6e\x73"], ["\x66\x6f\x6c\x64\x65\x72" => $folder, "\x70\145\162\155\x69\x73\x73\x69\x6f\x6e" => $permission, "\x69\163\x53\145\164" => $isSet]); } private function addFileAndSetErrors($folder, $permission, $isSet) { $this->addFile($folder, $permission, $isSet); $this->results["\x65\x72\x72\157\x72\x73"] = true; } }

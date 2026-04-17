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
 namespace App\Http\Controllers\Installer\Helpers; class InstalledFileManager { public function create() { $installedLogFile = storage_path("\151\x6e\x73\164\141\154\154\x65\x64"); $dateStamp = date("\x59\x2f\155\x2f\144\40\x68\x3a\151\x3a\x73\x61"); if (!file_exists($installedLogFile)) { goto Jr6Nc; } $message = trans("\151\x6e\x73\164\x61\x6c\x6c\145\x72\137\x6d\x65\163\x73\x61\x67\x65\163\56\x75\160\x64\141\x74\145\162\56\154\x6f\x67\56\163\x75\143\143\x65\163\163\137\155\145\x73\163\x61\147\x65") . $dateStamp; file_put_contents($installedLogFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX); goto cUeRw; Jr6Nc: $message = trans("\151\156\x73\164\141\154\154\145\162\137\x6d\x65\163\x73\141\147\x65\163\x2e\x69\x6e\x73\164\x61\x6c\154\145\x64\x2e\x73\165\x63\x63\x65\x73\163\x5f\154\x6f\x67\137\155\145\163\x73\x61\147\145") . $dateStamp . "\12"; file_put_contents($installedLogFile, $message); cUeRw: return $message; } public function update() { return $this->create(); } }

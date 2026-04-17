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
 namespace App\Http\Controllers\Installer\Helpers; use Exception; use Illuminate\Support\Facades\Artisan; use Symfony\Component\Console\Output\BufferedOutput; class FinalInstallManager { public function runFinal() { $outputLog = new BufferedOutput(); $this->generateKey($outputLog); $this->publishVendorAssets($outputLog); return $outputLog->fetch(); } private static function generateKey($outputLog) { try { if (!config("\151\x6e\163\x74\141\x6c\154\x65\162\56\x66\151\156\141\154\56\x6b\145\x79")) { goto O1UFP; } Artisan::call("\153\x65\x79\x3a\147\x65\x6e\x65\162\x61\x74\x65", ["\55\55\x66\x6f\162\143\x65" => true], $outputLog); O1UFP: } catch (Exception $e) { return static::response($e->getMessage(), $outputLog); } return $outputLog; } private static function publishVendorAssets($outputLog) { try { if (!config("\x69\156\x73\164\x61\x6c\154\x65\162\56\x66\x69\156\141\154\56\x70\x75\142\x6c\x69\163\150")) { goto xPtCv; } Artisan::call("\x76\x65\x6e\144\x6f\x72\x3a\x70\165\x62\154\x69\x73\x68", ["\55\x2d\x61\154\x6c" => true], $outputLog); xPtCv: } catch (Exception $e) { return static::response($e->getMessage(), $outputLog); } return $outputLog; } private static function response($message, $outputLog) { return ["\163\x74\141\164\165\163" => "\x65\162\162\x6f\x72", "\x6d\x65\163\x73\141\x67\145" => $message, "\x64\x62\117\165\x74\160\x75\164\114\157\147" => $outputLog->fetch()]; } }

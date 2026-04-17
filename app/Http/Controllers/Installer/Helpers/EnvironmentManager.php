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
 namespace App\Http\Controllers\Installer\Helpers; use Exception; use Illuminate\Http\Request; class EnvironmentManager { private $envPath; private $envExamplePath; public function __construct() { $this->envPath = base_path("\56\145\x6e\166"); $this->envExamplePath = base_path("\56\145\x6e\x76\56\145\x78\x61\155\x70\154\145"); } public function getEnvContent() { if (file_exists($this->envPath)) { goto Bl3yW; } if (file_exists($this->envExamplePath)) { goto O6vMT; } touch($this->envPath); goto dtNCV; O6vMT: copy($this->envExamplePath, $this->envPath); dtNCV: Bl3yW: return file_get_contents($this->envPath); } public function getEnvPath() { return $this->envPath; } public function getEnvExamplePath() { return $this->envExamplePath; } public function saveFileClassic(Request $input) { $message = trans("\x69\156\163\164\x61\x6c\x6c\x65\162\137\x6d\145\163\x73\x61\x67\x65\163\56\145\x6e\x76\x69\x72\x6f\156\155\x65\x6e\164\x2e\163\x75\x63\x63\x65\x73\163"); try { file_put_contents($this->envPath, $input->get("\145\x6e\x76\103\157\x6e\146\x69\x67")); } catch (Exception $e) { $message = trans("\x69\x6e\163\x74\x61\154\x6c\145\x72\137\155\x65\x73\x73\x61\x67\x65\163\x2e\x65\x6e\166\151\x72\157\156\x6d\x65\156\164\56\145\162\x72\157\x72\x73"); } return $message; } }

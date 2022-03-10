<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/src/lib/requirements.php';
require_once dirname(__FILE__,3) . '/src/lib/auth.php';
require_once dirname(__FILE__,3) . '/src/lib/pdf.php';

class API{

  protected $Settings = [];
  protected $Language = 'english';
  protected $Languages = [];
  protected $Fields = [];
  protected $Timezones;
  protected $PHPVersion;
  protected $Auth;
  protected $PDF;
  protected $Debug = true;
  protected $Log = "tmp/php-pdf.log";

  public function __construct(){

    // Increase PHP memory limit
    ini_set('memory_limit', '2G');
    ini_set('max_execution_time', 0);

    // Init tmp directory
    $this->mkdir('tmp');

		// Gathering Server Information
		$this->PHPVersion=substr(phpversion(),0,3);

    // Import Configurations
		if(is_file(dirname(__FILE__,3) . "/config/config.json")){
			$this->Settings = json_decode(file_get_contents(dirname(__FILE__,3) . '/config/config.json'),true);
		}

		// Setup Debug
		if((isset($this->Settings['debug']))&&($this->Settings['debug'])){ $this->Debug = true; }
    if($this->Debug){ error_reporting(-1); } else { error_reporting(0); }

    // Setup log
    if(isset($this->Settings['log']['file'])){ $this->Log = $this->Settings['log']['file']; }
    $this->log("====================================================");
    $this->log("  Log ".date("Y-m-d H:i:s")."");
    $this->log("====================================================");

		//Import Listings
    $this->Timezones = json_decode(file_get_contents(dirname(__FILE__,3) . '/dist/data/timezones.json'),true);

		// Setup Language
		if(isset($_COOKIE['language'])){ $this->Language = $_COOKIE['language']; }
    elseif(isset($this->Settings['language'])){ $this->Language = $this->Settings['language']; }
    $this->Languages = array_diff(scandir(dirname(__FILE__,3) . "/dist/languages/"), array('.', '..'));
    foreach($this->Languages as $key => $value){ $this->Languages[$key] = str_replace('.json','',$value); }
    $this->Fields = json_decode(file_get_contents(dirname(__FILE__,3) . "/dist/languages/".$this->Language.".json"),true);

		// Setup Instance
		if(isset($this->Settings['timezone'])){ date_default_timezone_set($this->Settings['timezone']); }

    // Setup Auth
    $this->Auth = new Auth($this->Settings,$this->Fields);

    // Customize SMTP template
    if(isset($this->Settings['smtp'],$this->Settings['smtp']['username'],$this->Settings['smtp']['password'],$this->Settings['smtp']['host'],$this->Settings['smtp']['port'],$this->Settings['smtp']['encryption'])){
      $links = [
        "support" => "https://github.com/LouisOuellet/php-pdf",
        "trademark" => "#",
        "policy" => "#",
        "logo" => ""
      ];
      $this->Auth->SMTP->customization("PHP PDF",$links);
    }

    // Setup PDF
    $PDF = new phpPDF();
  }

  protected function mkdir($directory){
    $make = dirname(__FILE__,3);
    $directories = explode('/',$directory);
    foreach($directories as $subdirectory){
      $make .= '/'.$subdirectory;
      if(!is_file($make)&&!is_dir($make)){ mkdir($make); }
    }
    return $make;
  }

  protected function set(){
    try {
      $this->mkdir('config');
      $json = fopen(dirname(__FILE__,3).'/config/config.json', 'w');
  		fwrite($json, json_encode($this->Settings, JSON_PRETTY_PRINT));
  		fclose($json);
      return true;
    } catch(Exception $error){ return false; }
  }

  public function isInstall(){
    return is_file(dirname(__FILE__,3).'/config/config.json');
  }

  protected function error($log = []){
    $this->log(json_encode($log, JSON_PRETTY_PRINT));
    exit();
  }

  protected function log($txt){
    if($this->Debug){ echo $txt."\n"; }
    if(isset($this->Settings['log']['status']) && $this->Settings['log']['status']){
      return file_put_contents($this->Log, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    }
  }
}

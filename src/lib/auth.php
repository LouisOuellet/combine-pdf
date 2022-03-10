<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/src/lib/imap.php';
require_once dirname(__FILE__,3) . '/src/lib/smtp.php';

class Auth{

  public $SMTP;
  public $IMAP;
  protected $Settings = [];
  protected $Fields = [];

  public function __construct($settings = [],$fields = []){
    if(!empty($settings)){ $this->Settings = $settings; }
    if(!empty($fields)){ $this->Fields = $fields; }
    if(isset($this->Settings['imap'])){
      $this->IMAP = new PHPIMAP($this->Settings['imap']['host'],$this->Settings['imap']['port'],$this->Settings['imap']['encryption'],$this->Settings['imap']['username'],$this->Settings['imap']['password']);
    } else { $this->IMAP = new PHPIMAP(); }
    if(isset($this->Settings['smtp'])){
      $this->SMTP = new MAIL($this->Settings['smtp'],$this->Fields);
    } else { $this->SMTP = new MAIL(); }
    if(!isset($_SESSION['quarantine-username']) && isset($_POST['signin'],$_POST['username'],$_POST['password'])){
      $this->try($_POST['username'],$_POST['password']);
    }
  }

  public function try($username, $password){
    if((session_status() == PHP_SESSION_ACTIVE)&&(isset($_SESSION['quarantine-username']))){} else {
      if(!isset($_COOKIE['quarantine-username'])){
        if($this->IMAP->login($username,$password,$this->Settings['imap']['host'],$this->Settings['imap']['port'],$this->Settings['imap']['encryption'])){
          $_SESSION['quarantine-username'] = $username;
          $_SESSION['quarantine-password'] = $password;
          // setcookie('quarantine-username', $username, time() + (86400 * 30), "/");
          return true;
        } else {
          return false;
        }
      } else {
        $_SESSION['quarantine-username'] = $_COOKIE['quarantine-username'];
        $_SESSION['quarantine-password'] = $_COOKIE['quarantine-password'];
      }
    }
  }

  public function login($username, $password, $type = "imap", $settings = []){
    if($type == "imap"){
      if(empty($settings) && !empty($this->Settings)){ $settings = $this->Settings['imap']; }
      if(isset($settings['host'],$settings['port'],$settings['encryption'])){
        return $this->IMAP->login($username,$password,$settings['host'],$settings['port'],$settings['encryption']);
      } else { return false; }
    } elseif($type == "smtp"){
      if(empty($settings) && !empty($this->Settings)){ $settings = $this->Settings['smtp']; }
      if(isset($settings['host'],$settings['port'],$settings['encryption'])){
        return $this->SMTP->login($username,$password,$settings['host'],$settings['port'],$settings['encryption']);
      } else { return false; }
    } else { return false; }
  }
}

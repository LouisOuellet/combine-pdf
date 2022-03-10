<?php

class URLparser{

	public function is_base64_encoded($string){
	  if(preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $string)){ return TRUE; }
		else { return FALSE; }
	}

	public function is_url_encoded($string){
	  if(urldecode($string) != $string){ return TRUE; }
		else { return FALSE; }
	}

	public function decode($string){
		$string = trim($string);
		if($this->is_url_encoded($string)){ $string = urldecode($string); }
		if($this->is_base64_encoded($string)){ $string = base64_decode($string); }
		return $string;
	}

	public function isJson($string) {
		json_decode($string);
		return json_last_error() === JSON_ERROR_NONE;
	}

	public function parse($string){
		if($this->isJson($string)){
			try { return json_decode($string, true); }
			catch(Exception $error){ return $string; }
		} else { return $string; }
	}

	public function encode($array){
		return urlencode(base64_encode(json_encode($array, JSON_PRETTY_PRINT)));
	}
}

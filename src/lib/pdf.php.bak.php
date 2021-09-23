<?php

// Import Librairies

// import the namespaces

class apiPDF {

	protected $DPI = 300;
	protected $scale = 80;
	protected $maxFileSize = 10000000;

	private $compression = false;

	public $errors = [];

	public function __construct($settings = null){
		if($settings != null && is_array($settings)){
			if(isset($settings['scale'])){ $this->scale = $settings['scale']; }
			if(isset($settings['maxFileSize'])){ $this->maxFileSize = $settings['maxFileSize']; }
			if(isset($settings['compression'])){ $this->compression = $settings['compression']; }
		}
	}

	public function merge($files, $size = null){
		if($size == null || !is_numeric($size)){ $size = $this->maxFileSize; }
		// Verifications
		if(is_array($files) && count($files) > 0){
			// Initialize PDF
			$dir = pathinfo($files[0])['dirname'];
			$filename = $dir.'/'.time().'.pdf';
			$cmd = "convert ";
			foreach($files as $file){
				if(strpos(strtolower($file), '.pdf') !== false){
					$decrypted = str_replace('.pdf','-decrypted.pdf',$file);
					shell_exec("qpdf --decrypt $file $decrypted");
					$cmd .= $decrypted." ";
				}
			}
			shell_exec($cmd." $filename");
			return $filename;
		} else { $this->errors[] =  "No Files!"; }
	}
}

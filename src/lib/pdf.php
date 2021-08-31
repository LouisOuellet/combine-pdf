<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/vendor/fpdf183/fpdf.php';
require_once dirname(__FILE__,3) . '/vendor/FPDI-2.3.6/src/FpdiException.php';
require_once dirname(__FILE__,3) . '/vendor/FPDI-2.3.6/src/PdfParser/PdfParserException.php';
require_once dirname(__FILE__,3) . '/vendor/FPDI-2.3.6/src/PdfParser/CrossReference/CrossReferenceException.php';
require_once dirname(__FILE__,3) . '/vendor/FPDI-2.3.6/src/autoload.php';
require_once dirname(__FILE__,3) . '/vendor/php-pdf-merge/src/Jurosh/PDFMerge/PDFObject.php';
require_once dirname(__FILE__,3) . '/vendor/php-pdf-merge/src/Jurosh/PDFMerge/PDFMerger.php';
require_once dirname(__FILE__,3) . '/vendor/pdf-version-converter-1.0.5/src/Converter/ConverterInterface.php';
require_once dirname(__FILE__,3) . '/vendor/pdf-version-converter-1.0.5/src/Converter/GhostscriptConverter.php';
require_once dirname(__FILE__,3) . '/vendor/pdf-version-converter-1.0.5/src/Converter/GhostscriptConverterCommand.php';
require_once dirname(__FILE__,3) . '/vendor/pdf-version-converter-1.0.5/src/Guesser/GuesserInterface.php';
require_once dirname(__FILE__,3) . '/vendor/pdf-version-converter-1.0.5/src/Guesser/RegexGuesser.php';
require_once dirname(__FILE__,3) . '/vendor/symfony/process/Process.php';
require_once dirname(__FILE__,3) . '/vendor/symfony/process/ProcessUtils.php';
require_once dirname(__FILE__,3) . '/vendor/symfony/process/Pipes/PipesInterface.php';
require_once dirname(__FILE__,3) . '/vendor/symfony/process/Pipes/AbstractPipes.php';
require_once dirname(__FILE__,3) . '/vendor/symfony/process/Pipes/UnixPipes.php';
require_once dirname(__FILE__,3) . '/vendor/symfony/filesystem/Filesystem.php';

// import the namespaces
use Symfony\Component\Filesystem\Filesystem;
use Xthiago\PDFVersionConverter\Guesser\RegexGuesser;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;

class apiPDF {

	protected $DPI = 96;
	protected $RESOLUTION = 300;
	protected $SCALE = 80;
  protected $MM_IN_INCH = 25.4;
  protected $LETTER_HEIGHT = 279.4;
  protected $LETTER_WIDTH = 215.9;
  protected $MAX_WIDTH = 800;
  protected $MAX_HEIGHT = 500;

	public $errors = [];

	public function __construct(){}

	public function version($file){
		$guesser = new RegexGuesser();
		return floatval($guesser->guess($file));
	}

	public function combine($files,$destDIR = 'tmp/', $filename = null){
		// Create Merger Instance
		$pdf = new \Jurosh\PDFMerge\PDFMerger;
		// Start Merging
		foreach($files as $file){
			if(strpos(strtolower($file), '.pdf') !== false){
				$version = $this->version($file);
				if($version > 1.4){ $this->pdf214($file); }
				$pdf->addPDF($file, 'all');
			}
		}
		// Generate Name
		if($filename == null){ $file = trim($destDIR,'/').'/'.time().'.pdf'; }
		else{ $file = '/'.trim($destDIR,'/').'/'.$filename.'.pdf'; }
		// Save Locally
		$pdf->merge('file', $file);
		// Return
		return $file;
	}

	public function compress($pdf, $size = 10000000){
		// Initialize
		$pdfs = [];
		if(strpos(strtolower($pdf), '.pdf') !== false){
			// Gathering info
			$nbrPages = $this->getNbrPages($pdf);
			$fileSize = $this->getFileSize($pdf);
			$imgSize = ($fileSize - ($fileSize - $size)) / $nbrPages;
			// Convert to images
			$images = $this->pdf2img($file);
			if(!count($this->errors)){
				foreach($images as $image){
					// Compress Image
					$this->compressIMG($image, $imgSize);
					// Convert to PDF
					$pdfs[] = $this->img2pdf($image);
					// Remove Image
				  unlink($image);
				}
				// Compiling PDF
				$pdf = $PDF->combine($pdfs);
				// Remove PDFs
				foreach($pdfs as $file){ unlink($file); }
			}
		} else { $this->errors[] =  $file." is not a PDF file"; }
		if(!count($this->errors)){ return $pdf; } else { return false; }
	}

	// OCR

	public function OCR($file){
		$ocr = new TesseractOCR();
		$ocr->image($file);
		$timeout = 500;
		return $ocr->run($timeout);
	}

	// Helpers

	protected function getNbrPages($file){
		$imagick = new Imagick($file);
		return $imagick->getNumberImages();
	}

	protected function getFileSize($file){
		$imagick = new Imagick($file);
		return $imagick->getImageLength();
	}

  protected function pixelsToMM($val) {
    return $val * $this->MM_IN_INCH / $this->DPI;
  }

  protected function resizeToFit($imgFilename) {
	  list($width, $height) = getimagesize($imgFilename);
	  $widthScale = $this->MAX_WIDTH / $width;
	  $heightScale = $this->MAX_HEIGHT / $height;
	  $scale = min($widthScale, $heightScale);
	  return array(
      round($this->pixelsToMM($scale * $width)),
      round($this->pixelsToMM($scale * $height))
	  );
  }

	// Compressions

	protected function compressIMG($file, $size = 10000){
		$format = pathinfo($file)['extension'];
		if(strpos(strtolower($file), '.'.$format) !== false){
			$imagick = new Imagick($file);
			while ($imagick->getImageLength() > $size) {
				if(!$imagick->scaleImage($this->SCALE, $this->SCALE, true)){ $this->errors[] =  "Unable to scale ".$file; }
			}
			if(!$imagick->writeImage($file)){ $this->errors[] =  "Unable to write ".$file; }
		} else { $this->errors[] =  $file." is not a ".strtoupper($format)." file"; }
		if(!count($this->errors)){ return true; } else { return false; }
	}

	// Conversions

	protected function pdf214($file){
		$command = new GhostscriptConverterCommand();
		$filesystem = new Filesystem();
		$converter = new GhostscriptConverter($command, $filesystem);
		$converter->convert($file, '1.4');
		if(!count($this->errors)){ return true; } else { return false; }
	}

	public function pdf2img($file, $format = 'png'){
		if(strpos(strtolower($file), '.pdf') !== false){
			$images = [];
			// Convert to PNG
			for ($page = 0; $page <= $this->getNbrPages($file)-1; $page++) {
				$imagick = new Imagick();
				$pdf->setResolution($this->RESOLUTION,$this->RESOLUTION);
				if(!$imagick->readImage($file."[".$page."]")){ $this->errors[] =  "Unable to read ".$file."[".$page."]"; }
				$imagick->setImageFormat($format);
				$imagick->setImageDepth(32); // TesseractOCR 8
				$filename = str_replace('.pdf','-'.$page.'.'.$format,$file);
				if(!$imagick->writeImage($filename)){ $this->errors[] =  "Unable to write ".$filename; }
				$images[] = $filename;
			}
		} else { $this->errors[] =  $file." is not a PDF file"; }
		if(!count($this->errors)){ return $images; } else { return false; }
	}

	public function img2pdf($file){
		$format = pathinfo($file)['extension'];
		if(strpos(strtolower($file), '.'.$format) !== false){
			$pdf = new Imagick();
			$pdf->setResolution($this->RESOLUTION,$this->RESOLUTION);
			if(!$pdf->readImage($file)){ $this->errors[] =  "Unable to read ".$file; }
			$pdf->setFormat('pdf');
			$filename = str_replace('.'.$format,'.pdf',$file);
			if(!$pdf->writeImage($filename)){ $this->errors[] =  "Unable to write ".$filename; }
		} else { $this->errors[] =  $file." is not a ".strtoupper($format)." file"; }
		if(!count($this->errors)){ return $filename; } else { return false; }
	}

	// public function png2pdff($file){
	// 	if(strpos(strtolower($file), '.png') !== false){
	// 		list($width, $height) = $this->resizeToFit($file);
	// 		$pdf = new FPDF();
	// 		if($height >= $width){
	// 			$pdf->AddPage('P',"Letter");
	// 			$pdf->Image(
	// 	      $file, ($this->LETTER_WIDTH - $width) / 2,
	// 	      ($this->LETTER_HEIGHT - $height) / 2,
	// 	      $width,
	// 	      $height
	// 	    );
	// 		} else {
	// 			$pdf->AddPage('L',"Letter");
	// 			$pdf->Image(
	// 	      $file, ($this->LETTER_HEIGHT - $width) / 2,
	// 	      ($this->LETTER_WIDTH - $height) / 2,
	// 	      $width,
	// 	      $height
	// 	    );
	// 		}
	// 		// $pdf->Image($file, 0, 0);
	// 		$filename = str_replace('.png','.pdf',$file);
	// 		$pdf->Output('F', $filename, true);
	// 	} else { $this->errors[] =  $file." is not a PNG file"; }
	// 	if(!count($this->errors)){ return true; } else { return false; }
	// }
}

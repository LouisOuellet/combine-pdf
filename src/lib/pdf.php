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

class apiPDF{

	public $errors = [];

	public function __construct(){}

	public function version($file){
		$guesser = new RegexGuesser();
		return $guesser->guess($file);
	}

	public function combine($files,$destDIR, $filename = null){
		// Create Merger Instance
		$pdf = new \Jurosh\PDFMerge\PDFMerger;
		// Start Merging
		foreach($files as $file){
			if(strpos(strtolower($file), '.pdf') !== false){
				if($this->version($file) != '1.14'){ $this->pdf214($file); }
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
		if(strpos(strtolower($pdf), '.pdf') !== false){
			// Get Filename
			$tiff = str_replace('.pdf','.tiff',$pdf);
			// Convert to TIFF
			$this->pdf2tiff($pdf);
			$this->resizeTiff($tiff, $size);
			// converts /dir/fax.tiff to /dir/fax.pdf
			$this->tiff2pdf($tiff, $pdf);
		} else { $this->errors[] =  $file." is not a PDF file"; }
		if(!count($this->errors)){ return true; } else { return false; }
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
		$document = new Imagick($file);
		return $document->getNumberImages();
	}

	// Compressions

	protected function resizeTiff($file, $size = 10000000){
		if(strpos(strtolower($file), '.tiff') !== false){
			$tiff = new Imagick($file);
			// Setting your default compression
			$compression_value = 40;
			$comression_type = Imagick::COMPRESSION_JPEG;
			// Imagick needs to know how to compress
			$tiff->setImageCompression($comression_type);
			$tiff->setImageCompressionQuality($compression_value);
			// getImageLength gets the length of the file in bytes.
			while ($tiff->getImageLength() > $size) {
			    $compression_value = $compression_value +1;
			    $tiff->setImageCompressionQuality($compression_value);
			}
			$tiff->writeImage($file);
		} else { $this->errors[] =  $file." is not a TIFF file"; }
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

	protected function pdf2tiff($file){
		if(strpos(strtolower($file), '.pdf') !== false){
			// Initialize
			$colorspace = imagick::COLORSPACE_CMYK;
			// Convert to TIFF
			for ($page = 0; $page <= $this->getNbrPages($file); $page++) {
				$tiff = new Imagick();
				$tiff->readimage($file."[".$page."]");
				$tiff->setImageFormat("tiff");
				$tiff->setImageColorSpace($colorspace);
				$tiff->setImageDepth(8);
				$tiff->writeImage(str_replace('.pdf','-'.$page.'.tiff',$file));
				$images[] = str_replace('.pdf','-'.$page.'.tiff',$file);
			}
		} else { $this->errors[] =  $file." is not a PDF file"; }
		if(!count($this->errors)){ return $images; } else { return false; }
	}

	public function pdf2png($file){
		if(strpos(strtolower($file), '.pdf') !== false){
			// Initialize
			$colorspace = imagick::COLORSPACE_CMYK;
			$images = [];
			// Convert to PNG
			for ($page = 0; $page <= $this->getNbrPages($file); $page++) {
				$png = new Imagick();
				$png->readimage($file."[".$page."]");
				$png->setImageFormat("png");
				$png->setImageColorSpace($colorspace);
				$png->setImageDepth(8);
				$png->writeImage(str_replace('.pdf','-'.$page.'.png',$file));
				$images[] = str_replace('.pdf','-'.$page.'.png',$file);
			}
		} else { $this->errors[] =  $file." is not a PDF file"; }
		if(!count($this->errors)){ return $images; } else { return false; }
	}

	protected function tiff2pdf($file_tif, $file_pdf){
	  // Initialize
	  $cmd_ps2pdf = "/usr/bin/ps2pdfwr";
	  // Initial Error handling
	  if (!file_exists($file_tif)) $this->errors[] = "Original TIFF file:".$file_tif." does not exist";
	  if (!file_exists($cmd_ps2pdf)) $this->errors[] = "Ghostscript PostScript to PDF converter not found at: ".$cmd_ps2pdf;
	  if (!extension_loaded("imagick")) $this->errors[] = "Imagick extension not installed or not loaded";
	  // Only continue if there aren't any errors
	  if (!count($this->errors)) {
      // Determine the file base
      $base = $file_pdf;
      if(($ext = strrchr($file_pdf, '.')) !== false) $base = substr($file_pdf, 0, -strlen($ext));
      // Determine the temporary .ps filepath
      $file_ps = $base.".ps";
      // Open the original .tiff
      $document = new Imagick($file_tif);
      // Use Imagick to write multiple pages to 1 .ps file
      if (!$document->writeImages($file_ps, true)) {
        $this->errors[] = "Unable to use Imagick to write multiple pages to 1  .ps file: ".$file_ps;
      } else {
        $document->clear();
        // Use ghostscript to convert .ps -> .pdf
        exec($cmd_ps2pdf." -sPAPERSIZE=a4 ".$file_ps." ".$file_pdf, $o, $r);
        if ($r) {
          $this->errors[] = "Unable to use ghostscript to convert .ps(".$file_ps.") -> .pdf(".$file_pdf."). Check rights. ";
        }
      }
	  }
	  // return array with errors, or true with success.
		if(!count($this->errors)){ return true; } else { return false; }
	}
}

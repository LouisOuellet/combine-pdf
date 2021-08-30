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

	public function compress($file, $size="10mb"){
		// Initialize
		$file = escapeshellarg($file);
		if(strpos(strtolower($file), '.pdf') !== false){
			// Get Filename
			$filename = str_replace('.pdf','',$file);
			// Convert to TIFF
			$tiff = $this->pdf2tiff($file);
			$tiff = $this->resizeTiff($tiff);
			// converts /dir/fax.tiff to /dir/fax.pdf
			if (($return = $this->tiff2pdf($tiff, $file)) !== true) { echo "Error:\n"; print_r($return);
			} else { echo "success!\n"; }
		}
	}

	// Compressions

	public function resizeTiff($file, $size = 10000000){
		// Initialize
		$file = escapeshellarg($file);
		if(strpos(strtolower($file), '.tiff') !== false){
			$tiff = new Imagick($file);
			// Setting your default compression
			$compression_value = int(40);
			// Imagick needs to know how to compress
			$tiff->setImageCompression(COMPRESSION_JPEG);
			$tiff->setImageCompressionQuality($compression_value);
			// getImageLength gets the length of the file in bytes.
			while ($tiff->getImageLength() > $size) {
			    $compression_value = $compression_value +1;
			    $tiff->setImageCompressionQuality($compression_value);
			}
			$tiff->writeImage($file);
			return $file;
		}
	}

	// Conversions

	protected function pdf214($file){
		$command = new GhostscriptConverterCommand();
		$filesystem = new Filesystem();
		$converter = new GhostscriptConverter($command, $filesystem);
		$converter->convert($file, '1.4');
	}

	protected function pdf2tiff($file){
		// Initialize
		$file = escapeshellarg($file);
		if(strpos(strtolower($file), '.pdf') !== false){
			// Convert to TIFF
			$tiff = new Imagick($file);
			$tiff->setImageFormat("tiff");
			$tiff->setImageColorSpace(5);
			$tiff->writeImage(str_replace('.pdf','.tiff',$file));
			return str_replace('.pdf','.tiff',$file);
		}
	}

	protected function tiff2pdf($file_tif, $file_pdf){
	  // Initialize
	  $errors     = array();
	  $cmd_ps2pdf = "/usr/bin/ps2pdfwr";
	  $file_tif   = escapeshellarg($file_tif);
	  $file_pdf   = escapeshellarg($file_pdf);
	  // Initial Error handling
	  if (!file_exists($file_tif)) $errors[] = "Original TIFF file:".$file_tif." does not exist";
	  if (!file_exists($cmd_ps2pdf)) $errors[] = "Ghostscript PostScript to PDF converter not found at: ".$cmd_ps2pdf;
	  if (!extension_loaded("imagick")) $errors[] = "Imagick extension not installed or not loaded";
	  // to include the imagick extension dynamically use an optional:
	  dl('imagick.so');
	  // Only continue if there aren't any errors
	  if (!count($errors)) {
      // Determine the file base
      $base = $file_pdf;
      if(($ext = strrchr($file_pdf, '.')) !== false) $base = substr($file_pdf, 0, -strlen($ext));
      // Determine the temporary .ps filepath
      $file_ps = $base.".ps";
      // Open the original .tiff
      $document = new Imagick($file_tif);
      // Use Imagick to write multiple pages to 1 .ps file
      if (!$document->writeImages($file_ps, true)) {
        $errors[] = "Unable to use Imagick to write multiple pages to 1  .ps file: ".$file_ps;
      } else {
        $document->clear();
        // Use ghostscript to convert .ps -> .pdf
        exec($cmd_ps2pdf." -sPAPERSIZE=a4 ".$file_ps." ".$file_pdf, $o, $r);
        if ($r) {
          $errors[] = "Unable to use ghostscript to convert .ps(".$file_ps.") -> .pdf(".$file_pdf."). Check rights. ";
        }
      }
	  }
	  // return array with errors, or true with success.
	  if (!count($errors)) { return true; } else { return $errors; }
	}
}

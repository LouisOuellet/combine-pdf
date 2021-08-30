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
			for ($page = 0; $page <= $this->getNbrPages($file)-1; $page++) {
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
			for ($page = 0; $page <= $this->getNbrPages($file)-1; $page++) {
				$png = new Imagick();
				if(!$pdf->readImage($file."[".$page."]")){ $this->errors[] =  "Unable to read ".$file."[".$page."]"; }
				$png->setImageFormat("png");
				$png->setImageColorSpace($colorspace);
				$png->setImageDepth(8);
				$filename = str_replace('.pdf','-'.$page.'.png',$file);
				if(!$png->writeImage($filename)){ $this->errors[] =  "Unable to write ".$filename; }
				$images[] = $filename;
			}
		} else { $this->errors[] =  $file." is not a PDF file"; }
		if(!count($this->errors)){ return $images; } else { return false; }
	}

	public function png2pdf($file){
		if(strpos(strtolower($file), '.png') !== false){
			$pdf = new Imagick();
			if(!$pdf->readImage($file)){ $this->errors[] =  "Unable to read ".$file; }
			$pdf->setFormat('pdf');
			$filename = str_replace('.png','.pdf',$file);
			if(!$png->writeImage($filename)){ $this->errors[] =  "Unable to write ".$filename; }
		} else { $this->errors[] =  $file." is not a PNG file"; }
		if(!count($this->errors)){ return true; } else { return false; }
	}
}

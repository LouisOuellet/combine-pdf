<?php

// Import Librairies
require_once dirname(__FILE__) . '/src/lib/pdf.php';

// Adding Librairies
$PDF = new apiPDF();

$file = dirname(__FILE__) . '/tmp/test.pdf';
$size = 10000000; //10mb

$pdfs = [];
foreach($PDF->pdf2png($file) as $image){
  if(!$PDF->png2pdff($image)){ print_r($PDF->errors); }
  $pdfs[] = str_replace('.png','.pdf',$image);
}
$pdf = $PDF->combine($pdfs);
if(count($PDF->errors)){ print_r($PDF->errors); } else { print_r($pdf); }

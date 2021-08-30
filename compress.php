<?php

// Import Librairies
require_once dirname(__FILE__) . '/src/lib/pdf.php';

// Adding Librairies
$PDF = new apiPDF();

$file = dirname(__FILE__) . '/tmp/test.pdf';
$size = 10000000; //10mb

if(!$images = $PDF->pdf2png($file)){ print_r($PDF->errors); }
foreach($images as $image){
  if(!$PDF->png2pdf($file)){ print_r($PDF->errors); }
}
// if(!$PDF->compress($file,$size)){ print_r($PDF->errors); }

<?php

// Import Librairies
require_once dirname(__FILE__) . '/src/lib/pdf.php';

// Adding Librairies
$PDF = new apiPDF();

$file = dirname(__FILE__) . '/tmp/test.pdf';
$size = 10000000; //10mb

$pdf = $PDF->compress($file, $size);
if(count($PDF->errors)){ print_r($PDF->errors); } else { echo $pdf."\n"; }

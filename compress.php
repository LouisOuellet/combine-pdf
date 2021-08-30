<?php

// Import Librairies
require_once dirname(__FILE__) . '/src/lib/pdf.php';

// Adding Librairies
$PDF = new apiPDF();

$file = dirname(__FILE__) . '/tmp/test.pdf';
$size = 10000000; //10mb

if(!$images = $PDF->pdf2png($file)){ print_r($PDF->errors); } else { print_r($images); }
// if(!$PDF->compress($file,$size)){ print_r($PDF->errors); }

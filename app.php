<?php
session_start();

// Import Librairies
require dirname(__FILE__) . '/src/lib/application.php';

// Initialise Application
$App = new Application;
$App->start();

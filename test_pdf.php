<?php
require 'vendor/autoload.php';

try {
    $parser = new \Smalot\PdfParser\Parser();
    echo "¡Librería instalada correctamente!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
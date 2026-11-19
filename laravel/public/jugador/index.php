<?php
// Fallback para el servidor de desarrollo de PHP cuando se accede a /jugador/
$file = file_exists(__DIR__ . '/index.html') ? __DIR__ . '/index.html' : __DIR__ . '/browser/index.html';
if (file_exists($file)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($file);
} else {
    http_response_code(404);
    echo "No se encontró index.html";
}

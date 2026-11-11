<?php

use App\Services\Demo\DemoResetService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:reset', function (DemoResetService $demoReset) {
    $ms = $demoReset->reset();
    $this->info("⚡ Base de datos de demostración reiniciada en {$ms} ms.");
    $this->line('   • Panel Web (Admin Liga): directiva.liga@playbook.cl / password');
    $this->line('   • App Jugador (Ionic):    javier@email.com / password');
})->purpose('Reinicia instantáneamente los datos de demostración para la Feria de Ciencias');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinisherQueueController;

Route::get('/', function () {
    return view('welcome');
});

// Finisher Queue Routes (Public access for displays)
Route::get('/finisher/queue', [FinisherQueueController::class, 'index'])->name('finisher.queue');
Route::get('/finisher/queue/data', [FinisherQueueController::class, 'data'])->name('finisher.queue.data');

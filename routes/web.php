<?php

use App\Http\Controllers\ArticleExtractorController;
use App\Http\Controllers\DistributorController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::prefix('distributors')->name('distributors.')->group(function () {
    Route::get('/', [DistributorController::class, 'index'])->name('index');
});

Route::get('/extract', [ArticleExtractorController::class, 'form'])->name('extract.form');
Route::post('/extract', [ArticleExtractorController::class, 'extractView'])->name('extract.view');

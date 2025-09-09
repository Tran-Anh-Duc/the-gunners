<?php

//use App\Http\Controllers\ArticleExtractorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DistributorController;
use App\Http\Controllers\Api\ArticleExtractorController;


Route::get('/', function () {
    return view('welcome');
});



Route::prefix('distributors')->name('distributors.')->group(function () {
    Route::get('/', [DistributorController::class, 'index'])->name('index');   // danh sách
});

Route::get('/extract', [ArticleExtractorController::class, 'form'])->name('extract.form');
Route::post('/extract', [ArticleExtractorController::class, 'extractView'])->name('extract.view');

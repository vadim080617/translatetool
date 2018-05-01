<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});

Route::get("send", "ExcelController@test");
Route::post("send/excel", "ExcelController@excelParser");
Route::post("send/translate", "ExcelController@translateParser");
Route::get("download/translate","ExcelController@downloadTranslates");
Route::get("download/excel","ExcelController@downloadExcel");
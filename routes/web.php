<?php

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\FileManager;
use Illuminate\Support\Facades\Route;

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
    //return view('welcome');
    return view('file-manager');
});

Route::match(['get', 'post'], '/get-directories', [FileManager::class, 'getDirectories']);
Route::match(['get', 'post'], '/get-files', [FileManager::class, 'getFiles']);
Route::match(['get', 'post'], '/get-files-directories', [FileManager::class, 'getFilesDirectories']);

Route::match(['get', 'post'], '/get-tree', [FileManager::class, 'getTree']);
Route::match(['post'], '/make-directory', [FileManager::class, 'makeDir']);
Route::get('delete-directory',[FileManager::class, 'deleteDir']);
Route::post('rename-directory',[FileManager::class, 'renameDir']);
Route::post('upload-file',[FileManager::class, 'uploadFile']);
Route::get('admin', [DashboardController::class, 'index'])->name('admin');

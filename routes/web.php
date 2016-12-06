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

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::get('/CompanyPages', 'Collection\CompanyController@CompanyPages');
Route::get('/Companyinfos', 'Collection\CompanyController@Companyinfos');

Route::get('/cert', 'Collection\cert@addcert');
Route::get('/CompanyCerts', 'Collection\cert@get_company_cert');
Route::get('/entlist', 'Collection\export@entlist');

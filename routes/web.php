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
    return view('index');
});
Route::group(['prefix' => 'site', 'as' => 'site.'], function () {
    Route::get('home', 'Site\SiteController@index')->name('home');
    Route::get('login', 'Site\SiteController@login')->name('login');
    Route::get('resgiter', 'Site\SiteController@signup')->name('resgiter');
    Route::get('success_booking', 'Site\SiteController@success');
    Route::get('accept', 'Site\SiteController@accept');
});
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin'], function () {
    Route::get('home','AdminController@home');
    Route::get('manager_customer', 'AdminController@manager_customer')->name('customer');
    Route::get('manager_booking', 'AdminController@manager_booking')->name('booking');
    Route::get('list_booking', 'AdminController@list_booking')->name('list_booking');
    Route::get('manager_department', 'AdminController@manager_department');
    Route::get('manager_setting', 'AdminController@manager_service')->name('manager_setting');
    Route::get('profile/{id}', 'AdminController@profile');
    Route::get('bill', 'AdminController@bill');
    Route::get('list_bill', 'AdminController@list_bill')->name('list_bill');
    Route::get('export_bill/{id}', 'BillController@exportBill')->name('export_bill');
});


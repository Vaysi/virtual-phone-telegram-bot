<?php

Route::get('/',function (){
    return auth()->check() ? redirect(url('/dashboard')) : abort(404);
});

Route::get('/dashboard','DashboardController@index');

Route::post('/webhook','HomeController@webhook');

Route::get('verifyPayment','HomeController@verifyPayment')->name('verify');
Route::get('pay/{ref}','HomeController@toGateway')->name('pay');

Auth::routes();

Route::get('logout', 'Auth\LoginController@logout')->name('logout');

Route::resource('countries','CountriesController');

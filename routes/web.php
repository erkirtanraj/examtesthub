<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
Route::get('/', fn()=>Inertia::render('Welcome'))->name('home');
Route::get('/language/{locale}', function(string $locale){ abort_unless(in_array($locale,['en','hi'],true),404); session(['locale'=>$locale]); return back(); })->name('language.switch');

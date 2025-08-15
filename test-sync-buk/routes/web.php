<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/system-rrhh/departments', 'departments.index')->name('system-rrhh.departments');
Route::view('/system-rrhh/skills', 'skills.index')->name('system-rrhh.skills');
Route::view('/system-rrhh/employees', 'employees.index')->name('system-rrhh.employees');


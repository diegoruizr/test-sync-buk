<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/system-attendance/departments', 'departments.index')->name('system-attendance.departments');
Route::view('/system-attendance/skills', 'skills.index')->name('system-attendance.skills');
Route::view('/system-attendance/employees', 'employees.index')->name('system-attendance.employees');
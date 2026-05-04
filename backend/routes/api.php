<?php

use App\Http\Controllers\Api\Ticket\ExportXlsxController;
use Illuminate\Support\Facades\Route;


Route::get('/ticket/export-xlsx', [ExportXlsxController::class, 'index']);

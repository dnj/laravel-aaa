<?php

use dnj\AAA\Http\Controllers\TypesController;
use dnj\AAA\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")->middleware(["api", "auth"])->group(function() {
	Route::apiResource("users", UsersController::class);
	Route::apiResource("types", TypesController::class);
});
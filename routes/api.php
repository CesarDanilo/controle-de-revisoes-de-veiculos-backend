<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\RevisionsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReportController;

use App\Http\Controllers\Auth\GoogleAuthController;


Route::apiResource('users', UserController::class);

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('people', PeopleController::class);
});

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('brands', BrandsController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('vehicles', VehicleController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('revisions', RevisionsController::class);
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('vehicles', [ReportController::class, 'allVehicles']);
    Route::get('vehicles/by-person', [ReportController::class, 'vehiclesByPerson']);
    Route::get('vehicles/by-gender', [ReportController::class, 'vehiclesByGender']);
    Route::get('vehicles/brands-ranking', [ReportController::class, 'brandsRanking']);
    Route::get('vehicles/brands-by-gender', [ReportController::class, 'brandsByGender']);

    Route::get('people', [ReportController::class, 'allPeople']);
    Route::get('people/by-gender', [ReportController::class, 'peopleByGender']);

    Route::get('revisions', [ReportController::class, 'revisionsByPeriod']);
    Route::get('revisions/brands-ranking', [ReportController::class, 'brandsRevisionRanking']);
    Route::get('revisions/people-ranking', [ReportController::class, 'peopleRevisionRanking']);
    Route::get('revisions/avg-interval', [ReportController::class, 'avgIntervalByPerson']);
    Route::get('revisions/upcoming', [ReportController::class, 'upcomingRevisions']);
});
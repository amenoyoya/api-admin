<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Amenoyoya\TrackableJob\Facades\TrackableJob;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * GET /api/job_status?job_status_id={job_status_id}
 * @return array {'status' => string, 'name' => string, 'id' => string, 'created_at' => string, 'updated_at' => string, ...}
 */
Route::get('/job_status', function (Request $request) {
    if (null === ($jobStatus = TrackableJob::getJobStatus($request->get('job_status_id')))) {
        return response()->json(['response' => 'not found'], 404);
    }
    return response()->json($jobStatus);
});

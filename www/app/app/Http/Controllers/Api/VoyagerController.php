<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessExecutionJob;
use Amenoyoya\TrackableJob\Facades\TrackableJob;

class VoyagerController extends Controller
{
    /**
     * OS command execution API: POST /voyager/api/exec
     * @param Request $request
     *      - token: API認証用トークン
     *      - command: 実行コマンド
     * @return Response
     */
    public function exec(Request $request)
    {
        if (empty($command = $request->get('command'))) {
            return response()->json(['response' => '`command` must be required'], 400);
        }
        // ProcessExecutionJob を Queue に追加して JobStatusId を返す
        $job_status_id = TrackableJob::dispatch(new ProcessExecutionJob($command));
        return response()->json(compact('job_status_id'));
    }
}

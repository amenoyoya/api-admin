<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        // 作業ディレクトリを artisan のある場所に変更してから exec
        $cwd = getcwd();
        if (!chdir(base_path())) {
            return response()->json(['response' => 'failed to change working directory'], 500);
        }
        $stdout = [];
        $response = exec($command, $stdout);
        // 作業ディレクトリを元に戻す
        chdir($cwd);
        return response()->json(compact('response', 'stdout'));
    }
}

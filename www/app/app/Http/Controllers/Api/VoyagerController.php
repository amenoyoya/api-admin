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
        // proc_open で OS コマンド実行
        $descriptorspec = [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'w'], // stderr
        ];
        if (false === ($process = proc_open($command, $descriptorspec, $pipes, base_path()))) {
            return response()->json(['response' => 'command failed to execute'], 500);
        }
        // stdout, stderr 取得
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        // デッドロックを避けるため、proc_close を呼ぶ前にすべてのパイプを閉じる
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
        return response()->json(compact('stdout', 'stderr'));
    }
}

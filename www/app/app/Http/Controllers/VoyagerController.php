<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\DataType;

class VoyagerController extends Controller
{
    /**
     * CSVインポート: POST /admin/{slug}/import
     * @param File $csvfile
     */
    public function import(Request $request, $slug)
    {
        $model = DataType::where('slug', $slug)->first()->model_name;
        // CSVファイルを一時保存
        $tmp = $request->file('csvfile')->store('csv');
        // 一時保存したCSVファイルを読み込み
        $fp = fopen(storage_path('app/') . $tmp, 'r');
        // ヘッダー解析
        $headers = fgetcsv($fp);
        $columns = [];
        foreach ($headers as $header) {
            $columns[] = $header;
        }
        // データを読み込みながらDBにインポート
        while ($row = fgetcsv($fp)) {
            $data = [];
            foreach ($columns as $index => $column) {
                $data[$column] = $row[$index];
            }
            if (isset($data['id'])) {
                $where = ['id' => $data['id']];
                $model::updateOrCreate($where, array_diff_key($data, $where));
            } else {
                $model::create($data);
            }
        }
        // FlashMessageを表示して一覧ページにリダイレクト
        return back()->with([
            'message'    => 'データがインポートされました',
            'alert-type' => 'success',
        ]);
    }

    /**
     * CSVエクスポート: POST /admin/{slug}/export
     * @param string $ids '1,2,3,...'
     */
    public function export(Request $request, $slug)
    {
        $dataType = DataType::where('slug', $slug)->first();
        $items = $dataType->model_name::whereIn('id', explode(',', $request->get('ids')))->get();
        // CSVダウンロード
        return response()->stream(function() use($items) {
            // CSV作成
            $fp = fopen('php://output', 'w+');
            // CSVヘッダ
            $attributes = array_keys($items[0]->getAttributes());
            fputcsv($fp, $attributes);
            // 全データ書き込み
            foreach ($items as $item) {
                $row = [];
                foreach ($attributes as $attr) {
                    $row[] = $item->{$attr};
                }
                fputcsv($fp, $row);
            }
            fclose($fp);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$dataType->name}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}

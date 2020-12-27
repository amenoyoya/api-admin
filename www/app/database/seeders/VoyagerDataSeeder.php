<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VoyagerDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Voyager::BLEAD/Database 設定
        $table = \DB::table('data_types');
        foreach ($this->loadCSV(dirname(__FILE__) . '/csv/data_types.csv') as $row) {
            $table->updateOrInsert(
                ['id' => $row['id']],
                array_merge($row, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            ));
            // テーブル更新直後だと updateOrInsert のときに id が上手く判定されないため毎回 table を取得し直す
            $table = \DB::table('data_types');
        }
        
        // Voyager::BLEAD/FormFields 設定
        $table = \DB::table('data_rows');
        foreach ($this->loadCSV(dirname(__FILE__) . '/csv/data_rows.csv') as $row) {
            $table->updateOrInsert(
                ['id' => $row['id']],
                $row
            );
            // テーブル更新直後だと updateOrInsert のときに id が上手く判定されないため毎回 table を取得し直す
            $table = \DB::table('data_rows');
        }
    }

    /**
     * CSVファイルを連想配列として読み込み
     * @param string $filename
     * @return array|bool
     */
    private function loadCSV($filename)
    {
        $csv = [];
        if (false === ($handle = fopen($filename, 'r'))) {
            return false;
        }
        
        // CSVを配列に変換
        while ($row = fgetcsv($handle)) {
            $csv[] = $row;
        }
        
        // 連想配列に変換
        $keys = array_shift($csv);
        $result = array_map(function($row) use ($keys) {
            return array_combine($keys, $row);
        }, $csv);

        return $result;
    }
}

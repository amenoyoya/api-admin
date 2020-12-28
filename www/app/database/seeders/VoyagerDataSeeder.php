<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Symfony\Component\Yaml\Yaml;

class VoyagerDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedVoyagerDataFromYaml('scheduled_tasks');
    }

    /**
     * YamlファイルからVoyagerデータベース準備
     * @param string $name
     */
    private function seedVoyagerDataFromYaml($name)
    {
        $data = $this->loadYaml(dirname(__FILE__) . "/yaml/$name.yml");
        $this->seedVoyagerBreadDataType($data);
        $this->seedVoyagerBreadDataRows($data);
        $this->seedVoyagerBreadMenuItem($data);
        $this->seedVoyagerBreadPermissions($data);
    }

    /**
     * 連想配列から Voyager::BLEAD/DataType 作成
     * @param array $data ['data_type' => array, 'data_rows' => array]
     */
    private function seedVoyagerBreadDataType($data)
    {
        \DB::table('data_types')->updateOrInsert(
            ['id' => $data['data_type']['id']],
            array_merge($data['data_type'], [
                'generate_permissions' => 1,
                'details' => '{"order_column":null,"order_display_column":null,"order_direction":"asc","default_search_key":null,"scope":null}',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ));
    }

    /**
     * 連想配列から Voyager::BLEAD/DataRows 作成
     * @param array $data ['data_type' => array, 'data_rows' => array]
     */
    private function seedVoyagerBreadDataRows($data)
    {
        foreach ($data['data_rows'] as $index => $row) {
            \DB::table('data_rows')->updateOrInsert(
                ['id' => $row['id']],
                array_merge($row, [
                    'data_type_id' => $data['data_type']['id'],
                    'order' => $index + 1,
                ])
            );
        }
    }

    /**
     * 連想配列から Voyager::BLEAD/MenuItem 作成
     * @param array $data ['data_type' => array, 'data_rows' => array]
     */
    private function seedVoyagerBreadMenuItem($data)
    {
        $id = $data['data_type']['id'] + 8;
        \DB::table('menu_items')->updateOrInsert(
            ['id' => $id],
            [
                'id' => $id,
                'menu_id' => 1,
                'title' => $data['data_type']['display_name_plural'],
                'url' => '',
                'target' => '_self',
                'icon_class' => $data['data_type']['icon'],
                'order' => $id + 3,
                'route' => "voyager.{$data['data_type']['slug']}.index",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * 連想配列から Voyager::BLEAD/Permissions 作成
     * @param array $data ['data_type' => array, 'data_rows' => array]
     */
    private function seedVoyagerBreadPermissions($data)
    {
        $permissions = ['browse', 'read', 'edit', 'add', 'delete'];
        $id = $data['data_type']['id'] * 5 + 12;
        foreach ($permissions as $index => $permission) {
            \DB::table('permissions')->updateOrInsert(
                ['id' => $id + $index],
                [
                    'id' => $id + $index,
                    'key' => "{$permission}_{$data['data_type']['name']}",
                    'table_name' => $data['data_type']['name'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
            // permission * role 紐付け
            $relData = ['permission_id' => $id + $index, 'role_id' => 1];
            \DB::table('permission_role')->updateOrInsert($relData, $relData);
        }
    }
    
    /**
     * Yamlファイル読み込み
     * @param string $filename
     * @return array
     */
    private function loadYaml($filename)
    {
        return Yaml::parse(file_get_contents($filename));
    }
}

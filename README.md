# api-admin

- ⚡ Admin control panel by Laravel/Voyager
- ⚡ API server by Express.js

## Environment

- Shell: `bash`
- Docker: `19.03.12`
    - docker-compose: `1.26.0`

### Setup
```bash
# Docker構成スクリプトに実行権限を付与
$ chmod +x ./x

# Docker構成テンプレートを展開
## すでに実行済みのため実行不要
$ ./x init
```

#### Structure
```bash
./
|_ docker/ # Dockerコンテナ設定
|  |_ db/ # db service container
|  |  |_ dump/ # mount => service://db:/var/dump/ (ダンプデータ等のやり取り用)
|  |  |_ initdb.d/ # mount => service://db:/docker-entrypoint-initdb.d/
|  |  |            # この中に配置した *.sql ファイルで初期データベースを構成可能
|  |  |_ my.cnf # mount => service://db:/etc/mysql/conf.d/my.cnf:ro (MySQL設定ファイル)
|  |
|  |_ web/ # web service container
|     |_ conf/
|     |  |_ 000-default.conf # mount => service://web:/etc/apache2/sites-available/000-default.conf (Apache設定ファイル)
|     |  |_ php.ini # mount => service://web:/etc/php.d/php.ini (PHP設定ファイル)
|     |
|     |_ Dockerfile # web service container build setting file
|
|_ www/ # mount => service://web:/var/www/ (~www-data)
|  |_ (app/) # プロジェクトディレクトリ (lamp スクリプトでセットアップする)
|  |   |_ public/ # DocumentRoot
|  |
|  |_ .msmtprc # SMTPサーバ接続設定ファイル
|  |_ startup.sh # web service container が開始したときに実行されるスクリプト (Apache実行等)
|
|_ .env # Dockerコンテナ実行ポート等の環境変数設定
|_ .gitignore
|_ docker-compose.yml # Docker構成設定
|_ x # Docker環境構成・各種操作用スクリプト
```

#### Docker containers
- networks:
    - **appnet**: `bridge`
        - この環境におけるDockerコンテナは全てこのネットワークに属する
- volumes:
    - **db-data**: `local`
        - db service container データ永続化用
    - **mongo-data**: `local`
        - mongo service container データ永続化用
- services:
    - **web**: `php:7.3-apache`
        - PHP + Apache Web Server
        - https://web.localhost => http://localhost:7080 => service://web:80
            - サーバ実行ポートは `WEB_PORT` 環境変数で変更可能
    - **db**: `mysql:5.7`
        - MySQL Datbase Server
        - tcp://localhost:7033 => service://db:3306
            - MySQL接続ポートは `DB_PORT` 環境変数で変更可能
        - Login:
            - User: `root`
            - Password: `root`
            - Database: `app`
    - **pma**: `phpmyadmin/phpmyadmin`
        - MySQL Database Admin Server
        - https://pma.localhost => http://localhost:8057 => service://phpmyadmin:80
            - 管理画面ポートは `PMA_PORT` 環境変数で変更可能
    - **mailhog**: `mailhog/mailhog`
        - SMTP Server + Mail catching sandbox environment
        - https://mail.localhost => http://localhost:7025 => service://mailhog:8025
            - 管理画面ポートは `MAILHOG_PORT` 環境変数で変更可能
            - SMTP接続ポートはポートフォワーディングなし (service://mailhog:1025)
    - **redis**: `redis:5`
        - Session Database & Cache Server
        - 接続: service://redis:6379
    - **momgo**: `mongo:4.4`
        - MongoDB Datbase Server
        - tcp://localhost:27017 => service://mongo:27017
            - MongoDB接続ポートは `MONGO_PORT` 環境変数で変更可能
        - Login:
            - User: `root`
            - Password: `root`
            - Database: `sekokan`
    - **express**: `mongo-express:latest`
        - MongoDB Database Admin Server
        - https://mongo.localhost => http://localhost:8081 => service://express:8081
            - 管理画面ポートは `MONGO_EXPRESS_PORT` 環境変数で変更可能
    - **proxy**: `jwilder/nginx-proxy`
        - http://localhost:80, https://localhost:443
        - 起動しているDockerコンテナを自動的に認識し、リクエストされたホスト名に紐付いているコンテナに振り分けを行うリバースプロキシサーバ
        - 紐付けるホスト名は、環境変数 `VIRTUAL_HOST` で指定する
        - 振り分け先のDockerコンテナは同一ネットワーク上で実行されている必要があるため、docker-compose.yml にネットワーク構築の設定を記述するか、`network_mode: bridge` でブリッジモード接続する必要がある
    - **letsencrypt**: `letsencrypt-nginx-proxy-companion`
        - 上記 nginx-proxy コンテナと連動して、SSL証明書を自動的に取得する Let's Encrypt コンテナ
        - これにより、面倒なSSL証明書発行の手間を省いて、https化することができる
        - 環境変数 `LETSENCRYPT_HOST` が定義されているコンテナを見つけると Let's Encrypt 申請を行うため、ローカル開発時は `LETSENCRYPT_HOST` は使わない
        - 代わりに `CERT_NAME` 環境変数に `default`（自己証明書）を指定する

### Usage
```bash
# Dockerコンテナ構築
## $ export USER_ID=$UID && docker-compose build
$ ./x build

# Dockerコンテナ起動
$ ./x up -d

# => Launch docker containers
## web service container: https://web.localhost => http://localhost:8080 => service://web:80
## db service container: tcp://localhost:8033 => service://db:3306
## pma service container: https://pma.localhost => http://localhost:8057 => service://pma:80
## mailhog service container: https://mail.localhost => http://localhost:8025 => service://mailhog:80
## redis service container: service://redis:6379
## mongo service container: tcp://localhost:27017
## express service container: https://mongo.localhost => http://localhost:8081
## proxy service container: http://localhost:80, https://localhost:443
##  |_ letsencrypt service container

# laravelプロジェクト作成
## $ export USER_ID=$UID && docker-compose exec -w '/var/www/' web composer create-project --prefer-dist laravel/laravel app '8.*' ...
$ ./x init-laravel-project

# => create laravel project: ./www/app/

# install laravel/voyager
$ ./x install-voyager

# => https://web.localhost/admin/
## - login email: admin@voyager.localhost
## - login password: admin
```

***

## Queue

[imTigger/laravel-job-status](https://github.com/imTigger/laravel-job-status) を使うことで状態監視可能な Queue Job を作成することができる

### Setup
Queue の揮発性データベースとして Redis を利用する

#### .env
```bash
# ...

# queue は redis サーバで管理
QUEUE_CONNECTION=redis

# docker.service://redis:6379 サーバを利用
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis
```

### Install imTigger/laravel-job-status
imTigger/laravel-job-status を使って Queue Job の状態管理を行えるようにする

このライブラリはDBテーブル `job_statuses` に、実行中 Job の状態を書き込んで状態管理を行う

- imTigger/laravel-job-status の仕組み
    - Job が Queue にスタックされたら `job_statuses.status` を `queued` に更新
    - Queue が Job を終了したときに `job_statuses.status` を `finished` に更新するコールバックを登録

```bash
# install
$ ./x web composer require imtigger/laravel-job-status

# ServiceProvider を発行
$ ./x web php artisan vendor:publish --provider="Imtigger\LaravelJobStatus\LaravelJobStatusServiceProvider"

# => following files will generate
## config/job-status.php
## database/migrations/***_create_job_statuses_table.php

# job_statuses テーブル作成
$ ./x web php artisan migrate
```

#### config/app.php
```php
// ...
'providers' => [
    // ...
    Imtigger\LaravelJobStatus\LaravelJobStatusServiceProvider::class,
]
```

### Test
以下のような Queue Job で動作確認を行う

#### app/Jobs/TestJob.php
```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Imtigger\LaravelJobStatus\Trackable;

class TestJob implements ShouldQueue
{
    // Trackable trait を使うことで job_statuses テーブルを使った状態管理ができるようになる
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->prepareStatus();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // ログを出力するだけの Job
        \Log::info('キュー実行完了');
    }
}
```

#### app/Console/Commands/TestCommand.php
```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TestJob;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::info('コマンド実行開始');

        // 非同期実行を明確化するために1分遅延させる
        $job = (new TestJob)->delay(now()->addMinutes(1));
        dispatch($job);

        // JobStatusId (DB.job_statuses.id) を取得
        dump($job->getJobStatusId());

        \Log::info('コマンド実行完了');
    }
}
```

#### app/Console/Commands/JobCommand.php
```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Imtigger\LaravelJobStatus\JobStatus;

class jobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:status {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 指定された id の Job の状態を取得
        dump(JobStatus::find($this->argument('id'))->status);
    }
}
```

#### Execution
```bash
# Laravel の Queue 処理 Worker を起動
## 本番稼働させる場合は、バックグラウンドで処理するためにデーモン化する必要がある
$ ./x web php artisan queue:work

# TestJob を TestCommand 経由で実行
$ ./x web php artisan test:queue

# => Queue に追加された Job の id を確認

# JobStatusId: 1 の Job の状態確認
$ ./x web php artisan job:status 1
```

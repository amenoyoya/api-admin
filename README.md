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
./ # mount to => service://node:/work/
|_ docker/ # Dockerコンテナ設定
|  |_ db/ # db service container
|  |  |_ dump/ # mount => service://db:/var/dump/ (ダンプデータ等のやり取り用)
|  |  |_ initdb.d/ # mount => service://db:/docker-entrypoint-initdb.d/
|  |  |            # この中に配置した *.sql ファイルで初期データベースを構成可能
|  |  |_ my.cnf # mount => service://db:/etc/mysql/conf.d/my.cnf:ro (MySQL設定ファイル)
|  |
|  |_ node/ # node service container
|  |  |_ Dockerfile # node service container build setting file
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
    - **node**: `node:12-alpine`
        - Node.js command line interface
        - http://localhost => service://node
            - hostサーバで実行される
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
## node service container: on host server
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

# strapiプロジェクト作成
## $ export USER_ID=$UID && docker-compose exec node yarn create strapi-app app
$ ./x node yarn create strapi-app app

# => strapi-app project settings: データベースに MongoDB を使うように設定する
## Choose your installation type: Custom (manual settings)
## Choose your default database client (Use arrow keys): mongo
## Database name: app
## Host: 127.0.0.1
## +srv connection: false
## Port (It will be ignored if you enable +srv): 27017
## Username: root
## Password: root
## Authentication database (Maybe "admin" or blank): (blank)
## Enable SSL connection (y/N): n

# strapi開発サーバ起動
## $ export USER_ID=$UID && docker-compose exec -w /work/app/ node yarn develop
$ w=./app/ ./x node yarn develop
```

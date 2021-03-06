#!/bin/bash

# Apache/PHP/MySQL 構成 + Node.js/MongoDB 構成

cd $(dirname $0)
export USER_ID="${USER_ID:-$UID}"

case "$1" in
"init")
    mkdir -p ./docker/certs/
    mkdir -p ./docker/db/dump/
    mkdir -p ./docker/db/initdb.d/
    mkdir -p ./docker/web/conf/
    mkdir -p ./docker/node/
    mkdir -p ./www/app/public/
    mkdir -p ./nodejs/
    tee ./docker/certs/.gitignore << \EOS
/*
!/.gitignore
EOS
    tee ./docker/db/dump/.gitignore << \EOS
/*
!/.gitignore
EOS
    touch ./docker/db/initdb.d/.gitkeep
    tee ./docker/db/my.cnf << \EOS
[mysqld]
character-set-server = 'utf8mb4'
[client]
default-character-set = 'utf8mb4'
EOS
    touch ./docker/web/conf/crontab
    tee ./docker/web/conf/supervisord.conf << \EOS
[supervisord]
nodaemon=true

[program:apache2]
; 実行コマンド
command=/usr/local/bin/apache2-foreground

; プロセス名（numprocsが1以上の場合は%(process_num)sを使用して重複を避ける）
process_name=%(program_name)s

; 実行ユーザ
user=root

; 終了コード
exitcodes=1

; 起動時のカレントディレクトリ
; directory = /home/dev-user/

; 自動リスタート
; true:常に再起動,false:常に再起動しない,unexpected:終了コードがexitcodesのあるもの以外なら再起動
autorestart=false

; この値より早く終了し場合に異常終了として扱う（超えて終了した場合は正常終了）
; startsecs = 0

; リスタートの試行回数
; startretries=0

; この値（秒）を超えた場合、SIGKILLを送信
; stopwaitsecs = 3600

; 子プロセスまでkillする
; stopasgroup = false

; SIGKILLをプロセスグループ全体に送信
; killasgroup = true

; logに関する設定
; ローテートするログファイル容量
logfile_maxbytes=50MB

; ローテートで残す世代数
logfile_backup=10

; ログファイル
stdout_logfile=/var/log/supervisor/%(program_name)s.log
stderr_logfile=/var/log/supervisor/%(program_name)s-error.log

[program:crond]
command=/bin/busybox crond -f
process_name=%(program_name)s
logfile_maxbytes=50MB
logfile_backup=10
stdout_logfile=/var/log/supervisor/%(program_name)s.log
stderr_logfile=/var/log/supervisor/%(program_name)s-error.log
EOS
    tee ./docker/web/conf/000-default.conf << \EOS
# Apacheバージョン非表示
ServerTokens ProductOnly
ServerSignature off

# HTTP TRACE 機能を無効化
TraceEnable off

<IfModule mod_headers.c>
    # PHPバージョン非表示
    ## PHP-FPM の場合は php.ini: expose_php を設定
    Header unset X-Powered-By

    # X-Frame-Options HTTP レスポンスヘッダ送信
    ## WordPress管理画面などで必要になる場合があればコメントアウトする
    Header append X-Frame-Options "DENY"

    # Strict-Transport-Security レスポンスヘッダ送信
    Header add Strict-Transport-Security "max-age=15768000"
</IfModule>

# .ht 系ファイル非表示
<Files ~ "^\.ht">
    Deny from all
</Files>

# VirtualHost: default site
<VirtualHost *:80>
    DocumentRoot /var/www/app/public/

    ErrorLog /var/log/httpd/error.log
    CustomLog /var/log/httpd/access.log combined

    <Directory /var/www/app/>
        # indexの存在しないディレクトリアクセス時、ファイルリストを表示させない: -Indexes
        # 拡張子なしURLを拡張子有ファイルにリダイレクト可能に（コンテントネゴシエーション無効化）: -MultiViews
        Options -Indexes -MultiViews +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOS
    tee ./docker/web/conf/php.ini << \EOS
; debug: display errors
display_errors = on

; hide php version info
expose_php = off

; sendmail => msmtp (mstmp-mta: replace msmtp => default MTA: sendmail)
;; SMTP seetings =>  ~/.msmtprc
sendmail_path = "/usr/bin/msmtp -t -i"

; Japanese settings
[Date]
date.timezone = "Asia/Tokyo"
[mbstring]
mbstring.internal_encoding = "UTF-8"
EOS
    tee ./docker/web/Dockerfile << \EOS
FROM php:7.3-apache

# composer 2 インストール
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV PATH $PATH:~/.composer/vendor/bin

# 開発に必要なパッケージ等のインストール
RUN apt-get update && \
    apt-get install -y wget curl git jq libicu-dev mailutils unzip vim \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libxml2-dev && \
    docker-php-ext-install mbstring intl gd xml mysqli pdo pdo_mysql && \
    : 'install php-pecl-redis' && \
    pecl install redis-5.1.1 && docker-php-ext-enable redis && \
    : 'create log directory' && \
    mkdir -p /var/log/httpd/ && \
    : 'enable apache2 modules' && \
    a2enmod rewrite && \
    a2enmod headers && \
    a2enmod ssl && \
    : 'install supervisor, busybox' && \
    apt-get install -y supervisor busybox-static && \
    mkdir -p /var/log/supervisor/ && \
    : 'install msmtp (sendmail 互換の送信専用 MTA; ssmtp の後継)' && \
    : 'msmtp-mta も入れておくとデフォルトの MTA を sendmail から置き換えてくれるため便利' && \
    apt-get install -y msmtp msmtp-mta && \
    : 'install docker client' && \
    apt-get install -y apt-transport-https ca-certificates curl gnupg2 software-properties-common && \
    curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add - && \
    add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/debian $(lsb_release -cs) stable" && \
    apt-get update && apt-get install -y docker-ce && \
    gpasswd -a www-data docker && \
    : 'docker exec を service 名で実行できるようにするスクリプトを追加' && \
    echo '#!/bin/bash\ndocker exec -i $opt `docker ps --format {{.Names}} | grep $1` "${@:2:($#-1)}"' | tee /usr/local/bin/docker-exec && \
    chmod +x /usr/local/bin/docker-exec && \
    : 'nodeコンテナで nodejs を実行するコマンド alias を追加' && \
    echo '#!/bin/bash\ndocker exec -iw "${w:-/work/nodejs/}" `docker ps --format {{.Names}} | grep node` node "$1.js" "${@:2:($#-1)}"' | tee /usr/local/bin/nodejs && \
    chmod +x /usr/local/bin/nodejs && \
    : 'www-data ユーザで sudo 実行可能に' && \
    apt-get install -y sudo && \
    echo 'www-data ALL=NOPASSWD: ALL' >> '/etc/sudoers' && \
    : 'cleanup apt-get caches' && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# 作業ディレクトリ
## docker://web:/var/www/ => host://./www/
## DocumentRoot: docker://web:/var/www/app/public/
WORKDIR /var/www/app/

# 作業者: www-data
USER www-data

# スタートアップコマンド（docker up の度に実行される）
## 環境変数を引き継いで sudo 実行するため -E オプションをつけている
## execute docker://web:/var/www/startup.sh
CMD ["sudo", "-E", "/bin/bash", "/var/www/startup.sh"]
EOS
    tee ./docker/node/Dockerfile << \EOS
FROM mcr.microsoft.com/playwright

# Docker実行ユーザIDを環境変数から取得
ARG UID

RUN : '日本語対応' && \
    apt-get update && \
    apt-get -y install locales fonts-ipafont fonts-ipaexfont && \
    echo "ja_JP UTF-8" > /etc/locale.gen && locale-gen && \
    : 'playwrightインストール' && \
    yarn global add playwright && \
    : 'Add user $UID if not exists' && \
    if [ "$(getent passwd $UID)" = "" ]; then useradd -u $UID worker; fi && \
    : '$UID ユーザで sudo 実行可能に' && \
    apt-get install -y sudo && \
    echo "$(getent passwd $UID | cut -f 1 -d ':') ALL=NOPASSWD: ALL" >> '/etc/sudoers' && \
    : 'Fix permission' && \
    mkdir -p /usr/local/share/.config/ && \
    chown -R $UID /usr/local/share/.config/ && \
    : 'cleanup apt-get caches' && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# 作業ディレクトリ: ./ => service://node:/work/
WORKDIR /work/

# 作業ユーザ: Docker実行ユーザ
## => コンテナ側のコマンド実行で作成されるファイルパーミッションをDocker実行ユーザ所有に
USER $UID
EOS
    tee ./www/.gitignore << \EOS
/.*
!/.gitignore
!/.msmtprc
supervisord.*
EOS
    tee ./www/.msmtprc << \EOS
# Set default values for all following accounts.
defaults
auth on
tls on
logfile ~/.msmtp.log

# local smtp server: service://mailhog:1025
account mailhog
host mailhog
port 1025
from root@localhost
## mailhog not require authentication
auth off
tls off

# google smtp server (example)
account gmail
host smtp.gmail.com
port 587
from youraccount@gmail.com
user youraccount@gmail.com
password yourpassword

# Set a default account
account default : mailhog
EOS
    tee ./www/startup.sh << \EOS
#!/bin/bash
# -- sudo www-data@service://web/

# 環境変数 UID が与えられていれば www-data ユーザIDを $UID に合わせる
if [ "$UID" != "" ]; then
    # www-data ユーザIDを変更
    usermod -u $UID www-data
    # www-data のホームディレクトリのパーミッション修正
    chown -R www-data:www-data /var/www/
fi

# ~/.msmtprc のパーミッション修正
chown www-data:www-data /var/www/.msmtprc
chmod 600 /var/www/.msmtprc

# install crontab
busybox crontab /var/spool/cron/crontabs/www-user

# supervisor 起動
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOS
    if [ ! -e './www/app/public/index.php' ]; then
        echo '<?php phpinfo() ?>' > ./www/app/public/index.php
    fi
    echo '{}' > ./nodejs/package.json
    echo 'node_modules/' > ./.gitignore
    tee ./.env << \EOS
WEB_PORT=8080
DB_PORT=8033
PMA_PORT=8057
MAILHOG_PORT=8025
MONGO_PORT=27017
MONGO_EXPRESS_PORT=8081
EOS
    tee ./docker-compose.yml << \EOS
# ver 3.6 >= required: enable '-w' option for 'docker-compose exec'
version: "3.8"

networks:
  # プロジェクト内仮想ネットワーク
  ## 同一ネットワーク内の各コンテナはサービス名で双方向通信可能
  appnet:
    driver: bridge
    # ネットワークIP範囲を指定する場合
    # ipam:
    #   driver: default
    #   config:
    #     # 仮想ネットワークのネットワーク範囲を指定
    #     ## 172.68.0.0/16 の場合、172.68.0.1 ～ 172.68.255.254 のIPアドレスを割り振れる
    #     ## ただし 172.68.0.1 はゲートウェイに使われる
    #     - subnet: 172.68.0.0/16

volumes:
  # volume for db service container 
  db-data:
    driver: local
  # volume for mongo service container 
  mongo-data:
    driver: local

services:
  # web service container: php + apache
  web:
    build: ./docker/web/
    logging:
      driver: json-file
    # restart: always
    # 所属ネットワーク
    networks:
      - appnet
    # DNSサーバにGoogleDNS利用
    dns: 8.8.8.8
    # ポートフォワーディング
    ports:
      # http://localhost:${WEB_PORT} => service://web:80
      - "${WEB_PORT:-8080}:80"
    volumes:
      # ~www-data: host://./www/ => docker://web:/var/www/
      ## DocumentRoot: host://./www/app/public/ => docker://web:/var/app/public/
      - ./www/:/var/www/
      # 設定ファイル
      - ./docker/web/conf/000-default.conf:/etc/apache2/sites-available/000-default.conf
      - ./docker/web/conf/php.ini:/usr/local/etc/php/conf.d/php.ini
      - ./docker/web/conf/supervisord.conf:/etc/supervisor/conf.d/supervisord.conf
      - ./docker/web/conf/crontab:/var/spool/cron/crontabs/www-user
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
    environment:
      # USER_ID: www-data のユーザIDを docker 実行ユーザIDに合わせたい場合に利用 (export USER_ID=$UID)
      ## ユーザIDを合わせないと ./www/ (docker://web:/var/www/) 内のファイル編集が出来なくなる
      UID: ${USER_ID}
      # Composer設定
      COMPOSER_ALLOW_SUPERUSER: 1 # root権限での実行を許可
      COMPOSER_NO_INTERACTION: 1  # 非対話的にインストール
      # MySQL接続設定
      MYSQL_HOST: db
      MYSQL_PORT: 3306
      MYSQL_USER: root
      MYSQL_PASSWORD: root
      MYSQL_DATABASE: app
      # TimeZone設定
      TZ: Asia/Tokyo
  
  # db service container: MySQL server
  db:
    image: mysql:5.7
    logging:
      driver: json-file
    # restart: always
    # ポートフォワーディング
    ports:
      # tcp://localhost:${DB_PORT} => service://db:3306
      - "${DB_PORT:-8033}:3306"
    # 所属ネットワーク
    networks:
      - appnet
    volumes:
      # データ永続化: docker-volume.db-data => docker.db:/var/lib/mysql
      - db-data:/var/lib/mysql
      # MySQL設定ファイル: host:/./docker/db/my.cnf => /etc/mysql/conf.d/my.cnf 644
      - ./docker/db/my.cnf:/etc/mysql/conf.d/my.cnf:ro
      # ダンプデータやり取り用
      - ./docker/db/dump/:/var/dump/
      # 初回投入データ: ./docker/db/initdb.d/
      - ./docker/db/initdb.d/:/docker-entrypoint-initdb.d/
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
    working_dir: /var/dump/
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: app
      # TimeZone設定
      TZ: Asia/Tokyo
  
  # pma service container: phpMyAdmin
  pma:
    image: phpmyadmin/phpmyadmin
    logging:
      driver: json-file
    # 所属ネットワーク
    networks:
      - appnet
    # ポートフォワーディング
    ports:
      # http://localhost:${PMA_PORT} => service://pma:80
      - "${PMA_PORT:-8057}:80"
    volumes:
      - /sessions
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
    environment:
      PMA_ARBITRARY: 1
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: root
  
  # mailhog service container: local SMTP server + Mail catcher
  mailhog:
    image: mailhog/mailhog
    logging:
      driver: json-file
    # 所属ネットワーク
    networks:
      - appnet
    # ポートフォワーディング
    ports:
      # http://localhost:${MAILHOG_PORT} => service://mailhog:8025
      - "${MAILHOG_PORT:-8025}:8025"
      # - "1025" # SMTP Port: ホスト側ポートはランダムに選出
    volumes:
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/

  # redis service container: session database + cache server
  redis:
    image: redis:6
    logging:
      driver: json-file
    # 所属ネットワーク
    networks:
      - appnet
    volumes:
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
  
  # node service container: node:12-alpine
  # $ docker-compose exec node $command ...
  node:
    build:
      context: ./docker/node/
      args:
        # use current working user id
        UID: $USER_ID
    logging:
      driver: json-file
    # tcp://localhost:<port> => service://node:<port>
    network_mode: host
    # enable terminal
    tty: true
    volumes:
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
    environment:
      TZ: Asia/Tokyo

  # mongo service container: mongo db v4.4
  mongo:
    image: mongo:4.4
    logging:
      driver: json-file
    # restart: always
    # 所属ネットワーク
    networks:
      - appnet
    ports:
      # tcp://localhost:${MONGO_PORT} => service://mongo:27017
      - ${MONGO_PORT:-27017}:27017
    volumes:
      # database data persistence
      - mongo-data:/data/db/
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
    environment:
      MONGO_INITDB_ROOT_USERNAME: root
      MONGO_INITDB_ROOT_PASSWORD: root
      TZ: Asia/Tokyo

  # express service container: mongo-express:latest
  express:
    image: mongo-express:latest
    logging:
      driver: json-file
    # 所属ネットワーク
    networks:
      - appnet
    ports:
      # http://localhost:${MONGO_EXPRESS_PORT} => service://express:8081
      - ${MONGO_EXPRESS_PORT:-8081}:8081
    volumes:
      # Docker socket 共有
      - /var/run/docker.sock:/var/run/docker.sock
      - ./:/work/
    environment:
      ME_CONFIG_MONGODB_ADMINUSERNAME: root
      ME_CONFIG_MONGODB_ADMINPASSWORD: root
      ME_CONFIG_MONGODB_SERVER: mongo # MongoDB: service://mongo:27017
      ME_CONFIG_MONGODB_PORT: 27017
      TZ: Asia/Tokyo
    
  # --- local nginx proxy: port 80, 443 を専有するため、別のプロジェクト開発時には停止する ---
  ## local nginx proxy を停止する場合: $ docker-compose stop proxy
  proxy:
    image: steveltn/https-portal:1
    logging:
      driver: json-file
    # 所属ネットワーク
    networks:
      - appnet
    ports:
      - '80:80'
      - '443:443'
    volumes:
      - /var/run/docker.sock/:/tmp/docker.sock/:ro
    environment:
      STAGE: local # 本番環境の場合は production を指定（実際の Let's Encrypt に SSL 申請を行う）
      DOMAINS: 'web.localhost -> http://web:80, pma.localhost -> http://pma:80, mail.localhost -> http://mailhog:8025, mongo.localhost -> http://express:8081'

EOS
    ;;
"init-laravel-project")
    rm -rf ./www/app/
    
    docker-compose exec -w '/var/www/' web composer create-project --prefer-dist laravel/laravel app '8.*'
    sed -i 's/^APP_URL=.*/APP_URL=https:\/\/web.localhost/g' ./www/app/.env
    sed -i 's/^DB_HOST=.*/DB_HOST=db/g' ./www/app/.env
    sed -i 's/^DB_DATABASE=.*/DB_DATABASE=app/g' ./www/app/.env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=root/g' ./www/app/.env
    sed -i 's/^MAIL_HOST=.*/MAIL_HOST=mailhog/g' ./www/app/.env
    sed -i 's/^MAIL_PORT=.*/MAIL_PORT=1025/g' ./www/app/.env
    # cache, session, queue の揮発性データベースとして redis 利用
    sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/g' ./www/app/.env
    sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/g' ./www/app/.env
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/g' ./www/app/.env
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/g' ./www/app/.env
    # redis client として php-pecl-redis を利用
    sed -i '/^REDIS_PORT=.*/a REDIS_CLIENT=phpredis' ./www/app/.env
    # 日本語化
    sed -i "s/'en'/'ja'/g" ./www/app/config/app.php
    sed -i "s/'UTC'/'Asia\/Tokyo'/g" ./www/app/config/app.php
    # https 対応
    tee ./www/app/app/Providers/AppServiceProvider.php << \EOS
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // AWS ALB -> EC2 間通信が http の場合に Mixed Content が発生するため修正
        \URL::forceRootUrl(config('app.url'));
        if (preg_match('/^https:/', config('app.url'))) {
            \URL::forceScheme('https');
        }
    }
}
EOS
    # laravel cron と queue:work サービス登録
    if [ "$(cat ./docker/web/conf/crontab | grep 'php artisan schedule:run')" = "" ]; then
        echo '* * * * * (echo "$(date): laravel schedule:run"; cd /var/www/app && php artisan schedule:run) > /var/www/.laravel_schedule_run.log' >> ./docker/web/conf/crontab
    fi
    if [ "$(cat ./docker/web/conf/supervisord.conf | grep 'laravel_queue_work')" = "" ]; then
        tee -a ./docker/web/conf/supervisord.conf << \EOS
[program:laravel_queue_work]
command=php artisan queue:work
directory=/var/www/app/
process_name=%(program_name)s
logfile_maxbytes=50MB
logfile_backup=10
stdout_logfile=/var/log/supervisor/%(program_name)s.log
stderr_logfile=/var/log/supervisor/%(program_name)s-error.log
EOS
    fi
    ;;
"install-voyager")
    docker-compose exec web composer require tcg/voyager
    docker-compose exec web php artisan voyager:install
    
    # voyager:admin 作成
    ## email: admin@voyager.localhost
    ## name: admin
    ## password: admin
    ## confirm password: admin
    docker-compose exec -T web php artisan voyager:admin admin@voyager.localhost --create << \PROMPT
admin
admin
admin
PROMPT
    # 日本語化
    cp -r ./www/app/vendor/tcg/voyager/publishable/lang/ja ./www/app/resources/lang/
    sed -i "s/'en'/'ja'/g" ./www/app/config/voyager.php
    ;;
"web")
    if [ "$w" != "" ]; then
        docker-compose exec -Tw "$w" web "${@:2:($#-1)}"
    else
        docker-compose exec -T web "${@:2:($#-1)}"
    fi
    ;;
"node")
    docker-compose exec -Tw "${w:-/work/nodejs/}" node "${@:2:($#-1)}"
    ;;
*)
    docker-compose $*
    ;;
esac
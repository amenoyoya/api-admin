FROM php:7.3-apache

# composer 2 インストール
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV PATH $PATH:~/.composer/vendor/bin

# 開発に必要なパッケージ等のインストール
RUN apt-get update && \
    apt-get install -y wget curl git libicu-dev mailutils unzip vim \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libxml2-dev && \
    docker-php-ext-install mbstring intl gd xml mysqli pdo pdo_mysql && \
    : 'install php-pecl-redis' && \
    pecl install redis-5.1.1 && docker-php-ext-enable redis && \
    : 'create log directory' && \
    mkdir -p /var/log/httpd/ && \
    : 'install msmtp (sendmail 互換の送信専用 MTA; ssmtp の後継)' && \
    : 'msmtp-mta も入れておくとデフォルトの MTA を sendmail から置き換えてくれるため便利' && \
    apt-get install -y msmtp msmtp-mta && \
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

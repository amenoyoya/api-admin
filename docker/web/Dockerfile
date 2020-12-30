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
    echo '#!/bin/bash\ndocker exec -iw "${w:-/work/nodejs/}" `docker ps --format {{.Names}} | grep node` "$1.js" "${@:2:($#-1)}"' | tee /usr/local/bin/nodejs && \
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

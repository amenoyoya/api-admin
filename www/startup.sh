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

# Apache をフォアグランドで起動
a2enmod rewrite
a2enmod headers
a2enmod ssl
apachectl -D FOREGROUND

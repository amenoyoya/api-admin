FROM node:12-alpine

# Docker実行ユーザIDを環境変数から取得
ARG UID

RUN apk update && apk upgrade && \
    apk add --no-cache git python python3 g++ make && \
    rm -rf /var/cache/apk/* && \
    yarn global add codeceptjs && \
    : 'install chromium browser' && \
    apk add --no-cache chromium nss freetype freetype-dev harfbuzz ca-certificates ttf-freefont && \
    : 'Add user $UID if not exists' && \
    if [ "$(getent passwd $UID)" = "" ]; then useradd -S -u $UID worker; fi && \
    : 'Fix permission' && \
    mkdir -p /usr/local/share/.config/ && \
    chown -R $UID /usr/local/share/.config/ && \
    : '$UID ユーザで sudo 実行可能に' && \
    apk add --no-cache sudo && \
    echo "$(getent passwd $UID | cut -f 1 -d ':') ALL=NOPASSWD: ALL" >> '/etc/sudoers'

# 作業ディレクトリ: ./ => service://node:/work/
WORKDIR /work/

# 作業ユーザ: Docker実行ユーザ
## => コンテナ側のコマンド実行で作成されるファイルパーミッションをDocker実行ユーザ所有に
USER $UID

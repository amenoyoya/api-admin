FROM node:12-alpine

# Docker実行ユーザIDを環境変数から取得
ARG UID

RUN apk update && apk upgrade && \
    apk add --no-cache git python python3 g++ make && \
    rm -rf /var/cache/apk/* && \
    yarn global add strapi && \
    : 'Add user $UID if not exists' && \
    if [ "$(getent passwd $UID)" = "" ]; then useradd -S -u $UID worker; fi && \
    : 'Fix permission' && \
    mkdir -p /usr/local/share/.config/ && \
    chown -R $UID /usr/local/share/.config/

# 作業ディレクトリ: ./ => service://node:/work/
WORKDIR /work/

# 作業ユーザ: Docker実行ユーザ
## => コンテナ側のコマンド実行で作成されるファイルパーミッションをDocker実行ユーザ所有に
USER $UID

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
#!/bin/sh

SCRIPT_DIR=$(cd $(dirname $0); pwd)

echo -n '新しいパスワード: '
read newpass

if [ ${#newpass} -lt 8 ]; then
    echo "8文字以上のパスワードを指定して下さい"
fi

# 環境設定のロード
eval $(php $SCRIPT_DIR/loadconf.php)

if [ "$APPLICATION_ENV" = "production" ]; then
    echo "production 環境では使わないで下さい"
    exit
fi

# パスワード生成
PASS_MD5=$(echo $newpass | php -R 'echo md5($argn)."\n";')

# ユーザテーブル名
TABLE="${PREFIX}_user"

# 書き換え実行
echo "UPDATE ${TABLE} SET passwd = '$PASS_MD5' WHERE uid = 1"  | sh $SCRIPT_DIR/console.sh


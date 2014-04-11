#!/bin/sh

#
# モジュールインストールスクリプト
#

TOP=$(cd ../../; /bin/pwd)
WEBAPP_DIR=$TOP/webapp
TOOL_DIR=$WEBAPP_DIR/tool
HTML_TOP=$TOP/html
MODULE_TOP=$TOP/webapp/modules
PHP=/Applications/MAMP/bin/php/php5.4.4/bin/php

#
# 環境チェック
#
function getEnvironment() {
    if [ ! -f $HTML_TOP/.htaccess ]; then
        echo "==> ERROR : not found,  $HTML_TOP/.htaccess";
        exit 1;
    fi
    grep APPLICATION_ENV $HTML_TOP/.htaccess | grep -v ^# | awk '{ print $3 }'
}

#
# データベース情報取得
#
function getDatabaseInfo() {
    WEBAPP_CUSTOM_CONFIG=$WEBAPP_DIR/config/custom/config.ini
    WEBAPP_LEGACY_CONFIG=$WEBAPP_DIR/config/config.ini
    if [ -f $WEBAPP_CUSTOM_CONFIG ]; then
        CONF=$WEBAPP_CUSTOM_CONFIG
    elif [ -f $WEBAPP_LEGACY_CONFIG ]; then
        CONF=$WEBAPP_LEGACY_CONFIG
    else
        echo "==> ERROR: not found webapp config";
        exit 1;
    fi
    $PHP -r "
        require './CodeTool.php';
        \$tool = new CodeTool();
        \$tool->setupEnv();
        \$target_section = \$tool->getApplicationEnv();
        \$tool->loadConfig('$CONF', \$target_section);
        \$conf = \$tool->getConfig();
        echo 'export DB_TYPE=',    \$conf->db->type,          PHP_EOL;
        echo 'export DB_USER=',    \$conf->db->dsn->username, PHP_EOL;
        echo 'export DB_PASS=',    \$conf->db->dsn->password, PHP_EOL;
        echo 'export DB_NAME=',    \$conf->db->dsn->dbname,   PHP_EOL;
        echo 'export DB_HOST=',    \$conf->db->dsn->host,     PHP_EOL;
        echo 'export DB_PREFIX=',  \$conf->db->prefix,        PHP_EOL;
        echo 'export DB_CHARSET=', \$conf->db->charset,       PHP_EOL;
    "
}

function createTables() {
    TARGET_DIR=$1

    cd $TARGET_DIR
    find . -maxdepth 1 -type d -print | while read DIR
    do
        if [ "$DIR" = "." -o "$DIR" = ".." ]; then
            continue;
        fi

        DIRNAME=$(basename $DIR)

        SQL=$TARGET_DIR/$DIRNAME/sql/${DB_TYPE}.sql
        META=$TARGET_DIR/$DIRNAME/sql/meta.xml

        if [ ! -f $SQL ]; then

            # meta.xml があれば metaprocessor.php で sql ファイルを生成する
            if [ -f $META ]; then
                pushd $TOOL_DIR > /dev/null
                $PHP ./metaprocessor.php $META
                popd > /dev/null
            fi

        fi

        if [ -f $SQL ]; then
            # webapp/tool ディレクトリに移動して alter.sh を実行
            pushd $TOOL_DIR > /dev/null
            echo "/bin/sh ./alter.sh $DIRNAME $SQL"

            # alter.sh が "y" で続行するよう echo しつつ呼び出す
            echo "y" | /bin/sh ./alter.sh $DIRNAME $SQL

            popd > /dev/null
        else
            echo "-----> not found: mod = $DIRNAME, file = $SQL"
        fi
    done
}

$(getDatabaseInfo)
createTables $MODULE_TOP;


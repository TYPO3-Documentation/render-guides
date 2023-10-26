#!/usr/bin/env bash
set -e

SCRIPT_DIR=$(dirname "$0")

if [ ! -f $SCRIPT_DIR/box.phar ]; then
    echo "box.phar not found, downloading..."
    curl -L https://github.com/box-project/box/releases/download/4.4.0/box.phar --output $SCRIPT_DIR/box.phar
    chmod +x $SCRIPT_DIR/box.phar
fi

composer remove --dev ergebnis/composer-normalize phpstan/extension-installer
php $SCRIPT_DIR/box.phar compile

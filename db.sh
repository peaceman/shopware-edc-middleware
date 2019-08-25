#!/usr/bin/env bash

BACKUP_FOLDER="storage/backups"
DB_NAME="$(grep DB_DATABASE .env | sed -e 's/^[^=]*=//')"

backup() {
    echo "backup $DB_NAME"
    mysqldump --add-drop-database --databases "$DB_NAME" > "$BACKUP_FOLDER/$DB_NAME-$(date +%Y%m%d-%H%M%S).sql"
}

restore() {
    LAST_BACKUP_FILE="$(ls -t $BACKUP_FOLDER/$DB_NAME* | head -n1)"

    echo "restore $LAST_BACKUP_FILE"
    mysql < "$LAST_BACKUP_FILE"
}

${1:-backup}


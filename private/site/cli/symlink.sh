#!/bin/bash

#https://stackoverflow.com/a/246128
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"

# shellcheck disable=SC2034,SC2046
PUBLIC="$(dirname $(dirname $(dirname "${DIR}")))/public"

#https://serverfault.com/a/295591/377751

#-------------------------- install

find "${DIR}/enabled/install" -maxdepth 1 -type l -delete

ln -sfn "${DIR}/available/clearBuilder.php" "${DIR}/enabled/install/01-clearBuilder.php"

#-------------------------- update

find "${DIR}/enabled/update" -maxdepth 1 -type l -delete

ln -sfn "${DIR}/available/migration.php" "${DIR}/enabled/update/01-migration.php"
ln -sfn "${DIR}/available/clearCache.php" "${DIR}/enabled/update/02-clearCache.php"
ln -sfn "${DIR}/available/browscap.php" "${DIR}/enabled/update/03-browscap.php"
ln -sfn "${DIR}/available/deleteSymlink.php" "${DIR}/enabled/update/04-deleteSymlink.php"
ln -sfn "${DIR}/available/deleteTmp.php" "${DIR}/enabled/update/05-deleteTmp.php"

#-------------------------- daily

find "${DIR}/enabled/daily" -maxdepth 1 -type l -delete

ln -sfn "${DIR}/available/browscap.php" "${DIR}/enabled/daily/01-browscap.php"
#ln -sfn "${DIR}/available/dumpDb.php" "${DIR}/enabled/daily/02-dumpDb.php"
ln -sfn "${DIR}/available/deleteLog.php" "${DIR}/enabled/daily/03-deleteLog.php"
ln -sfn "${DIR}/available/deleteSymlink.php" "${DIR}/enabled/daily/04-deleteSymlink.php"
ln -sfn "${DIR}/available/deleteTmp.php" "${DIR}/enabled/daily/05-deleteTmp.php"

#-------------------------- hourly

find "${DIR}/enabled/hourly" -maxdepth 1 -type l -delete

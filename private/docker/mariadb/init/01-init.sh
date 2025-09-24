#!/bin/bash

set -eEuo pipefail
shopt -s inherit_errexit

#https://github.com/docker-library/mysql/issues/213#issuecomment-246849213
#https://stackoverflow.com/a/56774722
#https://stackoverflow.com/a/44675081

if [ -n "${MARIADB_ROOT_USER}" ] && [ -n "${MARIADB_ROOT_PASSWORD}" ]; then

	#https://stackoverflow.com/a/14454465
	#https://github.com/docker-library/mysql/issues/229
	mariadb-tzinfo-to-sql /usr/share/zoneinfo | mariadb -D mysql -u"${MARIADB_ROOT_USER}" -p"${MARIADB_ROOT_PASSWORD}"

	if [ -n "${MARIADB_DATABASES}" ]; then

		IFS=',' read -ra databases <<<"${MARIADB_DATABASES}"

		for i in "${databases[@]}"; do
			mariadb --protocol=socket -u"${MARIADB_ROOT_USER}" -p"${MARIADB_ROOT_PASSWORD}" <<EOSQL
CREATE DATABASE IF NOT EXISTS \`$i\`;
EOSQL
		done

	fi

fi

#!/usr/bin/env bash
##
# Create migration source database with sample data.
#
# This script populates the migration source database with a `categories` table
# containing sample data for demonstration purposes.
#
# Usage:
#   DB_URL=mysql://user:pass@host:port/dbname ./create-migrate-source-db.sh
#
# Or using drush:
#   $(drush sql:connect) < <(./create-migrate-source-db.sh --sql)

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

sql=$(
  cat <<'SQL'
DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Jedi Order', 'Ancient order of Force-sensitive protectors of peace and justice in the galaxy.'),
(2, 'Sith Lords', 'Dark side practitioners who seek power and dominion over the galaxy.'),
(3, 'Galactic Empire', 'Authoritarian government that ruled the galaxy through fear and military might.'),
(4, 'Rebel Alliance', 'Coalition of resistance fighters opposing the tyranny of the Galactic Empire.'),
(5, 'Bounty Hunters', 'Mercenaries and trackers who pursue targets for credits across the galaxy.'),
(6, 'Smugglers', 'Independent pilots and traders who operate outside the law for profit.'),
(7, 'Droids', 'Mechanical beings serving various roles from protocol to astromech duties.'),
(8, 'Mandalorians', 'Warrior culture known for their beskar armor and combat traditions.'),
(9, 'Wookiees', 'Tall, fur-covered species from Kashyyyk known for their strength and loyalty.'),
(10, 'Hutts', 'Powerful crime lords who control vast criminal enterprises across the Outer Rim.');
SQL
)

if [ "${1:-}" = "--sql" ]; then
  printf '%s\n' "${sql}"
  exit 0
fi

echo "${sql}"

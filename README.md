KilikBackup - Scripts suite to backup files and MySQL databases
--

Project state: planning in progress

Working features:
- backup files with rsync
- purge automatically old backup (with simple maintain rules)

Planned features:
- templates configuration files
- incremental backup
- mysqld and mysqld_multi backup
- consistent snapshots (with lvm volumes)
- logging
- send results API

Configuration sample:
check app/config/demo.json.

Short documentation of .json configuration file:
- time: array for time rules
- time.'rulename'.day_of_week: day of week (* or 1 or 1,7)
- time.'rulename'.day: day of month (* or 1 or 1,31)
- time.'rulename'.month: month (* or 1 or 1,12)
- time.'rulename'.delay: time expression (ex: 10 days or 6 months)
- time.repository.path: main repository location (to store your backups)
- time.rsync.options: default rsync options
- time.servers: array
- time.servers.'servername': server name (ex: myserver)
- time.servers.'servername'.hostname: server hostname (ex: myserver.com)
- time.servers.'servername'.snapshots: not documented feature
- time.servers.'servername'.rsync.options: options to replace global options
- time.servers.'servername'.backups: array
- time.servers.'servername'.backups.'backupname': config of backup (ex: www)
- time.servers.'servername'.backups.'backupname'.type: not available
- time.servers.'servername'.backups.'backupname'.snapshot: config of snapshots (see app/config/*.json for examples)
- time.servers.'servername'.backups.'backupname'.path: remote path to backup
- time.servers.'servername'.backups.'backupname'.rsync.options: options to replace global or server options
- time.servers.'servername'.backups.'backupname'.rsync.more_options: options to add to server or global rsync options

Create the binary (phar.readonly should be Off): 
php build.php
sudo cp backup.phar /bin/backup.phar

Usage exemple (without executable, use php main.php instead):
- backup all servers: backup.phar --config /etc/backup.json --backup all
- purge old backups: backup.phar --config /etc/backup.json --purge
- display help: backup.phar --help

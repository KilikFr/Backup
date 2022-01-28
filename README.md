# KilikBackup - Scripts suite to backup files and MySQL databases

Project state: beta

Working features:
- backup files with rsync
- create lvm (Logical Volume Manager) snapshots for consistent saves
- purge automatically old backup (with simple maintain rules)
- custom remote command lines (top stop/start services)
- samples configuration files (in /app/config)

Planned features:
- logging
- send results via API

Configuration sample:
check doc/config/*.json.

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

## Install from binary

```shell
wget -O /usr/local/bin/backup.phar "https://github.com/KilikFr/Backup/releases/latest/download/backup.phar"
chmod +x /usr/local/bin/backup.phar
```

## Or use docker image

```shell
mkdir backup
wget -O backup/config.yml "https://github.com/KilikFr/Backup/releases/latest/download/doc/config/simple-backup.yml"
# edit config
nano backup/config.yml
# then run
docker run --rm -it --name kilik-backup -v $(pwd)/backup:/backup -v $HOME:/root kilik/backup backup

cd kilik-backup
wget -O docker-compose.yml "https://github.com/KilikFr/Backup/releases/latest/download/docker-compose.yml.dist"

wget -O /usr/local/bin/backup.phar "https://github.com/KilikFr/Backup/releases/latest/download/backup.phar"
chmod +x /usr/local/bin/backup.phar
```

## Work on this project

### Work on sources

```shell
git clone https://github.com/KilikFr/Backup.git backup
cd backup
make pull
make up
```

### Build the binary

```shell
make build-phar
```

Note: backup.phar need php 8.0 to run

### Build the docker image

```shell
make build-docker
```

Usage exemple (without executable, use php main.php instead):
- backup all servers: backup.phar backup --config=/etc/backup.json all
- purge old backups: backup.phar purge --config=/etc/backup.json
- display help: backup.phar --help

# upgrade from 0.x to 1.x

phar version:

| old command                    | new command                    |
|--------------------------------|--------------------------------|
| --backup <servers> [<backup>]  | backup <servers> [<backup>]    |
| --purge                        | purge                          |
| --check-config                 | check-config                   |

| old options                    | new options             |
|--------------------------------|-------------------------|
| --config <config-file>         | --config=<config-file>  |

| default configuration | old                      | new default configuration |
|-----------------------|--------------------------|---------------------------|
| config-file           | /etc/kilik.backup.json   | /backup/config.json       |

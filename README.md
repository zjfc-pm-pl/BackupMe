# BackupMe
Implements the "Pterodactyl backup system" but can also backup your server without restarting it and mainly made for those who don't use Pterodactyl to use
**This plugin implements a backup system that works like the "Pterodactyl backup system"**
# Configuration
## Options
| Option | Description |
| ------ | ----------- |
| `enable-plugin` | The plugin power switch. **If this option is false, the plugin will disable on load.** |
| `backup-name` | The name format that the backup archive file will be used, please see the available name format tag list! |
| `file-checker-interval` | The interval in seconds between every time the file checker checks if a `backup.me` is created. |
| `ignore-disk-space` | Continue to do a server backup **even if the server disk is not having enough space.** |
## Name format tags
| Tag | Replacement |
| --- | ----------- |
| `{y}` | **Current year** represented by a two-digit number |
| `{m}` | **Current month** represented by a two-digit number |
| `{d}` | **Current date** represented by a two-digit number |
| `{h}` | **Current hour** represented by a two-digit number |
| `{i}` | **Current minute** represented by a two-digit number |
| `{s}` | **Current second** represented by a two-digit number |
| `{format}` | The chosen backup archiver format ***(`zip` / `tar`)*** |
| `{uuid}` | A **universal unique identifier** of the current backup archive task |
### Examples formats:
- `backup-{y}-{m}-{d} {h}-{i}-{s}.{format}`
- `backup_my_server_{uuid}.{format}`
- `backup {y}-{m}-{d} ({uuid}).{format}`
## Smart ignore conditions
| Target object | **Type** | Condition |
| ------------- | -------- | --------- |
| `/players/` | **Directory** | The save player data option is false in `pocketmine.yml` |
| `/whitelist.txt` | **File** | The whitelist option is false in `server.properties` |
| `/resource_packs/` | **Directory** | No valid resource packs is found in the resource stack |
## backupignore.gitignore
You can input certain rules to prevent the backup archiver from adding that file to the backup archive file.

**This file is using the [GitIgnore](https://git-scm.com/docs/gitignore) syntax**
***(I will replace this with another file pattern regex system since this thing requires an additional [GitIgnoreChecker](https://github.com/inmarelibero/gitignore-checker/) library and causes many issues when this plugin is running on other OS such as Windows)***
### Example rules *(For people who have never used the GitIgnore syntax before)*
| Rules | What this rule means |
| ----- | -------------------- |
| `/plugins/` | The `plugins` directory at the backup source directory |
| `plugins/` | All the `plugins` directory no matter where it is |
| `/plugin.phar` | The `plugin.phar` file at the backup source directory |
| `plugin.phar` | The `plugin.phar` file at anywhere |
| `plugin.*` | The `plugin` file but any file extension names will match this rule |
| `*.phar` | Any .phar files |
| `!worlds/*` | None of all in any of the `worlds` directory |

# Start backup
Create a file name `backup.me` at the server root directory or use the [`/backupme` command]() from console or in-game. When the file checker finds it, a server backup will be started.

**Please do not turn off the server during the backup**

If a message with the backup task used time, the total amount of added files and ignored files popup. It means that the backup task is **completed**, you can do whatever you want at that time. The backup archive file will be generated at the server root directory *(Where you create the `backup.me` file)*

# Permissions
| Permission | Command |
| ---------- | ------- |
| `backupme.cmd.backup` | `/backupme` |

# API
## Custom backup requests

### Event

**Other plugins can manually start a server backup by calling a `\Endermanbugzjfc\BackupMe\events\BackupRequestEvent` event**

### Customize

| Member function name | Action |
| --------------- | ------ |
| `setBackupIgnoreContent(string $content)` | Overwrite the original backup ignore file contents |
| `setName(string $name)` | Overwrite the original backup archive file name |
| `setFormat(int $format)` | Overwrite the original backup archiver format |

**Available archiver formats (All in `\Endermanbugzjfc\BackupMe\BackupRequestListener`):**
- `BackupRequestListener::ZIP`
- `BackupRequestListener::TARGZ`
- `BackupRequestListener::TARBZ2`

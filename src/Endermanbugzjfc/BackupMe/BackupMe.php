<?php

/*

     					_________	  ______________		
     				   /        /_____|_           /
					  /————/   /        |  _______/_____    
						  /   /_     ___| |_____       /
						 /   /__|    ||    ____/______/
						/   /    \   ||   |   |   
					   /__________\  | \   \  |
					       /        /   \   \ |
						  /________/     \___\|______
						                   |         \ 
							  PRODUCTION   \__________\	

							   翡翠出品 。 正宗廢品  
 
*/

declare(strict_types=1);
namespace Endermanbugzjfc\BackupMe;

use pocketmine\command\{Command, CommandSender};
use pocketmine\utils\{TextFormat as TF, Utils};

use function dirname;
use function file_put_contents;
use function file_exists;
use function substr;
use function strlen;
use function extension_loaded;

use const DIRECTORY_SEPARATOR;

final class BackupMe extends \pocketmine\plugin\PluginBase {

	public const PREFIX = TF::BLUE . '[' . TF::BOLD . TF::DARK_AQUA . 'BackupMe' . TF::RESET . TF::BLUE  .']';
	
	private static $instance = null;
	
	public function onEnable() : void {
		if (!$this->initConfig()) {
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		self::$instance = $this;
		$this->displayStartupLogs();
		events\BackupRequestByCommandEvent::setBackupMePluginVersion($this);
		$this->getServer()->getPluginManager()->registerEvents($listener = (new BackupRequestListener($this)), $this);
		$checker = (new BackupMeFileCheckTask($this, $this->getSafeServerDataPath()));
		$checker->setBackupMeFile($this->getConfig()->get('check-for-file', 'backup.me'));
		$listener->setChecker($checker);
		$listener->setSource((string)(/*$this->getConfig()->get('backup-inside', $this->getSafeServerDataPath())*/$this->getSafeServerDataPath()));
		$listener->setDest((string)(/*$this->getConfig()->get('backup-into', $this->getSafeServerDataPath())*/$this->getSafeServerDataPath()));
		$listener->setFormat((int)($this->getConfig()->get('archiver-format', BackupRequestListener::ARCHIVER_ZIP)));
		$listener->setName((string)($this->getConfig()->get('backup-name', 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}')));
		$listener->setIgnoreDiskSpace((bool)($this->getConfig()->get('ignore-disk-space', false)));
		$listener->setBackupIgnoreFilePath($this->getDataFolder() . 'backupignore.gitignore');
		$this->getScheduler()->scheduleRepeatingTask($checker, (int)$this->getConfig()->get('file-checker-interval', 3) * 20);
		return;
	}

	private function initConfig() : bool {

		$this->saveIgnoreFile();

		$this->saveDefaultConfig();
		$conf = $this->getConfig();

		$all = $conf->getAll();
		foreach ($all as $k => $v) $conf->remove($k);

		$conf->set('enable-plugin', (bool)($all['enable-plugin'] ?? ($all['true-this-or-dream-might-quit-youtube'] ?? true)));
		$conf->set('archiver-format', (int)($all['archiver-format'] ?? BackupRequestListener::ARCHIVER_ZIP));
		$conf->set('backup-name', (string)($all['backup-name'] ?? 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}'));
		$conf->set('file-checker-interval', (int)($all['file-checker-interval'] ?? 3));
		$conf->set('ignore-disk-space', (bool)($all['ignore-disk-space'] ?? false));
		$conf->set('check-for-file', (string)($all['check-for-file'] ?? 'backup.me'));
		// $conf->set('archive-empty-dir', (bool)($all['archive-empty-dir'] ?? false));
		$conf->set('operation-log', (bool)($all['operation-log'] ?? false));

		$conf->save();
		$conf->reload();
		if (($conf->get('archiver-format', BackupRequestListener::ARCHIVER_ZIP) === BackupRequestListener::ARCHIVER_TARBZ2) and !extension_loaded('bz2')) {
			$this->getLogger()->critical('The selected archiver format is unavailable because the extension "bz2" is not loaded!');
			$false = false;
		}
		return $false ?? $conf->get('enable-plugin', true);
	}

	private function displayStartupLogs() : void {
		$log = $this->getLogger();
		$log->info('======= B A C K U P . M E =======');
		$log->info('');
		$log->info('Backup server by creating a "backup.me" file');
		$log->info('Or use the "backupme" command');
		$log->info('');
		$log->debug('Plugin version: ' . $this->getDescription()->getVersion());
		$log->debug('Plugin PHAR file hash: ' . ($this->isPhar() ? md5_file($this->getPharPath()) : 'UNKNOWN'));
		$log->debug('');
		$log->info('=================================');
		return;
	}

	private function saveIgnoreFile() : void {
		if (Utils::getOS() === Utils::OS_LINUX) if (file_exists($this->getDataFolder() . 'backupignore.gitignore')) return;
		file_put_contents($this->getDataFolder() . 'backupignore.gitignore', join("\n", [
			'# This file is using the gitignore syntax, enjoy!',
			'# Specify filepatterns you want the backup file archiver to ignore.',
			'',
			'backup-*-*-* *-*-*.*',
			'PocketMine-MP.phar',
			'bin/',
			'*.lock',
			'backup.me'
		]));
		return;
	}

	private function getSafeServerDataPath() : string {
		return dirname($this->getDataFolder(), 2) . DIRECTORY_SEPARATOR;
	}

	public function onCommand(CommandSender $p, Command $cmd, string $alias, array $args) : bool {
		if (!$cmd->getName() === 'backupme') return true;
		if (!$p->hasPermission('backupme.cmd.backup')) $p->sendMessage(TF::BOLD . TF::RED . "You do not have the permission to use this command!");
		(new events\BackupRequestByCommandEvent($this, $p))->call();
		return true;
	}

	public function getPharPath() : string {
		return $this->isPluginCompiled() ? substr($this->getFile(), 6, strlen(substr($this->getFile(), 6)) - 1) : $this->getFile();
	}

	public function isPluginCompiled() : bool {
		return $this->isPhar();
	}
	
	public function allowOperationLog() : book {
		return (bool)$this->getConfig()->get('operation-log', false);
	}
	
	public static function getInstance() : ?self {
		return self::$instance;
	}
}

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

use function dirname;
use function file_put_contents;
use function file_exists;

use const DIRECTORY_SEPARATOR;

final class BackupMe extends \pocketmine\plugin\PluginBase {
	
	public function onEnable() : void {
		if (!$this->initConfig()) {
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getServer()->getPluginManager()->registerEvents($archiver = (new BackupArchiver($this)), $this);
		$checker = (new BackupMeFileCheckTask($this, $this->getSafeServerDataPath()));
		$archiver->setChecker($checker)
				 ->setSource((string)($this->getConfig()->get('backup-inside', $this->getSafeServerDataPath())))
				 ->setDest((string)($this->getConfig()->get('backup-into', $this->getSafeServerDataPath())))
				 ->setFormat((int)($this->getConfig()->get('archiver-format', BackupArchiver::ARCHIVER_ZIP)))
				 ->setName((string)($this->getConfig()->get('backup-name', 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}')))
				 ->setSmartIgnore((bool)($this->getConfig()->get('smart-backup-ignorer', false)))
				 ->setIgnoreDiskSpace((bool)($this->getConfig()->get('ignore-disk-space', false)));
		$this->getScheduler()->scheduleRepeatingTask($checker, (int)$this->getConfig()->get('file-checker-interval', 3) * 20);
		return;
	}

	private function initConfig() : bool {

		$this->saveIgnoreFile();

		$this->saveDefaultConfig();
		$conf = $this->getConfig();

		$all = $conf->getAll();
		foreach ($all as $k => $v) $conf->remove($k);

		$conf->set('true-this-or-dream-might-quit-youtube', (bool)($all['true-this-or-dream-might-quit-youtube'] ?? true));
		// $conf->set('allow-backup-cmd', (bool)($all['allow-backup-cmd'] ?? false));
		// $conf->set('archiver-format', (int)($all['archiver-format'] ?? BackupArchiver::ARCHIVER_ZIP));
		$conf->set('backup-inside', (string)($all['backup-inside'] ?? $this->getSafeServerDataPath()));
		$conf->set('backup-into', (string)($all['backup-into'] ?? $this->getSafeServerDataPath()));
		$conf->set('backup-name', (string)($all['backup-name'] ?? 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}'));
		$conf->set('smart-backup-ignorer', (bool)($all['smart-backup-ignorer'] ?? false));
		$conf->set('file-checker-interval', (int)($all['file-checker-interval'] ?? 3));
		$conf->set('ignore-disk-space', (bool)($all['ignore-disk-space'] ?? false));
		// $conf->set('archive-empty-dir', (bool)($all['archive-empty-dir'] ?? false));

		$conf->save();
		$conf->reload();
		return $conf->get('true-this-or-dream-might-quit-youtube', true);
	}

	private function saveIgnoreFile() : void {
		if (file_exists($this->getDataFolder() . 'backupignore.gitignore')) return;
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
}

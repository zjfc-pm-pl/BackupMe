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

use function disk_free_space;
use function disk_total_space;
use function file_get_contents;
use function file_exists;
use function unlink;
use function is_null;

class BackupArchiver implements \pocketmine\event\Listener {

	public const ARCHIVER_ZIP = 0;
	public const ARCHIVER_TARGZ = 1;
	public const ARCHIVER_TARBZ2 = 2;

	protected $main;
	protected $checker;
	protected $source;
	protected $desk;
	protected $name = 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}';
	protected $format = self::ARCHIVER_ZIP;
	protected $dynamicignore = false;
	protected $ignorediskspace = false;

	public function __construct(\pocketmine\plugin\Plugin $main) {
		$this->main = $main;
	}

	public function requestByPlugin(events\BackupRequestByPluginEvent $e) : void {
		if ($e->isCancelled()) return;
		$log = $e->getPlugin()->getLogger();
		$log->info('Server backup requested...');
		$log->info('Checking disk space...');
		if (($free = (int)disk_free_space($this->desk)) < ($takes = disk_total_space($this->source) - (int)disk_free_space($this->source))) {
			$log->emergency('Disk space is not enough for a backup (' . round($takes / 1024 / 1024 / 1024, 2) . ' GB' . ' out of ' . round($free / 1024 / 1024 / 1024, 2) . ' GB)');
			if (!$this->doIgnoreDiskSpace()) {
				$log->critical('Abort backup task due to the lacking of disk space');
				(new events\BackupAbortEvent($e, events\BackupAbortEvent::REASON_DISK_SPACE_LACK))->call();
				return;
			}
			$log->warning('>> !DISCLAIMER! << Not my fault if the backup file or the disk of your server is broken due to the lacking of disk space');
		}
		else $log->info('Disk space is enough for a backup (' . round($takes / 1024 / 1024 / 1024, 2) . ' GB' . ' out of ' . round($free / 1024 / 1024 / 1024, 2) . ' GB)');
		$log->notice('Backup start now!');
		$e->getPlugin()->getServer()->getAsyncPool()->submitTask(new BackupArchiveAsyncTask($e, $this->source, $this->desk, $this->name, $this->format, $this->dynamicignore, (file_exists($e->getPlugin()->getDataFolder() . 'backupignore.gitignore') ? $e->getPlugin()->getDataFolder() . 'backupignore.gitignore' : null)));
	}

	public function stop(events\BackupStopEvent $e) : void {
		if (!is_null($e->getRequest()->getBackupMeFilePath() ?? null)) @unlink($e->getRequest()->getBackupMeFilePath());
	}

	public function setChecker(BackupMeFileCheckTask $checker) : BackupArchiver {
		$this->checker = $checker;
		return $this;
	}

	public function setSource(string $path) : BackupArchiver {
		$this->source = $path;
		return $this;
	}

	public function setDesk(string $path) : BackupArchiver {
		$this->desk = $path;
		return $this;
	}

	public function setName(string $name) : BackupArchiver {
		$this->name = $name;
		return $this;
	}

	public function setFormat(int $format) : BackupArchiver {
		$this->format = $format;
		return $this;
	}

	public function setDynamicIgnore(bool $dynamicignore) : BackupArchiver {
		$this->dynamicignore = $dynamicignore;
		return $this;
	}

	public function setIgnoreDiskSpace(bool $ignorediskspace) : BackupArchiver {
		$this->ignorediskspace = $ignorediskspace;
		return $this;
	}

	public function getChecker() : ?BackupMeFileCheckTask {
		return $this->checker;
	}

	public function getSource() : ?string {
		return $this->source;
	}

	public function getDesk() : ?string {
		return $this->desk;
	}

	public function getName() : ?string {
		return $this->name;
	}

	public function getFormat() : ?int {
		return $this->format;
	}

	public function doDynamicIgnore() : ?bool {
		return $this->dynamicignore;
	}

	public function doIgnoreDiskSpace() : ?bool {
		return $this->ignorediskspace;
	}
}

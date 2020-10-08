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
use function round;
use function microtime;

class BackupArchiver implements \pocketmine\event\Listener {

	public const ARCHIVER_ZIP = 0;
	public const ARCHIVER_TARGZ = 1;
	public const ARCHIVER_TARBZ2 = 2;

	protected $main;
	protected $checker;
	protected $source;
	protected $dest;
	protected $name = 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}';
	protected $format = self::ARCHIVER_ZIP;
	protected $smartignorer = false;
	protected $ignorediskspace = false;

	public function __construct(\pocketmine\plugin\Plugin $main) {
		$this->main = $main;
	}

	public function requestByPlugin(events\BackupRequestByPluginEvent $e) : void {
		if ($e->isCancelled()) return;
		$this->pauseChecker();
		$this->main->getLogger()->debug('File checker pasued');
		$log = $e->getPlugin()->getLogger();
		$log->info('Server backup requested...');
		$log->info('Checking disk space...');
		if (($free = (int)disk_free_space($this->dest)) < ($takes = disk_total_space($this->source) - (int)disk_free_space($this->source))) {
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
		$e->getPlugin()->getServer()->getAsyncPool()->submitTask(new BackupArchiveAsyncTask($e, $this->getSource(), $this->getDest(), $this->getName(), $this->getFormat(), $this->doSmartIgnore(), (file_exists($e->getPlugin()->getDataFolder() . 'backupignore.gitignore') ? $e->getPlugin()->getDataFolder() . 'backupignore.gitignore' : null)));
	}

	public function stop(events\BackupStopEvent $e) : void {
		if ($e->isCancelled()) return;
		$log = $this->main->getLogger();
		if (!is_null($file = ($e->getRequest()->getBackupMeFilePath() ?? null))) {
			@unlink($file);
			$log->debug('Deleted file "' . $file . '"');
		}

		if ($e instanceof events\BackupAbortEvent) {
			var_dump(get_class($ero));
			switch ($e->getReason()) {
				case events\BackupAbortEvent::REASON_COMPRESS_FAILED:
					$log->critical('>> !BACKUP FAILURED! << Exception encounted when compressing the backup archive file');
					if (($ero = $e->getException()) instanceof \Throwable) $log->logException($ero);
					break;

				case events\BackupAbortEvent::REASON_CANNOT_CREATE_ACHIVE_FILE:
					$log->emergency('>> !BACKUP FAILURED! << Exception encounted when creating the backup archive file');
					if (($ero = $e->getException()) instanceof \Throwable) $log->logException($ero);
					break;
			}
			return;
		}

		$log->notice('Backup task completed in ' . round(microtime(true) - $e->getStartTime(), 3) . ' seconds (' . $e->getTotalFileAdded() . ' files added, ' . $e->getTotalFileIgnored() . ' files ignored)');
		$this->resumeChecker();
		$log->debug('File checker resumed');
	}

	protected function pauseChecker() : void {
		$this->checker->pause();
	}

	protected function resumeChecker() : void {
		$this->checker->resume();
	}

	public function setChecker(BackupMeFileCheckTask $checker) : BackupArchiver {
		$this->checker = $checker;
		return $this;
	}

	public function setSource(string $path) : BackupArchiver {
		$this->source = $path;
		return $this;
	}

	public function setDest(string $path) : BackupArchiver {
		$this->dest = $path;
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

	public function setSmartIgnore(bool $smartignorer) : BackupArchiver {
		$this->smartignorer = $smartignorer;
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

	public function getDest() : ?string {
		return $this->dest;
	}

	public function getName() : ?string {
		return $this->name;
	}

	public function getFormat() : ?int {
		return $this->format;
	}

	public function doSmartIgnore() : ?bool {
		return $this->smartignorer;
	}

	public function doIgnoreDiskSpace() : ?bool {
		return $this->ignorediskspace;
	}
}

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

class BackupRequestListener implements \pocketmine\event\Listener {

	public const ARCHIVER_ZIP = 0;
	public const ARCHIVER_TARGZ = 1;
	public const ARCHIVER_TARBZ2 = 2;

	protected $main;
	protected $checker;
	protected $source;
	protected $dest;
	protected $name = 'backup-{y}-{m}-{d} {h}-{i}-{s}.{format}';
	protected $format = self::ARCHIVER_ZIP;
	protected $ignorediskspace = false;
	protected $ignorefilepath;

	public function __construct(\pocketmine\plugin\Plugin $main) {
		$this->main = $main;
	}

	public function request(events\BackupRequest $e) : void {
		if ($e->isCancelled()) return;
		$this->pauseChecker();
		$log = $e;
		$log->debug('File checker pasued');
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
		if (is_null($e->getBackupIgnoreContent()) and file_exists($this->ignorefilepath)) $e->setBackupIgnoreContent(Utils::filterIgnoreFileComments(file_get_contents($this->ignorefilepath)));
		if (is_null($e->getFormat())) $e->setFormat($this->getFormat());
		if (is_null($e->getName())) $e->setName($this->getName());
		$this->main->getServer()->getAsyncPool()->submitTask(new BackupArchiveAsyncTask($e, $this->getSource(), $this->getDest()));
		return;
	}

	public function stop(events\BackupStopEvent $e) : void {
		if ($e->isCancelled()) return;
		$log = $e->getRequest();
		if (!is_null($file = ($e->getRequest()->getBackupMeFilePath() ?? null))) {
			@unlink($file);
			$log->debug('Deleted file "' . $file . '"');
		}

		if ($e instanceof events\BackupAbortEvent) {
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

	public function setChecker(BackupMeFileCheckTask $checker) {
		$this->checker = $checker;
		return $this;
	}

	public function setSource(string $path) {
		$this->source = $path;
		return $this;
	}

	public function setDest(string $path) {
		$this->dest = $path;
		return $this;
	}

	public function setName(string $name) {
		$this->name = $name;
		return $this;
	}

	public function setFormat(int $format) {
		$this->format = $format;
		return $this;
	}

	public function setIgnoreDiskSpace(bool $ignorediskspace) {
		$this->ignorediskspace = $ignorediskspace;
		return $this;
	}

	public function setBackupIgnoreFilePath(string $path) {
		$this->ignorefilepath = $path;
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

	public function doIgnoreDiskSpace() : bool {
		return $this->ignorediskspace;
	}

	public function getBackupIgnoreFilePath() : string {
		return $this->ignorefilepath;
	}
}

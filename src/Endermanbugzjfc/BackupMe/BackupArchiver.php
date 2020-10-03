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

class BackupArchiver implements \pocketmine\event\Listener {

	public const ARCHIVER_NONE = 0;
	public const ARCHIVER_ZIP = 1;
	public const ARCHIVER_TARGZ = 2;
	public const ARCHIVER_TARBZ2 = 3;

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
		$log->info('Checking disk storage...');
		if (($space = (int)disk_free_space($this->desk)) < ($total = (int)disk_total_space($this->source))) {
			$log->emegancy('Disk space is not enough for a backup (' . round($space / 1024 / 1024, 0) . ' MB' . ' out of ' . round($total / 1024 / 1024, 0) . ' MB)');
			if (!$this->doIgnoreDiskSpace()) {
				$log->critical('Abort backup task due to the lacking of disk space');
				(new BackupAbortEvent($e, BackupAbortEvent::REASON_DISK_SPACE_LACK))->call();
				return;
			}
			$log->warning('>> !DISCLAIMER! << Not my fault if the backup file or the disk of your server is broken due to the lacking of disk space');
		}
		else $log->info('Disk space is enough for a backup (' . round($space / 1024 / 1024, 0) . ' MB' . ' out of ' . round($total / 1024 / 1024, 0) . ' MB)');
		$log->notice('Backup start now!');
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

	public function setSource() : ?string {
		return $this->source;
	}

	public function setDesk() : ?string {
		return $this->desk;
	}

	public function setName() : ?string {
		return $this->name;
	}

	public function setFormat() : ?int {
		return $this->format;
	}

	public function doDynamicIgnore() : ?bool {
		return $this->dynamicignore;
	}

	public function doIgnoreDiskSpace() : ?bool {
		return $this->ignorediskspace;
	}
}

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

use pocketmine\{Server, plugin\Plugin};

use Inmarelibero\GitIgnoreChecker\Model\{RelativePath, Repository, GitIgnore\File};

use function date;
use function substr;
use function scandir;
use function basename;
use function dirname;
use function microtime;
use function serialize;
use function unserialize;
use function mkdir;
use function copy;
use function unlink;
use function rename;
use function isdir;

use const DIRECTORY_SEPERATOR;

class BackupArchiveAsyncTask extends \pocketmine\scheduler\AsyncTask {

	protected $source;
	protected $desk;
	protected $format;
	protected $dynamicignore;
	protected $backupignore;

	protected const PROGRESS_FILE_ADDED = 0;
	protected const PROGRESS_FILE_IGNORED = 1;
	protected const PROGRESS_FILE_AUTO_IGNORED = 2;
	protected const PROGRESS_ARCHIVE_FILE_CREATED = 3;
	protected const PROGRESS_EXCEPTION_ENCOUNTED_WHEN_ADDING_FILE = 4;
	protected const PROGRESS_COMPRESSING_ARCHIVE = 5;

	protected const RESULT_SUCCESSED = 0;
	protected const RESULT_EXCEPTION_ENCOUNTED_WHEN_CREATING_ARCHIVE_FILE = 1;
	protected const RESULT_EXCEPTION_ENCOUNTED_WHEN_COMPRESSING = 2;

	public function __construct(events\BackupRequest $request, string $source, string $desk, string $name, int $format, bool $dynamicignore, ?string $backupignore) {
		$this->desk = $desk . (!(($dirsep = substr($source, -1, 1)) === '/' or $dirsep === "\\") ? DIRECTORY_SEPERATOR : '') . self::replaceFileName($name, $format);
		$this->source = $source;
		$this->format = $format;
		if (!$dynamicignore) $dynamicignore = [];
		else $dynamicignore = [
			$request->getPlugin()->getServer()->shouldSavePlayerData(),
			$request->getPlugin()->getServer()->hasWhitelist(),
			!empty($request->getPlugin()->getServer()->getResourcePackManager()->getResourceStack())
		];
		$this->dynamicignore = serialize($dynamicignore);
		$this->uuid = UUID::fromRandom()->toString();
		$this->ignorefilepath = $ignorefilepath;
		$this->storeLocal($request);
		return;
	}

	public function onRun() : void {
		$time = microtime(true);
		switch ($this->format) {
			case BackupArchiver::ARCHIVER_ZIP:
				$arch = (new \ZipArchive());
				if ($arch->open($this->desk, \ZipArchive::CREATE) !==TRUE ) {
					$this->setResult(self::RESULT_EXCEPTION_ENCOUNTED_WHEN_CREATING_ARCHIVE_FILE, self::serializeException(new \InvalidArgumentException('Archiver cannot open file "' . $this->desk . '"')));
					return;
				}
				break;

			case BackupArchiver::ARCHIVER_TARGZ:
			case BackupArchiver::ARCHIVER_TARBZ2:
				try {
					$arch = (new \PharData($this->desk));
				} catch (\Exception $ero) {
					$this->setResult(self::RESULT_EXCEPTION_ENCOUNTED_WHEN_CREATING_ARCHIVE_FILE, self::serializeException($ero));
					return;
				}
				break;
			
			default:
				$this->setResult(self::RESULT_EXCEPTION_ENCOUNTED_WHEN_CREATING_ARCHIVE_FILE, self::serializeException(new \InvalidArgumentException('Unknown backup archiver format ID "' . $this->format . '"')));
				return;
				break;
		}
		$this->publishProgress([self::PROGRESS_ARCHIVE_FILE_CREATED, (string)$this->desk]);
		if (isset($this->ignorefilepath)) {
			if (file_exists($this->source . '.gitignore')) {
				if (file_exists($tmpdir = $this->source . 'backup-archive-task-tmp-' . $this->uuid . DIRECTORY_SEPARATOR)) $this->publicProgress([self::PROGRESS_FAILED_TO_CREATE_BACKUP_TASK_TMP_DIR, $tmpdir]);
				else 
					mkdir($tmpdir);
					copy($this->source . '.gitignore', $tmpdir . '.gitignore');
					$this->publishProgress([self::PROGRESS_COPIED_FILE_TO_BACKUP_TASK_TMP_DIR]);
					@unlink($this->source . '.gitignore');
				}
			}
			require 'libs/vendor/autoload.php';
			var_dump(copy($this->ignorefilepath, $this->source . 'backupignore.gitignore'));
			var_dump(rename($this->source . 'backupignore.gitignore', $this->source . '.gitignore'));
			$ignore = (new GitIgnoreChecker($this->source));
		// }
		$result = $this->scanIn($arch, $ignore ?? null, $this->source, 0, 0, $tmpdir ?? null);
		if (isset($tmpdir)) if (file_exists($tmpdir)) {
			foreach (array_filter(scandir($tmpdir), function(string $dir) : bool {return !($dir === '.' or $dir === '..');}) as $dir) {
				@unlink($this->source . basename($dir));
				@copy($dir, $this->source . basename($dir));
				$arch->addFile($this->source . basename($dir));
				$this->publishProgress([self::PROGRESS_FOLE_ADDED, $this->source . basename($dir)]);
			}
		}
		$this->publishProgress([self::PROGRESS_COMPRESSING_ARCHIVE]);
		try {
			switch ($this->format) {
				case BackupArchiver::ARCHIVER_ZIP:
					$arch->close();
					break;

				case BackupArchiver::ARCHIVER_TARGZ;
					$arch->compress(\Phar::GZ);
					break;

				case BackupArchiver::ARCHIVER_TARBZ2;
					$arch->compress(\Phar::BZ2);
					break;
			}
		} catch (\Exception $ero) {
			$this->setResult([self::RESULT_EXCEPTION_ENCOUNTED_WHEN_COMPRESSING, self::serializeException($ero), $time, $result[0], $result[1]]);
			return;
		}
		$this->setResult([self::RESULT_SUCCESSED, $time, $result[0], $result[1]]);
		return;
	}

	public function onCompletion(Server $server) : void {
		$result = $this->getResult();
		$e = $this->fetchLocal();
		$log = $e->getPlugin()->getLogger();
		switch ((int)$result[0]) {
			case self::RESULT_EXCEPTION_ENCOUNTED_WHEN_CREATING_ARCHIVE_FILE:
				$log->emergency('>> !BACKUP FAILURED! << Exception encounted when creating the backup archive file');
				self::displayException($log, $result[1]);
				(new events\BackupAbortEvent($e, events\BackupAbortEvent::REASON_EXECEPTION_ENCOUNTED))->call();
				break;

			case self::RESULT_EXCEPTION_ENCOUNTED_WHEN_COMPRESSING:
				$log->error('Failed to compress the backup archive file!');
				$log->warning('The backup archive file has a high chance to be corrupted!');
				self::displayException($log, $result[1]);
				$log->notice('Backup task completed, details below >>');
				$log->info('Time used: ' . round());
				$log->info('Total added files: ' . (int)$result);
				(new events\BackupStopEvent($e))->call();
				break;

			case self::RESULT_SUCCESSED:
				$log->info('done');
				(new events\BackupStopEvent($e))->call();
				break;
		}
		return;
	}

	protected function scanIn($arch, ?GitIgnoreChecker $ignore, string $dir, int $ttfiles = 0, int $ttignored = 0, ?string $tmpdir) : array {
		$dir = array_filter(scandir($dir), function(string $dir) : bool {return !($dir === '.' or $dir === '..');});
		foreach ($dir as $dirorfile) {
			try {
				switch (false) {
					case is_dir($dirorfile):
						if (($ignore instanceof GitIgnoreChecker) and ($ignore->isPathIgnored('/' . $dirorfile))) {
							$this->publishProgress([self::PROGRESS_FILE_IGNORED, $dirorfile]);
							continue 2;
						}
						if ($this->doDynamicIgnore()) {
							$envir = unserialize($this->dynamicignore);
							if (
								((basename(dirname($dirorfile)) === 'player') and (!$envir[0])) or
								((basename($dirorfile, '.txt') === 'whitelist') and (!$envir[1])) or
								((basename(dirname($dirorfile)) === 'resource_packs') and (!$envir[2]))
							) {
								$ttignored++;
								$this->publishProgress([self::PROGRESS_FILE_AUTO_IGNORED, (string)$dirorfile]);
								continue 2;
							}
						}
						if ($dirorfile === $this->source . '.gitignore') continue 2;
						$arch->addFile($dirorfile);
						break;
					
					case !is_dir($dirorfile):
						if ($dirorfile === $tmpdir) continue 2;
						$tmp = $this->scanIn($arch, $ignore, $dirorfile, $ttfiles, $ttignored, $tmpdir ?? null);
						$ttfiles = $tmp[0];
						$ttignored = $tmp[1];
						break;
				}
			} catch (\Exception $e) {
				$this->publishProgress([self::PROGRESS_EXCEPTION_ENCOUNTED_WHEN_ADDING_FILE, (string)$dirorfile]);
			}
		}
		return [$ttfiles, $ttignored];
	}

	protected function doDynamicIgnore() : bool {
		return !empty(unserialize($this->dynamicignore));
	}

	protected static function serializeException(\Exception $ero) : array {
		return [
			$ero->getMessage(),
			$ero->getFile(),
			$ero->getLine(),
			$ero->getTrace()
		];
	}

	public function onProgressUpdate(Server $server, $progress) : void {
		$log = $this->fetchLocal()->getPlugin()->getLogger();
		switch ((int)$progress[0]) {
			case self::PROGRESS_FILE_ADDED:
				$log->debug('Added file "' . (string)$progress[1] . '"');
				break;

			case self::PROGRESS_FILE_IGNORED:
				$log->debug('File "' . (string)$progress[1] . '" was matching one or more record inside the backup ignore file, skipped file');
				break;

			case self::PROGRESS_ARCHIVE_FILE_CREATED:
				$log->debug('Created backup archive file "' . (string)$progress[1] . '"');
				break;

			case self::PROGRESS_COMPRESSING_ARCHIVE:
				$log->info('Compressing backup archive file...');
				break;
		}
		return;
	}

	protected static function replaceFileName(string $name, int $format) : string {
		$name = str_replace('{y}', date('Y'), $name);
		$name = str_replace('{m}', date('m'), $name);
		$name = str_replace('{d}', date('d'), $name);
		$name = str_replace('{h}', date('H'), $name);
		$name = str_replace('{i}', date('i'), $name);
		$name = str_replace('{s}', date('s'), $name);
		switch ($format) {
			case BackupArchiver::ARCHIVER_ZIP:
				$format = 'zip';
				break;

			case BackupArchiver::ARCHIVER_TARGZ:
			case BackupArchiver::ARCHIVER_TARBZ2:
				$format = 'tar';
				break;
			
			default:
				throw new \InvalidArgumentException('Unknown backup archiver format ID "' . $format . '"');
				break;
		}
		$name = str_replace('{format}', $format, $name);
		return $name;
	}
}

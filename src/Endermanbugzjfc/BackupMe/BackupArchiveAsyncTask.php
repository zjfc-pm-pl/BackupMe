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
		$this->backupignore = $backupignore;
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
		if (isset($this->backupignore)) {
			require 'libs/vendor/autoload.php';
			$repo = (new Repository($this->source));
			$ignore = File::buildFromContent(new RelatedPath($repo, $repo->getPath()), $this->backupignore);
		}
		$result = $this->scanIn($arch, $repo, $ignore, $this->source);
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

	protected static function displayException(\Logger $log, array $ero) : void {
		$log->debug('Now logging error details >>');
		$log->debug('Error message: ' . $ero[0]);
		$log->debug('Error occurred in file: ' . $ero[1]);
		$log->debug('Error occurred at line: ' . $ero[2]);
		$log->debug('Stack trace below >>');
		foreach (\pocketmine\utils\Utils::printableTrace($ero[3]) as $trace) $log->debug($trace);
	}

	protected function scanIn($arch, ?Repository $repo, ?File $ignore, string $dir, int $ttfiles = 0, int $ttignored = 0) : array {
		$dir = scandir($dir);
		foreach ($dir as $dirorfile) {
			try {
				switch (false) {
					case isdir($dirorfile):
						if ((isset($backupignore)) and ($ignore->isPathIgnored(new RelatedPath($repo, $dirorfile)))) {
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
						$arch->addFile($dirorfile);
						break;
					
					case !isdir($dirorfile):
						if ((isset($backupignore)) and ($ignore->isPathIgnored(new RelatedPath($repo, $dirorfile)))) continue 2;
						$tmp = $this->scanIn($arch, $repo, $ignore, $dirorfile, $ttfiles, $ttignored);
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

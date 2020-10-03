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

use Inmarelibero\Model\{RelativePath, Repository, GitIgnore\File};

use function date;
use function substr;
use function scandir;
use function basename;
use function dirname;
use function microtime;
use function serialize;
use function unserialize;

use const DIRECTORY_SEPERATOR;

class BackupArchiveAsyncTask extends \pocketminescheduler\AsyncTask {

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
			case BackupArchiver::FORMAT_ZIP:
				$arch = (new \ZipArchive());
				if ($arch->open($this->desk, \ZipArchive::CREATE) !==TRUE ) {
					$this->setResult(self::RESULT_EXCEPTION_ENCOUNTED_WHEN_CREATING_ARCHIVE_FILE, self::serializeException(new \InvalidArgumentException('Archiver cannot open file "' . $this->desk . '"')));
					return;
				}
				break;

			case BackupArchiver::FORMAT_TARGZ:
			case BackupArchiver::FORMAT_TARBZ2:
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
		if (isset($backupignore)) {
			$repo = (new Repository($this->source));
			$ignore = File::buildFromContent(new RelatedPath($repo, $repo->getPath()), $this->backupignore);
		}
		$dir = scandir($this->source);
		$this->scanIn($arch, $repo, $ignore, $dir);
		return;
	}

	public function onCompletion(Server $server) : void {
		// TODO
	}

	protected function scanIn($arch, Repository $repo, File $ignore, string $dir, int $ttfiles = 0, int $ttignored = 0) : array {
		foreach ($dir as $dirorfile) {
			try {
				switch (false) {
					case isdir($dirorfile):
						if ((isset($backupignore)) and ($ignore->isPathIgnored(new RelatedPath($repo, $dirorfile)))) {
							$this->publishProgress([self::PROGRESS_FILE_IGNORED, $dirorfile]);
							continue;
						}
						if ($this->doDynamicIgnore()) {
							$envir = unserialize($this->dynamicignore);
							if (
								((basename(dirname($dirorfile)) === 'player') and (!$envir[0])) or
								((basename($dirorfile, '.txt') === 'whitelist') and (!$envir[1])) or
								((basename(dirname($dirorfile)) === 'resource_packs') and (!$envir[2]))
							) {
								$ttignored++;
								$this->publishProgress([self::PROGRESS_EXCEPTION_ENCOUNTED_WHEN_ADDING_FILE, (string)$dirorfile]);
								continue;
							}
						}
						$arch->addFile($dirorfile);
						break;
					
					case !isdir($dirorfile):
						if ((isset($backupignore)) and ($ignore->isPathIgnored(new RelatedPath($repo, $dirorfile)))) continue;
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
			case BackupArchiver::FORMAT_ZIP:
				$format = 'zip';
				break;

			case BackupArchiver::FORMAT_TARGZ:
			case BackupArchiver::FORMAT_TARBZ2:
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

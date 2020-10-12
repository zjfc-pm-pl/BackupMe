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

use pocketmine\{Server, plugin\Plugin, utils\UUID};

use Inmarelibero\GitIgnoreChecker\GitIgnoreChecker;

use function date;
use function substr;
use function scandir;
use function basename;
use function dirname;
use function microtime;
use function copy;
use function unlink;
use function is_dir;
use function file_get_contents;
use function file_put_contents;
use function explode;
use function implode;
use function strpos;
use function str_replace;
use function unserialize;
use function is_string;

use const DIRECTORY_SEPARATOR;

class BackupArchiveAsyncTask extends \pocketmine\scheduler\AsyncTask {

	protected $source;
	protected $dest;
	protected $format;
	protected $smartignore;
	protected $uuid;
	protected $ignorefilepath;

	protected const PROGRESS_FILE_ADDED = 0;
	protected const PROGRESS_FILE_IGNORED = 1;
	protected const PROGRESS_ARCHIVE_FILE_CREATED = 2;
	protected const PROGRESS_EXCEPTION_ENCOUNTED_WHEN_ADDING_FILE = 3;
	protected const PROGRESS_COMPRESSING_ARCHIVE = 4;

	protected const RESULT_STOPPED = 0;
	protected const RESULT_CANNOT_CREATE_ACHIVE_FILE = 1;

	public function __construct(events\BackupRequest $request, string $source, string $dest, string $name, int $format, bool $smartignore, ?string $ignorefilepath) {
		$this->dest = $dest . (!(($dirsep = substr($source, -1, 1)) === '/' or $dirsep === "\\") ? DIRECTORY_SEPARATOR : '') . self::replaceFileName($name, $format, $uuid = UUID::fromRandom());
		$this->source = $source;
		$this->format = $format;
		if (!$smartignore) $smartignore = [];
		else $smartignore = [
			$request->getPlugin()->getServer()->shouldSavePlayerData(),
			$request->getPlugin()->getServer()->hasWhitelist(),
			!empty($request->getPlugin()->getServer()->getResourcePackManager()->getResourceStack())
		];
		$this->smartignore = serialize($smartignore);
		$this->uuid = $uuid->toString();
		$this->ignorefilepath = $ignorefilepath;
		$this->storeLocal([$request, $this->uuid]);
		return;
	}

	public function onRun() : void {
		$time = microtime(true);
		switch ($this->format) {
			case BackupArchiver::ARCHIVER_ZIP:
				$arch = (new \ZipArchive());
				if ($arch->open($this->dest, \ZipArchive::CREATE) !== true ) {
					$this->setResult(self::RESULT_CANNOT_CREATE_ACHIVE_FILE, Utils::serializeException(new \InvalidArgumentException('Archiver cannot open file "' . $this->dest . '"')));
					return;
				}
				break;

			case BackupArchiver::ARCHIVER_TARGZ:
			case BackupArchiver::ARCHIVER_TARBZ2:
				try {
					$arch = (new \PharData($this->dest));
				} catch (\Throwable $ero) {
					$this->setResult(self::RESULT_CANNOT_CREATE_ACHIVE_FILE, Utils::serializeException($ero));
					return;
				}
				break;
			
			default:
				$this->setResult(self::RESULT_CANNOT_CREATE_ACHIVE_FILE, Utils::serializeException(new \InvalidArgumentException('Unknown backup archiver format ID "' . $this->format . '"')));
				return;
				break;
		}
		$this->publishProgress([self::PROGRESS_ARCHIVE_FILE_CREATED, $this->dest]);
		$savedIgnores = self::cleanGitignore($this->source);
		if (isset($this->ignorefilepath)) {
			require 'libs/vendor/autoload.php';
			@copy($this->ignorefilepath, $this->source . '.gitignore');
			@self::filterIgnoreFileComments($this->source . '.gitignore');
			$ignore = (new GitIgnoreChecker($this->source));
		}
		$ttfiles = 0;
		$ttignored = 0;
		$this->scanIn($arch, $ignore ?? null, $this->source, $ttfiles, $ttignored);
		@unlink($this->source . '.gitignore');
		foreach ($savedIgnores as $path => $content) file_put_contents($path, $content);
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
		} catch (\Throwable $ero) {}
		$this->setResult([self::RESULT_STOPPED, $time, $ttfiles, $ttignored, (isset($ero) ? Utils::serializeException($ero) : null)]);
		return;
	}

	protected static function cleanGitignore(string $path, array $saved = []) : array {
		$dir = array_filter(scandir($path), function(string $dirorfile) use ($path) : bool {
			return false;
			return (is_dir($path . $dirorfile) and $dirorfile !== '.' and $dirorfile !== '..') or (!is_dir($path) and $dirorfile === '.gitignore');
		});
		foreach ($dir as $dirorfile) switch (is_dir($path . $dirorfile)) {
			case false:
				$saved[$path . $dirorfile] = file_get_contents($path . $dirorfile);
				@unlink($path . $dirorfile);
				break;
			
			case true:
				$saved = self::cleanGitignore($path . $dirorfile . DIRECTORY_SEPARATOR, $saved);
				break;
		}
		return $saved;
	}

	public function onCompletion(Server $server) : void {
		$result = $this->getResult();
		$fridge = $this->fetchLocal(); // Don't judge name lol
		$e = $fridge[0];
		switch ((int)$result[0]) {
			case self::RESULT_CANNOT_CREATE_ACHIVE_FILE:
				$ero = @unserialize($result[1]);
				(new events\BackupAbortEvent($e, UUID::fromString($fridge[1]), events\BackupAbortEvent::RESULT_CANNOT_CREATE_ACHIVE_FILE, $ero ?? null))->call();
				break;

			case self::RESULT_STOPPED:
				if (!is_null($result[4])) (new events\BackupAbortEvent($e, UUID::fromString($fridge[1]), events\BackupAbortEvent::REASON_COMPRESS_FAILED, $ero))->call();
				else {
					$e->debug('Compress successed');
					(new events\BackupStopEvent($e, UUID::fromString($this->uuid), $result[1], $result[2], $result[3]))->call();
				}
				break;
		}
		return;
	}

	protected function scanIn($arch, ?GitIgnoreChecker $ignore, string $dir, int &$ttfiles, int &$ttignored) : void {
		$dirs = array_filter(scandir($dir), function(string $dir) : bool {return !($dir === '.' or $dir === '..');});
		foreach ($dirs as $dirorfile) try {
			switch (is_dir($dir . $dirorfile)) {
				case false:
					if ($this->doSmartIgnore()) {
						$envir = unserialize($this->smartignore);
						if (
							($dir . $dirorfile === $dir . 'whitelist.txt') and (!$envir[1])
						) continue 2;
					}
					if (($ignore instanceof GitIgnoreChecker) and ($ignore->isPathIgnored(substr($dir, strlen($ignore->getRepository()->getPath())) . $dirorfile))) {
						$ttignored++;
						$this->publishProgress([self::PROGRESS_FILE_IGNORED, $dir . $dirorfile]);
						continue 2;
					}
					if ($dir . $dirorfile === $this->source . '.gitignore') continue 2;
					$arch->addFile(substr($dir, strlen($this->source)) . $dirorfile);
					$ttfiles++;
					$this->publishProgress([self::PROGRESS_FILE_ADDED, $dir . $dirorfile]);
					break;
				
				case true:
					if ($this->doSmartIgnore()) {
						$envir = unserialize($this->smartignore);
						if (
							(($dir . $dirorfile === $dir . 'players') and (!$envir[0])) or
							(($dir . $dirorfile === $dir . 'resource_packs') and (!$envir[2]))
						) continue 2;
					}
					$this->scanIn($arch, $ignore, $dir . $dirorfile . DIRECTORY_SEPARATOR, $ttfiles, $ttignored);
					break;
			}
		} catch (\Throwable $ero) {
			$this->publishProgress([self::PROGRESS_EXCEPTION_ENCOUNTED_WHEN_ADDING_FILE, (string)$dirorfile]);
		}
		return;
	}

	protected static function filterIgnoreFileComments(string $ignorefilepath) : void {
		file_put_contents($ignorefilepath, implode("\n", array_filter(explode("\n", file_get_contents($ignorefilepath)), function(string $line) : bool {
			return strpos($line, '#') !== 0 and str_replace(' ', '', $line) !== '';
		})));
		return;
	}

	protected function doSmartIgnore() : bool {
		return !empty(unserialize($this->smartignore));
	}

	public function onProgressUpdate(Server $server, $progress) : void {
		$e = $this->fetchLocal()[0];
		switch ((int)$progress[0]) {
			case self::PROGRESS_FILE_ADDED:
				$e->debug('Added file "' . (string)$progress[1] . '"');
				break;

			case self::PROGRESS_FILE_IGNORED:
				$e->debug('File "' . (string)$progress[1] . '" was matching one or more rules inside the backup ignore file');
				break;

			case self::PROGRESS_ARCHIVE_FILE_CREATED:
				$e->debug('Created backup archive file "' . (string)$progress[1] . '"');
				break;

			case self::PROGRESS_COMPRESSING_ARCHIVE:
				$e->info('Compressing backup archive file...');
				$e->warning('This will take a while, do not shutdown the server!');
				break;
		}
		return;
	}

	protected static function replaceFileName(string $name, int $format, UUID $uuid) : string {
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

			/*case BackupArchiver::ARCHIVER_TARGZ:
			case BackupArchiver::ARCHIVER_TARBZ2:
				$format = 'tar';
				break;*/
			
			default:
				throw new \InvalidArgumentException('Unknown backup archiver format ID "' . $format . '"');
				break;
		}
		$name = str_replace('{format}', $format, $name);
		$name = str_replace('{uuid}', $uuid->toString(), $name);
		return $name;
	}
}

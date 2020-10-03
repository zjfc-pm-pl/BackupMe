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

use pocketmine\plugin\Plugin;

use function date;
use function substr;

use const DIRECTORY_SEPERATOR;

class BackupArchiveAsyncTask extends \pocketminescheduler\AsyncTask {

	protected $source;
	protected $desk;
	protected $format = self::ARCHIVER_ZIP;
	protected $dynamicignore = false;
	protected $backupignore;

	public function __construct(Plugin $main, events\BackupRequest $request, string $source, string $desk, string $name, int $format, bool $dynamicignore, ?string $backupignore) {
		$this->desk = $desk . (!(($dirsep = substr($source, -1, 1)) === '/' or $dirsep === "\\") ? DIRECTORY_SEPERATOR : '') . self::replaceFileName($name, $format);
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
				$format = 'tar.gz';
				break;

			case BackupArchiver::FORMAT_TARBZ2:
				$format = 'tar.bz2';
				break;
			
			default:
				throw new \InvalidArgumentException('Unknown backup archiver format ID "' . $format . '"');
				break;
		}
		$name = str_replace('{format}', $format, $name);
		return $name;
	}
}

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

use function implode;
use function array_filter;
use function explode;
use function strpos;
use function str_replace;
use function date;

abstract class Utils {

    public static function filterIgnoreFileComments(string $content) : string {
        return implode("\n", array_filter(explode("\n", $content), function(string $line) : bool {
            return strpos($line, '#') !== 0 and str_replace(' ', '', $line) !== '';
        }));
    }

    public static function replaceFileName(string $name, int $format, \pocketmine\utils\UUID $uuid) {
        $name = str_replace('{y}', date('Y'), $name);
        $name = str_replace('{m}', date('m'), $name);
        $name = str_replace('{d}', date('d'), $name);
        $name = str_replace('{h}', date('H'), $name);
        $name = str_replace('{i}', date('i'), $name);
        $name = str_replace('{s}', date('s'), $name);
        switch ($format) {
            case BackupRequestListener::ARCHIVER_ZIP:
                $format = 'zip';
                break;

            case BackupRequestListener::ARCHIVER_TARGZ:
            case BackupRequestListener::ARCHIVER_TARBZ2:
                $format = 'tar';
                break;
            
            default:
                throw new \InvalidArgumentException('Unknown backup archiver format ID "' . $format . '"');
                break;
        }
        $name = str_replace('{format}', $format, $name);
        $name = str_replace('{uuid}', $uuid->toString(), $name);
        return $name;
    }
}

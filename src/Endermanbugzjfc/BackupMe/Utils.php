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

use function get_class;
use function preg_replace;
use function implode;
use function array_filter;
use function explode;
use function strpos;
use function str_replace;
use function date;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use const PHP_EOL;
use const PTHREADS_INHERIT_NONE;

abstract class Utils {

	/*public static function serializeException(\Throwable $ero) : string {
        return serialize([get_class($ero), $ero->getCode(), $errstr = preg_replace('/\s+/', ' ', trim($ero->getMessage())), $ero->getFile(), $ero->getLine(), \pocketmine\utils\Utils::printableTrace($ero->getTrace())]);
	}
    public static function getErrorCodeConversion(int $code) : string {
        static $ec = [
            0 => "EXCEPTION",
            E_ERROR => "E_ERROR",
            E_WARNING => "E_WARNING",
            E_PARSE => "E_PARSE",
            E_NOTICE => "E_NOTICE",
            E_CORE_ERROR => "E_CORE_ERROR",
            E_CORE_WARNING => "E_CORE_WARNING",
            E_COMPILE_ERROR => "E_COMPILE_ERROR",
            E_COMPILE_WARNING => "E_COMPILE_WARNING",
            E_USER_ERROR => "E_USER_ERROR",
            E_USER_WARNING => "E_USER_WARNING",
            E_USER_NOTICE => "E_USER_NOTICE",
            E_STRICT => "E_STRICT",
            E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
            E_DEPRECATED => "E_DEPRECATED",
            E_USER_DEPRECATED => "E_USER_DEPRECATED"
        ];
        return $ec[$code];
    }*/

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

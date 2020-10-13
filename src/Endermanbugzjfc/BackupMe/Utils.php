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

use function is_object;
use function get_class;
use function is_resource;
use function sprintf;
use function get_resource_type;
use function array_walk_recursive;
use function serialize;
use function unserialize;
use function implode;
use function array_filter;
use function explode;
use function strpos;
use function str_replace;

class Utils {

	private function __construct() {}

	public static function serializeException(\Throwable $ero) : string {
		$traceProperty = (new \ReflectionClass('Exception'))->getProperty('trace');
        $traceProperty->setAccessible(true);

        $flatten = function(&$value, $key) {
            if ($value instanceof \Closure) {
                $closureReflection = new \ReflectionFunction($value);
                $value = sprintf(
                    '(Closure at %s:%s)',
                    $closureReflection->getFileName(),
                    $closureReflection->getStartLine()
                );
            } 
            elseif (is_object($value)) $value = sprintf('object(%s)', get_class($value));
            elseif (is_resource($value)) $value = sprintf('resource(%s)', get_resource_type($value));
        };

        do {
            $trace = $traceProperty->getValue($ero);
            foreach($trace as &$call) array_walk_recursive($call['args'], $flatten);
            $traceProperty->setValue($ero, $trace);
        } while($ero = $ero->getPrevious());

        $traceProperty->setAccessible(false);
        return serialize($ero);
	}

    public static function filterIgnoreFileComments(string $content) : string {
        return implode("\n", array_filter(explode("\n", $content), function(string $line) : bool {
            return strpos($line, '#') !== 0 and str_replace(' ', '', $line) !== '';
        }));
    }
}

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

class Utils {

	private function __construct() {}

	// https://gist.github.com/Thinkscape/805ba8b91cdce6bcaf7c
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
}

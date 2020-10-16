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
namespace Endermanbugzjfc\BackupMe\events;

use pocketmine\{plugin\Plugin, utils\UUID};

/**
 * @allowHandle
 */

abstract class BackupRequest extends \pocketmine\event\plugin\PluginEvent implements \pocketmine\event\Cancellable, \Logger {	

	protected $uuid;
	protected $main;
	protected $ignore = null;
	protected $name = null;
	protected $format = null;

	public function __construct(Plugin $main) {
		$this->main = $main;
		$this->uuid = UUID::fromRandom();
	}

	public function getBackupTaskUUID() : UUID {
		return $this->uuid;
	}

	public function getPlugin() : Plugin {
		return $this->main;
	}

	public function getBackupIgnoreContent() : ?string {
		return $this->ignore;
	}

	public function getName() : ?string {
		return $this->name;
	}

	public function getFormat() : ?int {
		return $this->format;
	}

	public function setBackupIgnoreContent(string $ignore) {
		$this->ignore = $ignore;
		return;
	}
	
	public function setName(string $name) {
		$this->name = \Endermanbugzjfc\BackupMe\Utils::replaceFileName($name, $this->getFormat(), $this->getBackupTaskUUID());
		return;
	}
	
	public function setFormat(int $format) {
		$this->format = $format;
		return;
	}
	abstract public function getBackupMeFilePath() : ?string;
	abstract public function emergency($message);
	abstract public function alert($message);
	abstract public function critical($message);
	abstract public function error($message);
	abstract public function warning($message);
	abstract public function notice($message);
	abstract public function info($message);
	abstract public function debug($message);
	abstract public function log($level, $message);
	abstract public function logException(\Throwable $e, $trace = null);
}

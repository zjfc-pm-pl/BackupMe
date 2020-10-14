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

abstract class BackupRequestByBackupMeEvent extends \pocketmine\event\plugin\PluginEvent implements \pocketmine\event\Cancellable, BackupRequest {

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

	public function getBackupArchiveFileName() : ?string {
		return $this->name;
	}

	public function getBackupArchiverFormat() : ?int {
		return $this->format;
	}

	public function setBackupIgnoreContent(string $ignore) : void {
		$this->ignore = $ignore;
		return;
	}
	
	public function setBackupArchiveFileName(string $name) : void {
		$this->name = Utils::replaceFileName($name, $this->getBackupArchiverFormat(), $this->getBackupTaskUUID());
		return;
	}
	
	public function setBackupArchiverFormat(int $format) : void {
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

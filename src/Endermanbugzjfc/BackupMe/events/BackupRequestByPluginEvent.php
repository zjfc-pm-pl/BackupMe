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

class BackupRequestByPluginEvent extends \pocketmine\event\plugin\PluginEvent implements \pocketmine\event\Cancellable, BackupRequest {

	protected $main;
	protected $backupme;
	protected $uuid;

	public function __construct(Plugin $main, ?string $backupme) {
		$this->main = $main;
		$this->backupme = $backupme;
		$this->uuid = UUID::fromRandom();
	}

	public function getBackupTaskUUID() : UUID {
		return $this->uuid;
	}

	public function getPlugin() : Plugin {
		return $this->main;
	}

	public function getBackupMeFilePath() : ?string {
		return $this->backupme;
	}

	public function emergency($message) {
		$this->getPlugin()->getLogger()->emergency($message);
	}
	public function alert($message) {
		$this->getPlugin()->getLogger()->alert($message);
	}
	public function critical($message) {
		$this->getPlugin()->getLogger()->critical($message);
	}
	public function error($message) {
		$this->getPlugin()->getLogger()->error($message);
	}
	public function warning($message) {
		$this->getPlugin()->getLogger()->warning($message);
	}
	public function notice($message) {
		$this->getPlugin()->getLogger()->notice($message);
	}
	public function info($message) {
		$this->getPlugin()->getLogger()->info($message);
	}
	public function debug($message) {
		$this->getPlugin()->getLogger()->debug($message);
	}
	public function log($level, $message) {
		$this->getPlugin()->getLogger()->log($level, $message);
	}
	public function logException(\Throwable $e, $trace = null) {
		$this->getPlugin()->getLogger()->logException($e, $trace);
	}

}

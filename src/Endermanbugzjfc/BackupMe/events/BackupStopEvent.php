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

class BackupStopEvent extends \pocketmine\event\plugin\PluginEvent {

	protected $request;
	protected $start;
	protected $files;
	protected $ignored;
	protected $uuid;

	public function __construct(BackupRequest $e) {
		$this->request = $e;
	}

	public function getRequest() : BackupRequest {
		return $this->request;
	}

	public function setStartTime(float $time) : BackupStopEvent {
		$this->start = $time;
		return $this;
	}

	public function getStartTime() : ?float {
		return $this->start;
	}

	public function setTotalFileAdded(int $amount) : BackupStopEvent {
		$this->files = $amount;
		return $this;
	}

	public function getTotalFileAdded() : ?int {
		return $this->files;
	}

	public function setTotalFileIgnored(int $amount) : BackupStopEvent {
		$this->ignored = $amount;
		return $this;
	}

	public function getTotalFileIgnored() : ?int {
		return $this->ignored;
	}

	public function getBackupTaskUUID() : \pocketmine\utils\UUID {
		return $this->uuid;
	}
}

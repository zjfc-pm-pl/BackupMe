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

class BackupStopEvent extends \pocketmine\event\plugin\PluginEvent implements \pocketmine\event\Cancellable {

	protected $request;
	protected $start;
	protected $files;
	protected $ignored;
	protected $time;

	public function __construct(BackupRequest $e, float $time = null, int $files = null, int $ignored = null) {
		$this->request = $e;
		$this->start = $time;
		$this->files = $files;
		$this->ignored = $ignored;
	}

	public function getRequest() : BackupRequest {
		return $this->request;
	}

	public function getStartTime() : ?float {
		return $this->start;
	}

	public function getTotalFileAdded() : ?int {
		return $this->files;
	}

	public function getTotalFileIgnored() : ?int {
		return $this->ignored;
	}
}

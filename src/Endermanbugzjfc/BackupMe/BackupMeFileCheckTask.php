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

use pocketmine\scheduler\Task;

use function file_exists;

class BackupMeFileCheckTask {

	private $file;
	private $paused = false;

	public function __construct(string $file) {
		$this->file = $file;
	}

	final public function onRun(int $ct) : void {
		if ($this->isPaused()) return;
		if (!file_exists($file) return;
		(new events\BackupRequestByPluginEvent(BackupMe::getInstance(), $file))->call();
		return;
	}

	public function isPaused() : bool {
		return $this->paused;
	}

	public function pause() {
		$this->paused = true;
		return $this;
	}

	public function resume() {
		$this->paused = false;
		return $this;
	}

	public function getBackupMeFile() : string {
		return $this->getFileToCheck();
	}

	public function getFileToCheck() : string {
		return $this->file;
	}
}

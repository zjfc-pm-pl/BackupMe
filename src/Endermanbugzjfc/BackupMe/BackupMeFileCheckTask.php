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

use pocketmine\plugin\Plugin;

use function file_exists;

class BackupMeFileCheckTask extends \pocketmine\scheduler\Task {

	protected $main;
	protected $path;
	protected $paused = false;

	public function __construct(Plugin $main, string $path) {
		$this->main = $main;
		$this->path = $path;
	}

	final public function onRun(int $ct) : void {
		if ($this->isPaused()) return;
		if (!file_exists($this->getPath() . 'backup.me')) return;
		(new events\BackupRequestByPluginEvent($this->getPlugin(), $this->getPath() . 'backup.me'))->call();
		return;
	}

	public function getPath() : string {
		return $this->path;
	}

	public function getPlugin() : Plugin {
		return $this->main;
	}

	public function isPaused() : bool {
		return $this->paused;
	}

	public function pause() : BackupMeFileCheckTask {
		$this->paused = true;
		return $this;
	}

	public function resume() : BackupMeFileCheckTask {
		$this->paused = false;
		return $this;
	}
}

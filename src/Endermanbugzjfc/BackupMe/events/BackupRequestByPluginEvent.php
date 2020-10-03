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

use pocketmine\plugin\Plugin;

class BackupRequestByPluginEvent extends \pocketmine\event\plugin\PluginEvent implements \pocketmine\event\Cancellable, BackupRequest {

	protected $main;
	protected $backupme;

	public function __construct(Plugin $main, ?string $backupme) {
		$this->main = $main;
		$this->backupme = $backupme;
	}

	public function getPlugin() : Plugin {
		return $this->main;
	}

	public function getBackupMeFilePath() : ?string {
		return $this->backupme;
	}
}

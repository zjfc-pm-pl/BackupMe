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

interface BackupRequest extends \Logger {

	public function getPlugin() : \pocketmine\plugin\Plugin;
	public function getBackupTaskUUID() : \pocketmine\utils\UUID;
	public function getBackupMeFilePath() : ?string;
	public function getBackupIgnoreContent() : ?string;
	public function getName() : ?string;
	public function getFormat() : ?int;

	public function setBackupIgnoreContent(string $ignore);
	public function setName(string $name);
	public function setFormat(int $format);
}

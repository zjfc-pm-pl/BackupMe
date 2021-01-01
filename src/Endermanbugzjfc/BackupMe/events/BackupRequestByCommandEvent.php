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

use pocketmine\{
	plugin\Plugin, 
	command\CommandSender, 
	utils\TextFormat as TF, 
	utils\Utils,
	Player,
};

use Endermanbugzjfc\BackupMe\BackupMe;

use function phpversion;

class BackupRequestByCommandEvent extends BackupRequest {

	private const PREFIX = BackupMe::PREFIX;
	private static $version = 'UNKNOWN';
	private static $hash = 'UNKNOWN';

	protected $sender;

	public function __construct(Plugin $main, CommandSender $p) {
		parent::__construct($main);
		$this->sender = $p;
	}

	public function getRequestCommandSender() : CommandSender {
		return $this->sender;
	}

	final public function getSender() : CommandSender {
		return $this->getRequestCommandSender();
	}

	public function emergency($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->emergency($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::BOLD . TF::RED . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function alert($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->alert($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::YELLOW . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function critical($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->critical($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::RED . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function error($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->error($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::BOLD . TF::DARK_RED . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function warning($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->warning($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::BOLD . TF::YELLOW . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function notice($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->notice($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::BOLD . TF::GOLD . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function info($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->info($message);
		else {
			$message = self::PREFIX . TF::RESET . ' ' . TF::AQUA . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}

	public function debug($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->debug($message);
		elseif ((int)$this->getPlugin()->getServer()->getProperty('debug.level', 1) > 1) {
			$message = self::PREFIX . TF::RESET . ' ' . TF::BOLD . TF::GRAY . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function log($level, $message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->log($level, $message);
		else $this->info($message);
	}
	
	public function logException(\Throwable $e, $trace = null) {
	}

	public function getBackupMeFilePath() : ?string {
		return null;
	}
}

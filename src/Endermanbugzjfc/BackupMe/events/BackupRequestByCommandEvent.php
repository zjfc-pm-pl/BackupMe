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
	utils\UUID, 
	utils\Utils,
	Player,
	item\Item
};

use Endermanbugzjfc\BackupMe\BackupMe;

use function time;
use function implode;
use function get_class;
use function md5_file;

class BackupRequestByCommandEvent extends \pocketmine\event\plugin\PluginEvent implements \pocketmine\event\Cancellable, BackupRequest {

	private const PREFIX = BackupMe::PREFIX;
	private static $version = 'UNKNOWN';
	private static $hash = 'UNKNOWN';

	protected $main;
	protected $sender;
	protected $uuid;

	public function __construct(Plugin $main, CommandSender $p) {
		$this->main = $main;
		$this->sender = $p;
		$this->uuid = UUID::fromRandom();
	}

	public function getBackupTaskUUID() : UUID {
		return $this->uuid;
	}

	public function getPlugin() : Plugin {
		return $this->main;
	}

	public function getRequestCommandSender() : CommandSender {
		return $this->sender;
	}

	public function getSender() : CommandSender {
		return $this->getRequestCommandSender();
	}

	public function emergency($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->emergency($message);
		else {
			$message = TF::BOLD . TF::RED . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function alert($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->alert($message);
		else {
			$message = TF::YELLOW . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function critical($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->critical($message);
		else {
			$message = TF::RED . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function error($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->error($message);
		else {
			$message = TF::BOLD . TF::DARK_RED . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function warning($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->warning($message);
		else {
			$message = TF::BOLD . TF::YELLOW . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function notice($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->notice($message);
		else {
			$message = TF::BOLD . TF::GOLD . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function info($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->info($message);
		else {
			$message = TF::AQUA . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function debug($message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->debug($message);
		else {
			$message = TF::BOLD . TF::GRAY . $message . TF::RESET;
			$this->getSender()->sendMessage($message);
		}
	}
	public function log($level, $message) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->log($level, $message);
		else $this->info($message);
	}
	public function logException(\Throwable $e, $trace = null) {
		if (!$this->getSender() instanceof Player) $this->getPlugin()->getLogger()->logException($e, $trace);
		else {
			$i = Item::get(Item::WRITABLE_BOOK);
			$i->setNamedTagEntry(new \pocketmine\nbt\tag\ListTag('ench', []));
			$i->setCustomName(self::PREFIX . TF::RESET . TF::BOLD . TF::RED . "\nERROR LOG\n" . TF::ITALIC . TF::DARK_RED . "Backup task - " . $this->getBackupTaskUUID());
			foreach (self::getErrorLogPages($e, $trace) as $page => $content) $i->setPageText($page, $content);
			$this->getSender()->getInventory()->addItem($i);
			$this->getSender()->sendMessage(TF::YELLOW . "An " . TF::BOLD . TF::RED . "error has occurred " . TF::RESET . TF::YELLOW . "during the backup task, an " . TF::BOLD . TF::GOLD . "error log writable book item " . TF::RESET . TF::YELLOW . "has been added to your inventory!");
		}
	}

	public static function setBackupMePluginVersion(BackupMe $main) : void {
		self::$version = $main->getDescription()->getVersion();
		self::$hash = $main->isPhar() ? md5_file($main->getPharPath()) : 'UNKNOWN';
	}

	public static function getErrorLogPages(\Throwable $e, $trace = null) : array {
		$pages = [];
		$pages[] = implode(TF::RESET . "\n", [
			'Error occurred timestamp: ' . (string)time(),
			'BackupMe plugin version: ' . (string)self::$version,
			'Plugin PHAR file hash: ' . (string)self::$hash,
			'',
			'Error message: ' . $e->getMessage(),
			'Error type: ' . get_class($e),
			'Encounted file: ' . Utils::cleanPath($e->getFile()),
			'Encounted line: ' . $e->getLine()
		]);
		$pages[] = 'Stack trace >>';
		foreach (Utils::printableTrace($trace ?? $e->getTrace()) as $line) $pages[] = $line;
		return $pages;
	}
}

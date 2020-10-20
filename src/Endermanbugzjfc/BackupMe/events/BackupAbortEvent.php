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

class BackupAbortEvent extends BackupStopEvent {

	public const REASON_UNKNOWN = 0;
	public const REASON_DISK_SPACE_LACK = 1;
	public const REASON_COMPRESS_FAILED = 2;
	public const REASON_CANNOT_CREATE_ACHIVE_FILE = 3;

	protected $reason = 0;
	protected $exception;

	public function __construct(BackupRequest $e, int $reason, ?array $ero = null) {
		parent::__construct($e, $reason, $ero ?? null);
		$this->reason = $reason;
		$this->exception = $ero;
	}

	public function getReason() : int {
		return $this->reason;
	}

	public function getException() : ?array {
		return $this->exception;
	}

}

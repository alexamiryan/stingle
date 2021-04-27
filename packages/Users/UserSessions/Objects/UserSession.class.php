<?php
class UserSession{
	
	public int $id;
	public int $userId;
	public string $token;
	public string $creationDate;
	public string $lastUpdateDate;
	
	public ?User $user = null;
}

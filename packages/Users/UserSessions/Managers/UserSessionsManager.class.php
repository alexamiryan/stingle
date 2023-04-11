<?php

class UserSessionsManager extends DbAccessor {

	const TBL_USER_SESSIONS	= "user_sessions";
	
	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_USER = 1;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 1;
	
	protected Config $config;
    
    /**
     * UserSessionsManager constructor.
     * @param Config $config
     * @param ?string $instanceName
     */
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		$this->config = $config;
	}
    
    /**
     * @param UserSessionFilter|null $filter
     * @param MysqlPager|null $pager
     * @param int $initObjects
     * @param int $cacheMinutes
     * @return array[UserSession]
     * @throws MySqlException
     */
	public function getSessions(UserSessionFilter $filter = null, MysqlPager $pager = null, $initObjects = self::INIT_ALL, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF) : array{
		if($filter == null){
			$filter = new UserSessionFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$sessions = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				$sessions[] = $this->getUserSessionObjectFromData($row, $initObjects);
			}
		}
		return $sessions;
	}
	
	public function getSession(UserSessionFilter $filter, $initObjects = self::INIT_ALL) : ?UserSession {
		$sessions = $this->getSessions($filter, null, $initObjects);
		if(count($sessions) !== 1){
			throw new RuntimeException("There is no such user session or it is not unique.");
		}
		return $sessions[0];
	}
	
	
	public function getSessionByToken($token) : ?UserSession {
		$filter = new UserSessionFilter();
		$filter->setToken($token);
		
		$session = null;
		
		try{
			$session = $this->getSession($filter);
		}
		catch (RuntimeException $e){}
		
		return $session;
	}
	
	public function getUserFromSession($token) : ?User{
		$session = $this->getSessionByToken($token);
		if($session != null){
			return $session->user;
		}
		return null;
	}
	
	public function addSession($userId) : string {

		$token = generateRandomString(64, [RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS, RANDOM_STRING_SYMBOLS]);
		
        $qb = new QueryBuilder();
		$insertArr = array(
			'user_id' => $userId,
			'token' => $token,
            'last_update_date' => new Func('NOW')
		);
		
        $qb->insert(Tbl::get("TBL_USER_SESSIONS"))
            ->values($insertArr);

		$this->query->exec($qb->getSQL());
		
		return $token;
	}
	
	public function setToNowLastUpdateDate(string $token) : ?int {
        $qb = new QueryBuilder();
        
        $qb->update(Tbl::get('TBL_USER_SESSIONS'))
            ->set(new Field('last_update_date'), new Func('NOW'))
            ->where($qb->expr()->equal(new Field('token'), $token));
        
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    public function revokeAllSessions($userId) : ?int {
        $qb = new QueryBuilder();
        
        $qb->delete(Tbl::get('TBL_USER_SESSIONS'))
            ->where($qb->expr()->equal(new Field('user_id'), $userId));
        
        return $this->query->exec($qb->getSQL())->affected();
    }
	
	public function revokeToken($token) : ?int {
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_USER_SESSIONS'))
			->where($qb->expr()->equal(new Field('token'), $token));
		
		return $this->query->exec($qb->getSQL())->affected();
	}

	protected function getUserSessionObjectFromData($data, $initObjects = self::INIT_ALL) : UserSession{
		$sess = new UserSession();
		$sess->id 				= $data['id'];
		$sess->userId 			= $data['user_id'];
		$sess->token 			= $data['token'];
		$sess->creationDate		= $data['creation_date'];
		
		if (($initObjects & self::INIT_USER) != 0) {
			try{
				$sess->user = Reg::get('userMgr')->getUserById($data['user_id']);
			}
			catch(UserNotFoundException $e){ }
		}
		
		return $sess;
	}
	
	public static function registerUserByToken($tokenPlace){
	    $config = ConfigManager::getConfig("Users", "UserSessions");
	    $tokenName = $config->AuxConfig->tokenName;
        $token = null;
        switch($tokenPlace){
            case 'cookie':
                if(isset($_COOKIE[$tokenName])){
                    $token = $_COOKIE[$tokenName];
                }
                break;
            case 'post':
                if(isset($_POST[$tokenName])){
                    $token = $_POST[$tokenName];
                }
                break;
            case 'get':
                
                if(isset($_GET[$tokenName])){
                    $token = $_GET[$tokenName];
                }
                break;
        }
        
        if(!empty($token) && is_string($token)){
            $user = Reg::get($config->Objects->UserSessions)->getUserFromSession($token);
            if(is_a($user, "User")){
                Reg::register(ConfigManager::getConfig('Users', 'Users')->ObjectsIgnored->User, $user);
                if($config->AuxConfig->autoUpdateLastUpdateDate) {
                    Reg::get($config->Objects->UserSessions)->setToNowLastUpdateDate($token);
                }
            }
        }
    }
}

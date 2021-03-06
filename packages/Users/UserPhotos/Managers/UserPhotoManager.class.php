<?php
class UserPhotoManager extends DbAccessor
{
	const TBL_USERS_PHOTOS = 'users_photos';
	
	const MEMCACHE_PHOTO_TAG = 'UserPhoto:';
	
	const MODERATION_STATUS_APPROVED	= 'approved';
	const MODERATION_STATUS_NEW 		= 'new';
	const MODERATION_STATUS_DECLINED   = 'declined';
	
	const STATUS_DEFAULT_YES	= 1;
	const STATUS_DEFAULT_NO 	= 0;
	
	const EXCEPTION_MAX_COUNT_REACHED = 1;
	const EXCEPTION_UNAPROVED_TO_DEFAULT = 2;
	const EXCEPTION_DECLINED_TO_DEFAULT = 3;
	
	const IMAGE_TYPE_NAME = 'users';
	
	private $config;
	
	public function __construct($config, $instanceName = null){
		parent::__construct($instanceName);
		$this->config = $config;
	}
	
	public function uploadPhoto($file, $userId, $uploaderModelName = null){
		if(empty($userId)){
			throw new InvalidArgumentException("\$userId is empty!");
		}
		
		if(isset($this->config->maxPhotosCount) and $this->config->maxPhotosCount > 0){
			$filter = new UserPhotosFilter();
			$filter->setUserId($userId);
			$filter->setStatusNotEqual(UserPhotoManager::MODERATION_STATUS_DECLINED);
				
			$userPhotos = $this->getPhotos($filter);
			if(count($userPhotos) >= $this->config->maxPhotosCount){
				throw new UserPhotosException("Maximum photos volume reached for this user.", static::EXCEPTION_MAX_COUNT_REACHED);
			}
		}
		
		if($uploaderModelName === null){
			$uploaderModelName = self::IMAGE_TYPE_NAME;
		}
		
		$fileName = ImageManager::upload($file, $uploaderModelName);
		
		$photo = new UserPhoto();
		$photo->fileName = $fileName;
		$photo->userId = $userId;
		$photo->status = self::MODERATION_STATUS_NEW;
		
		return $this->addPhoto($photo);
		
	}
	
	public function addPhoto(UserPhoto $photo){
		if(empty($photo)){
			throw new InvalidArgumentException("\$photo is empty!");
		}
		if(empty($photo->fileName)){
			throw new InvalidArgumentException("photo fileName is have to been non empty string!");
		}
		
		$this->query->lockEndpoint();
		
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_USERS_PHOTOS'))
			->values(array(	"user_id" => $photo->userId, 
							"filename" => $photo->fileName,
							"status" => $photo->status));
		
		$this->query->exec($qb->getSQL());
		
		$photoId = $this->query->getLastInsertId();
		
		if($this->config->preModeration){
			if($photo->status == self::MODERATION_STATUS_APPROVED){
				$this->correctDefaultPhoto($photo->userId);
			}
		}
		else{
			$this->correctDefaultPhoto($photo->userId);
		}
		
		$newPhotoObj = $this->getPhoto($photoId);
		
		$this->query->unlockEndpoint();
		
		return $newPhotoObj;
		
	}
	
	
	/**
	 * Check if user don't have any default photo and 
	 * make default one of it's approved photos.
	 * Returns new default photo if corrected
	 * 
	 * @param integer $userId
	 * @return Photo $photo
	 */
	private function correctDefaultPhoto($userId){
		if(empty($userId)){
			throw new InvalidArgumentException("\$userId is empty!");
		}
		
		// If user don't have any default photo
		if(!$this->isUserHasDefaultPhoto($userId)){
			
			$filter = new UserPhotosFilter();
			$filter->setUserId($userId);
			
			if($this->config->preModeration){
				$filter->setStatusEqual(static::MODERATION_STATUS_APPROVED);
			}
			else{
				$filter->setStatusNotEqual(static::MODERATION_STATUS_DECLINED);
			}
			
			$userPhotos = $this->getPhotos($filter);
			
			if(count($userPhotos)){
				// Set as default first of the user's approved photos
				$this->setAsDefault($userPhotos[0]);
				Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_PHOTO_TAG . $userPhotos[0]->id);
				return $userPhotos[0];
			}
		}
		
		return null;
	}
	
	public function isUserHasDefaultPhoto($userId){
		$filter = new UserPhotosFilter();
		$filter->setUserId($userId);
		
		if($this->config->preModeration){
			$filter->setStatusEqual(static::MODERATION_STATUS_APPROVED);
		}
		else{
			$filter->setStatusNotEqual(static::MODERATION_STATUS_DECLINED);
		}
		
		$filter->setDefaultStatus(static::STATUS_DEFAULT_YES);
		
		return (count($this->getPhotos($filter)) != 0);
	}
	
	public function isUserHasPhoto($userId){
		$filter = new UserPhotosFilter();
		$filter->setUserId($userId);
		
		if($this->config->preModeration){
			$filter->setStatusEqual(static::MODERATION_STATUS_APPROVED);
		}
		else{
			$filter->setStatusNotEqual(static::MODERATION_STATUS_DECLINED);
		}
		
		return (count($this->getPhotos($filter)) != 0);
	}
	
	public function isPhotoBelongsToUser($photoId, $userId){
		$filter = new UserPhotosFilter();
		$filter->setPhotoId($photoId);
		
		$photos = $this->getPhotos($filter);
		
		return ($photos[0]->userId == $userId);
	}
	
	public function setAsDefault(UserPhoto $photo){
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		if($this->config->preModeration){
			if($photo->status !== static::MODERATION_STATUS_APPROVED){
				throw new UserPhotosException("Unapproved photo can't be set as default.", static::EXCEPTION_UNAPROVED_TO_DEFAULT);
			}
		}
		else{
			if($photo->status == static::MODERATION_STATUS_DECLINED){
				throw new UserPhotosException("Declined photo can't be set as default.", static::EXCEPTION_DECLINED_TO_DEFAULT);
			}
		}
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_USERS_PHOTOS'))
			->set(new Field('default'), static::STATUS_DEFAULT_NO)
			->where($qb->expr()->equal(new Field('user_id'), $photo->userId))
			->andWhere($qb->expr()->equal(new Field('default'), static::STATUS_DEFAULT_YES));

		$this->query->exec($qb->getSQL());

		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_USERS_PHOTOS'))
			->set(new Field('default'), static::STATUS_DEFAULT_YES)
			->where($qb->expr()->equal(new Field('id'), $photo->id))
			->limit(1);

		$this->query->exec($qb->getSQL());	
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_PHOTO_TAG . $photo->id);
	}
	
	public function deletePhoto(UserPhoto $photo, $uploaderModelName = null){
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		if($uploaderModelName === null){
			$uploaderModelName = self::IMAGE_TYPE_NAME;
		}
		
		ImageManager::deleteImage($photo->fileName, $uploaderModelName);
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_PHOTO_TAG . $photo->id);
		return $this->deletPhotoFromDB($photo);
	}
	
	public function deletPhotoFromDB(UserPhoto $photo){
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_USERS_PHOTOS'))
			->where($qb->expr()->equal(new Field('id'), $photo->id))
			->limit(1);
		
		$this->query->exec($qb->getSQL());
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_PHOTO_TAG . $photo->id);
		return $this->correctDefaultPhoto($photo->userId);
	}
	
	public function approvePhoto(UserPhoto $photo){
		$this->query->lockEndpoint();
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_USERS_PHOTOS'))
			->set(new Field('status'), static::MODERATION_STATUS_APPROVED)
			->set(new Field('modification_date'), new Func('NOW'))
			->where($qb->expr()->equal(new Field('id'), $photo->id))
			->limit(1);

		$this->query->exec($qb->getSQL());	
		
		$this->correctDefaultPhoto($photo->userId);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_PHOTO_TAG . $photo->id);
		$this->query->unlockEndpoint();
	}
	
	public function declinePhoto(UserPhoto $photo){
		$this->query->lockEndpoint();
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_USERS_PHOTOS'))
			->set(new Field('status'), static::MODERATION_STATUS_DECLINED)
			->set(new Field('modification_date'), new Func('NOW'))
			->where($qb->expr()->equal(new Field('id'), $photo->id));
		
		$this->query->exec($qb->getSQL());	
		
		$this->correctDefaultPhoto($photo->userId);
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_PHOTO_TAG . $photo->id);
		$this->query->unlockEndpoint();
	}
	
	public function getPhotos(UserPhotosFilter $filter, MysqlPager $pager = null, $cacheMinutes = null, $reduced = true){
		
		if($filter == null){
			$filter = new UserPhotosFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$userPhotos = array();
		if($this->query->countRecords()){
			while(($row = $this->query->fetchRecord()) != false){
				$photo = static::getFilledUserPhoto($row, $reduced);
				array_push($userPhotos, $photo);
			}
		}
		
		return $userPhotos;
	}
	
	public function getPhoto($photoId, $cacheMinutes = null, $reduced = true){
		$cacheName = ($reduced ? 'red' : 'nred');
		$photo = Reg::get('memcache')->getObject(self::MEMCACHE_PHOTO_TAG . $photoId, $cacheName);
		
		if(!empty($photo)){
			return $photo;
		}
		
		$filter = new UserPhotosFilter();
		$filter->setPhotoId($photoId);
		
		$photos = $this->getPhotos($filter, null, $cacheMinutes, $reduced);
		
		if(count($photos)){
			Reg::get('memcache')->setObject(self::MEMCACHE_PHOTO_TAG . $photos[0]->id, $cacheName, $photos[0], MemcacheWrapper::MEMCACHE_UNLIMITED);
			return $photos[0];
		}
		return false;
	}
	
	public static function getFilledUserPhoto($dbRow, $reduced = true){
		$userPhotoObj = new UserPhoto();
		$userPhotoObj->id = $dbRow['id'];
		$userPhotoObj->userId = $dbRow['user_id'];
		if(!$reduced){
			$UserManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
			$userPhotoObj->user = $UserManager->getUserById($dbRow['user_id']);
		}
		$userPhotoObj->fileName = $dbRow['filename'];
		$userPhotoObj->default = $dbRow['default'];
		$userPhotoObj->status = $dbRow['status'];
		$userPhotoObj->modificationDate = $dbRow['modification_date'];
		
		return $userPhotoObj;
	}
}

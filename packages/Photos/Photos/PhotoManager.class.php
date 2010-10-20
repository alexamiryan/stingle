<?php

/**
 * Manages photo uploads
 *
 */
class PhotoManager extends DbAccessor{

	/**
	 * A List of directories containing
	 * NOTE: An example structure of this array is:
	 * array(
	 * 		'/usr/local/photos/' => array (
	 * 			'imgWidth' => 50,
	 * 			'imgHeight' => 100,
	 * 			'minImgWidth' => 40,
	 * 			'minImgHeight' => 80,
	 * 		),
	 * 		'/var/lib/small_photos/' => array (
	 * 			'imgWidth' => 25,
	 * 			'imgHeight' => 50,
	 * 			'minImgWidth' => 20,
	 * 			'minImgHeight' => 40,
	 * 		)
	 * )
	 *
	 * @var array
	 */
	private $imageStorageDirs = array();

	/**
	 * Application image directory
	 *
	 * @var string
	 */
	private $imageBkgDir = '';

	private $genderBkgCodes = array(1 => 'm', 2 => 'f', 3 => 'c');

	/**
	 * Maximum number of photos allowed for a user to have
	 *
	 * @var int
	 */
	private $maxPhotoNumber = 0;

	const PHOTO_APPROVED = 1;
	const PHOTO_NOT_APPROVED = 2;
	
	const PHOTO_SIZE_BIG = 3;
	const PHOTO_SIZE_MEDIUM = 2;
	const PHOTO_SIZE_SMALL = 1;
	
	/**
	 * Class constructor
	 *
	 * @param array class configuration
	 * 				Example of a config is:
	 * 				$config = array();
	 *				$config['max_photo_count']			= $max_photos_count;
	 *				$config['image_bkg_dir']			= 'img'; // path to the folder containing image backgrounds
	 *
	 *				$config['dirs'][0]['path']			= 'uploads/photos/'; // there should not be slash in
	 * 																		 //	the begining and there must be one in the end
	 *
	 *				$config['dirs'][0]['imgWidth']		= 50;
	 *				$config['dirs'][0]['imgHeight'] 	= 60;
	 *				$config['dirs'][0]['minImgWidth']	= 40; // is not currently used
	 *				$config['dirs'][0]['minImgHeight']	= 30; // is not currently used
	 */
	public function __construct($config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);

		$this->imageBkgDir = $config->image_bkg_dir;
		$this->maxPhotoNumber = $config->max_photo_count;

		// Add directories
		foreach($config->dirs->toArray() as $dir){
			$this->addPhotoStorageDir($dir->path, $dir->imgWidth, $dir->imgHeight, $dir->minImgWidth, $dir->minImgHeight);
		}

	}

	/**
	 * Copies & resizes an uploaded image
	 *
	 * @param array $file e.g. $_FILES['photo']
	 * @param mixed $ownerId integer user id or wuser instance
	 * @param int $approved if set to true the photo will be automatically approved
	 */
	public function upload($file, $owner, $defaultPhoto = false, $approved = false){
		global $error, $um, $info;

		// If there are no folders to save images to throw an exception
		if(count($this->imageStorageDirs) == 0){
			throw new Exception("Instance of PhotoManager was not configured correctly, no image storage dirs are defined!");
		}

		$alreadyCopiedTo = array(); // Locations where the file was already copied


		// Deal with the user
		if(is_int($owner)){
			$owner = $um->getObjectById($owner);

		}
		$sex = $owner->sex;

		// Determine file name the images will be stored with
		$fileName = static::generateUniqueImgName();

		// check if the user has reached maximum upload limit
		$this->query->exec("select count(*) as `ph_cnt` from `users_photos` where `user_id`='" . $owner->getId() . "'");
		if($this->query->fetchField('ph_cnt') >= $this->maxPhotoNumber){
			$error->add('ERR_PHOTO_MAX_REACHED');
			return;
		}

		// Do the actual uploading
		foreach($this->imageStorageDirs as $dir){
			if(!$this->uploadPhoto($file, $dir['imgWidth'], $dir['imgHeight'], $dir['minImgWidth'], $dir['minImgHeight'], $dir['path'], $fileName, $sex)){
				$error->add('ERR_PHOTO_UPLOAD_FAILED');
				static::deletePhotoFromFolders($fileName, $alreadyCopiedTo);
				return;
			}

			$alreadyCopiedTo[] = $dir['path'];
		}

		// If all the previous stuff was successful insert into the db photo information
		$approvedValue = 0;
		if($approved){
			$approvedValue = 1;
		}

		$default = 0;
		if($defaultPhoto){
			$default = 1;
		}

		// Store the photo to the database
		if($this->query->exec("insert into `users_photos` (`user_id`, `filename`, `default`, `approved`)
						values('" . $owner->getId() . "', '$fileName', '$default', '$approvedValue')")){
			$info->add('SUCCESS_UPLOAD_PHOTO');
		}
		else{
			$error->add('UNEXPECTED_ERROR');
			static::deletePhotoFromFolders($fileName, $alreadyCopiedTo);
		}
	}

	/**
	 * Deletes files with the given name from all folders listed in $folderList
	 *
	 * @param string $fileName file to be deleted
	 * @param array $filderList List of folders(must have a trailing forward slash) to look for the file in.
	 * 							Example array('/tmp/', '/home/yervand/zibilnoc/');
	 */
	private function deletePhotoFromFolders($fileName, $folderList){
		foreach($folderList as $folder){
			@unlink($folder . $fileName);
		}
	}

	/**
	 * Deletes photos from the disk & database
	 *
	 * @param mixed $photoIds an int id or an array of ids
	 */
	public function delete($photoIds){
		global $um, $info;

		
		if(!is_array($photoIds) && !is_numeric($photoIds)){
			throw new InvalidArgumentException('Invalid value for argument $photoIds');
		}

		if(!is_array($photoIds)){
			$photoIds = array($photoIds);
		}
		
		// Determine file name & owner id
		$this->query->exec("select `filename`, `user_id` from `users_photos` where `id` in (" . implode(', ', $photoIds) . ")");
		list($fileName, $userId) = $this->query->fetchRecord(0);
		// delete photo files
		static::deletePhotoFromFolders($fileName, array_keys($this->imageStorageDirs));

		// Deleting the corresponding photo information from the database
		if($this->query->exec("delete from `users_photos` where `id` in (" . implode(', ', $photoIds) . ")")){

			// Determine whether the default photo has been deleted
			$this->query->exec("select count(*) as `def_cnt` from `users_photos` where `user_id`='$userId' and `default`='1'");
			if($this->query->fetchField('def_cnt') == 0){
				// If default was deleted set some other pic as the default
				$this->query->exec("update `users_photos` set `default`='1' where `user_id`='$userId' and `approved`='1' limit 1");
			}

			// If the user has no approved photos set has_photos to 0
			$this->query->exec("select count(*) as `cnt` from `users_photos` where `user_id`='$userId' and `approved`=1");
			if($this->query->fetchField('cnt') == 0){
				$um->updateUserExtra($userId, array('has_photo' => 0));
			}

			// Yay!
			$info->add('SUCCESS_PHOTO_DELETE');
		}
	}

	/**
	 * Approves an already uploaded photo
	 *
	 * @param int $photoId
	 */
	public function approve($photoId){
		$this->changeApprovalStatus($photoId, true);
	}

	/**
	 * Set this photo as default photo for it's owner
	 * NOTE: if some other photo was previously marked as default it will be unmarked
	 *
	 * @param integer $photoId
	 */
	public function setDefaultPhoto($photoId){
		global $error;

		// Check if the photo is approved
		$this->query->exec("SELECT `approved`, `user_id` FROM `users_photos` WHERE `id`='$photoId'");
		$photo = $this->query->fetchRecord();

		$userId = $photo["user_id"];
		if($photo["approved"] != 1){
			$error->add("ERR_UNAPPROVED_DEFAULT");
			return;
		}

		// If all the checks were passed mark the photo as default
		$this->query->exec("update `users_photos` set `default`='0' where `user_id`='" . $userId . "'");
		$this->query->exec("update `users_photos` set `default`='1' where `id`='$photoId'");
	}

	/**
	 * Upload user photo to server
	 *
	 * @param e.g. $_FILES['photo'] $file
	 * @param integer $width
	 * @param integer $height
	 * @param integer $min_width
	 * @param integer $min_height
	 * @param string $upload_dir (path for picture upload)
	 * @param string $filename (Use not random filename)
	 * @param integer $sex gender of the user the photo is being uploaded for
	 * @return $new_filename
	 */
	private function uploadPhoto($file, $width, $height, $min_width, $min_height, $upload_dir, $filename, $sex = 1){
		global $error;

		$image_path = $upload_dir . $filename;

		$img = new image($file["tmp_name"], $error);
		if(!$img){
			$error->add('ERR_INC_PHOTO');
			return false;
		}
		/*if($img->checkDimensions($min_width, $min_height)==2){
		$error->add('ERR_PHOTO_SMALL');
		return false;
		}*/
		if($img->checkDimensions($min_width, $min_height) == 6 or $img->checkDimensions($min_width, $min_height) == 7 or $img->checkDimensions($min_width, $min_height) == 2){
			$bgPath = "{$this->imageBkgDir}/bg_{$min_width}x{$min_height}_{$this->genderBkgCodes[$sex]}.gif";
			if(!$img->addBackround($bgPath)){
				$error->add('UNEXPECTED_ERROR');
				return false;
			}
		}
		elseif(!$img->resize($width, $height)){
			$error->add('UNEXPECTED_ERROR');
			return false;
		}

		if($img->writeJpeg($image_path)){
			return $filename;
		}

		// If writing file failed return false
		$error->add('UNEXPECTED_ERROR');

		return false;
	}

	/**
	 * Get photos of the specified user
	 *
	 * @param int $userId
	 * @param string $onlyApproved If TRUE only approved photos will be returned
	 * @param MysqlPager $pager
	 *
	 * @throws InvalidArgumentException in case of a non existant user id
	 */
	public function getPhotosByUser($userId, $onlyApproved = true, MysqlPager $pager = null, $cacheMinutes = 0){

		if(!is_numeric($userId)){
			throw new InvalidArgumentException('Param $userId should be an integer');
		}

		$where = '';
		if($onlyApproved){
			$where = ' AND approved=1';
		}

		$sqlQuery = "SELECT `id`, `filename`, `default`, `approved`
						FROM `users_photos`
						WHERE `user_id`='$userId' $where
						ORDER BY `default` DESC, `id` ASC";
		$photos = array();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		if($this->query->countRecords()){
			$photos = $this->query->fetchRecords();
		}

		return $photos;
	}
	
	/**
	 * Get count of photos of the specified user
	 *
	 * @param int $userId
	 * @param string $onlyApproved If TRUE only approved photos will be counted
	 *
	 * @throws InvalidArgumentException in case of a non existant user id
	 */
	public function countUserPhotos($userId, $onlyApproved = false){

		if(!is_numeric($userId)){
			throw new InvalidArgumentException('Param $userId should be an integer');
		}

		$where = '';
		if($onlyApproved){
			$where = ' AND approved=1';
		}

		$this->query->exec("SELECT COUNT(`id`) as `cnt`
							FROM `users_photos`
							WHERE `user_id`='$userId' $where
							ORDER BY `default` DESC, `id` ASC");

		return $this->query->fetchField("cnt");
	}
	
	/**
	 * Returns all photos from database paginated or without.
	 *
	 * @param PHOTO_APPROVED|PHOTO_NOT_APPROVED|null $approved
	 * 			PhotoManager::PHOTO_APPROVED or PhotoManager::PHOTO_NOT_APPROVED
	 * 			to get approved or not approved photos.
	 * 			For getting all photos pass null or nothing.
	 * @param MysqlPager $pager
	 *
	 * @return array Photos list array("id", "filename", "default", "approved")
	 */
	public function getPhotos($approved = null, MysqlPager $pager = null){
		if($approved === null){
			$approvedWhere = "";
		}
		elseif($approved === false){
			$approvedWhere = " WHERE `approved`='0'";
		}
		elseif($approved === true){
			$approvedWhere = " WHERE `approved`='1'";
		}
		else{
			throw new InvalidArgumentException("Wrong first parameter, it must be boolean or null");
		}
		
		$photos = array();
		
		$sqlQuery = "SELECT `id`, `user_id`, `filename`, `default`, `approved`
						FROM `users_photos`
						$approvedWhere
						ORDER BY `id` DESC";
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery);
		}
		else{
			$this->query->exec($sqlQuery);
		}
		
		if($this->query->countRecords()){
			$photos = $this->query->fetchRecords();
		}

		return $photos;
	}

	/**
	 * Adds the directory with it's respective image size to the list of dirs where images will be copied when uploaded
	 *
	 * @param string $path Folder path with a trailing slash
	 * @param int $imgWidth pixels
	 * @param int $imgHeight pixels
	 * @param int $imgMinWidth pixels, if null is set to $imgWidth
	 * @param int $imgMinHeight pixels, if null is set to $imgHeight
	 */
	public function addPhotoStorageDir($path, $imgWidth, $imgHeight, $imgMinWidth = null, $imgMinHeight = null){

		if(isset($this->imageStorageDirs[$path])){
			throw new InvalidArgumentException('The path \'$path\' was already added to the list');
		}

		if(is_null($imgMinWidth)){
			$imgWidth = $imgMinWidth;
		}

		if(is_null($imgMinHeight)){
			$imgWidth = $imgMinHeight;
		}

		$this->imageStorageDirs[$path] = array('path' => $path, 'imgWidth' => $imgWidth, 'imgHeight' => $imgHeight, 'minImgWidth' => $imgMinWidth, 'minImgHeight' => $imgMinHeight);
	}

	/**
	 * Gnerates a pseudounique file name base on current time
	 * NOTE: this function will append the file extension .jpg as well
	 *
	 * @return string file name
	 */
	private static function generateUniqueImgName(){
		return md5(uniqid(rand(), true)) . '.jpg';
	}
}

?>
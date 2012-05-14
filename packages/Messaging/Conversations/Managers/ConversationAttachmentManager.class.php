<?
class ConversationAttachmentManager extends DbAccessor{
	
	const TBL_CONVERSATION_ATTACHEMENTS		= "conversation_attachments";
	
	protected $config;
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->config = $config;
	}
	
	public function getAttachments(ConversationAttachmentFilter $filter, MysqlPager $pager = null){
		$attachments = array();
		
		$sqlQuery = $filter->getSQL();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery);
		}
		else{
			$this->query->exec($sqlQuery);
		}
		
		$attachmentRows = $this->query->fetchRecords();
		
		foreach ($attachmentRows as $attachmentRow){
			array_push($attachments, $this->getAttachmentObject($attachmentRow));
		}
		
		return $attachments;
	}
	
	/**
	 * 
	 * @param ConversationAttachmentFilter $filter
	 * @throws ConversationNotUniqueException
	 * @return ConversationAttachment
	 */
	public function getAttachment(ConversationAttachmentFilter $filter){
		$attachments = $this->getAttachments($filter);
		if(count($attachments) !== 1){
			throw new ConversationNotUniqueException("There is no such attachemnt or it is not unique.");
		}
		return $attachments[0];
	}
	
	public function getAttachmentsCount(ConversationAttachmentFilter $filter){
		$filter->setSelectCount();
		
		$sqlQuery = $filter->getSQL();

		return $this->query->exec($sqlQuery)->fetchField("cnt");
	}
	
	public function updateAttachmentMessageId($attachmentId, $newMessageId){
		if(empty($attachmentId) or !is_numeric($attachmentId)){
			throw new InvalidIntegerArgumentException("\$attachmentId have to be non zero integer.");
		}
		if(empty($newMessageId) or !is_numeric($newMessageId)){
			throw new InvalidIntegerArgumentException("\$newMessageId have to be non zero integer.");
		}
	
		$convMgr = Reg::get(ConfigManager::getConfig("Messaging", "Conversations")->Objects->ConversationManager);
		
		$filter = new ConversationMessagesFilter();
		$filter->setId($newMessageId);
		$message = $convMgr->getConversationMessage($filter);
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS'))
			->set(new Field('message_id'), $message->id)
			->where($qb->expr()->equal(new Field('id'), $attachmentId));
	
		$convMgr->setMessageHasAttachment($message);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * 
	 * @param array $file e.g. $_FILES['photo']
	 * @return ConversationAttachment
	 */
	public function addAttachment($file){
		$systemFilename = self::findNewFileName($this->config->uploadDir);
		
		$attachsImgUpConfig = $this->config->imageUploaderConfig;
		$attachsImgUpConfig->uploadDir = $this->config->uploadDir;
		if (in_array($file["type"], $attachsImgUpConfig->acceptedMimeTypes->toArray())){
			ImageUploader::upload($file, $systemFilename, $attachsImgUpConfig);
		}
		else{
			FileUploader::upload($file, $systemFilename, $this->config->uploadDir);
		}
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS'))
			->values(array(
					'system_filename' => $systemFilename,
					'filename' => $file['name'],
					'mime_type' => $file['type']));
		
		$attachmentId = $this->query->exec($qb->getSQL())->getLastInsertId();
		
		$filter = new ConversationAttachmentFilter();
		$filter->setId($attachmentId);
		
		return $this->getAttachment($filter);
	}
	
	public function outputAttachmentContents(ConversationAttachment $attachment){
		$filename = $this->config->uploadDir . $attachment->systemFilename;
		
		if (in_array($attachment->mimeType, $this->config->imageUploaderConfig->acceptedMimeTypes->toArray())){
			header("Content-Disposition: filename={$attachment->filename}");
		}
		else{
			header("Content-Disposition: attachment; filename={$attachment->filename}");
		}
		header("Content-Type: {$attachment->mimeType}");
		header("Content-Length: " . filesize($filename));
		readfile($filename);
	}
	
	public function deleteAttachment(ConversationAttachment $attachment){
		if(empty($attachment->id) or !is_numeric($attachment->id)){
			throw new InvalidIntegerArgumentException("\$attachment have to be filled ConversationAttachment object.");
		}
		if(empty($attachment->systemFilename)){
			throw new InvalidIntegerArgumentException("\$attachment have to be filled ConversationAttachment object.");
		}
		
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS'))
			->where($qb->expr()->equal(new Field('id'), $attachment->id));
		
		@unlink($this->config->uploadDir . $attachment->systemFilename);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function clearGarbage(){
		$db = MySqlDbManager::getDbObject();
		
		$db->lockTables(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS'), "w");
		
		$qb = new QueryBuilder();
	
		$qb->select(new Field("system_filename"))
			->from(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS', 'ConversationAttachmentManager'))
			->where($qb->expr()->isNull(new Field('message_id')))
			->andWhere($qb->expr()->greater($qb->expr()->diff(new Func("NOW"), new Field('date')), 60*60*24 * $this->config->attachmentsClearTimeout));
		
		$this->query->exec($qb->getSQL());
		while(($row = $this->query->fetchRecord()) != null){
			try{
				@unlink($this->config->uploadDir . $row['system_filename']);
			}
			catch (ErrorException $e){ }
		}
		
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS', 'ConversationAttachmentManager'))
			->where($qb->expr()->isNull(new Field('message_id')))
			->andWhere($qb->expr()->greater($qb->expr()->diff(new Func("NOW"), new Field('date')), 60*60*24 * $this->config->attachmentsClearTimeout));
		
		$deletedCount = $this->query->exec($qb->getSQL())->affected();
		
		$db->unlockTables();
		
		return $deletedCount;
	}
	
	private static function findNewFileName($uploadDir){
		$fileName = generateRandomString(32);
		while(true){
			if(file_exists($uploadDir . $fileName)){
				$fileName = $fileName = generateRandomString(32);
			}
			else{
				break;
			}
		}
		return $fileName;
	}
	
	protected function getAttachmentObject($attachmentRow){
		$attachment = new ConversationAttachment();
	
		$attachment->id = $attachmentRow['id'];
		$attachment->messageId = $attachmentRow['message_id'];
		$attachment->systemFilename = $attachmentRow['system_filename'];
		$attachment->filename = $attachmentRow['filename'];
		$attachment->mimeType = $attachmentRow['mime_type'];
		$attachment->isImage = in_array($attachment->mimeType, $this->config->imageUploaderConfig->acceptedMimeTypes->toArray());
		$attachment->date = $attachmentRow['date'];
	
		return $attachment;
	}
}
?>
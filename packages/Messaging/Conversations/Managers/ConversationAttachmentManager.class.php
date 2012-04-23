<?
class ConversationAttachmentManager extends DbAccessor{
	
	const TBL_CONVERSATION_ATTACHEMENTS		= "conversation_attachments";
	
	protected $uploadDir;
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->uploadDir = $config->uploadDir;
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
		
		return $this->query->exec($sqlQuery)->affected();
	}
	
	/**
	 * 
	 * @param array $file e.g. $_FILES['photo']
	 * @return ConversationAttachment
	 */
	public function addAttachment($file){
		$systemFilename = self::findNewFileName($this->uploadDir);
		
		$imageUploaderConfig = ConfigManager::getConfig("Image", "ImageUploader")->AuxConfig;
		$attachsImgUpConfig = clone($imageUploaderConfig);
		$attachsImgUpConfig->uploadDir = $this->uploadDir;
		
		if (in_array($file["type"], $imageUploaderConfig->acceptedMimeTypes->toArray())){
			ImageUploader::upload($file, $systemFilename, $attachsImgUpConfig);
		}
		else{
			FileUploader::upload($file, $systemFilename, $this->uploadDir);
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
	
	public function outputAttachmentContents(ConversationAttachmentFilter $filter){
		$attachment = $this->getAttachment($filter);
		
		$filename = $this->uploadDir . $attachment->systemFilename;
		
		header("Content-Disposition: attachment; filename={$attachment->filename}");
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
		
		@unlink($this->uploadDir . $attachment->systemFilename);
		
		return $this->query->exec($qb->getSQL())->affected();
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
		$attachment->date = $attachmentRow['date'];
	
		return $attachment;
	}
}
?>
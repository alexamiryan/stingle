<?php

class LinkShortener extends DbAccessor {

	const TBL_LINKS = 'links';
	
	const STATUS_CLICKED_NO = 0;
	const STATUS_CLICKED_YES = 1;
	
	const DEFAULT_EXPIRATION_BY_DAYS = 31;
	const EXPIRES_NEVER = 0;
	const LINK_ID_LENGTH = 16;
	
	protected $config = null;


	public function __construct(Config $config, $dbInstanceKey = null) {
		parent::__construct($dbInstanceKey);
		
		$this->config = $config;
	}

	public function getLinks(LinkShortenerFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = 0){
		
		if($filter == null){
			$filter = new LinkShortenerFilter();
		}
		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$links = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				$links[] = $this->getLinkObjectFromData($row);
			}
		}
		return $links;
	}
	
	public function getLink(LinkShortenerFilter $filter, $cacheMinutes = 0){
		$links = $this->getLinks($filter, null, $cacheMinutes);
		if(count($links) !== 1){
			throw new RuntimeException("There is no such link or it is not unique.");
		}
		return $links[0];
	}
	
	public function addLink(Link $link){

        $qb = new QueryBuilder();
		$insertArr = array(
			'link_id'			=> $link->linkId,
			'url'				=> $link->url,
			'expires'			=> $link->expires
		);
		
        $qb->insert(Tbl::get("TBL_LINKS"))->values($insertArr);

		return $this->query->exec($qb->getSQL())->getLastInsertId();
	}
	
	public function updateLink(Link $link){
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_LINKS'))
			->set(new Field('link_id'), $link->linkId)
			->set(new Field('url'), $link->url)
			->set(new Field('date'), $link->date)
			->set(new Field('expires'), $link->expires)
			->set(new Field('is_clicked'), $link->isClicked)
			->set(new Field('date_clicked'), $link->dateClicked)
			->where($qb->expr()->equal(new Field('id'), $link->id));
		
		try{
            return $this->query->exec($qb->getSQL())->affected();
        }
        catch(MySqlException $e){
            return false;
        }
	}
	
	public function deleteLink(Link $link){
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_LINKS'))
			->where($qb->expr()->equal(new Field('id'), $link->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function getLinkById($linkId){
		$filter = new LinkShortenerFilter();
		$filter->setLinkId($linkId);
		$filter->setOrderByIdDesc();
		
		$links = $this->getLinks($filter);
		if(count($links) > 0){
			return $links[0];
		}
		return false;
	}
	
	public function setLinkAsClickedById($linkId){
		$link = $this->getLinkById($linkId);
		if(!empty($link)){
			return $this->setLinkAsClicked($link);
		}
		return false;
	}
	
	public function setLinkAsClicked(Link $link){
		$link->isClicked = self::STATUS_CLICKED_YES;
		$link->dateClicked = date(DEFAULT_DATETIME_FORMAT);

		return $this->updateLink($link);
	}
	
	public function shortenUrl($url, $expires = null){
		if($expires === null){
			$expires = self::DEFAULT_EXPIRATION_BY_DAYS;
		}
		
		$link = new Link();
		
		$link->linkId = generateRandomString(self::LINK_ID_LENGTH, array(RANDOM_STRING_LOWERCASE, RANDOM_STRING_DIGITS));
		$link->url = $url;
		
		if(!empty($expires) and $expires !== self::EXPIRES_NEVER){
			$date = new DateTime();
			$date->add(new DateInterval("P" . $expires . "D"));
			$link->expires = $date->format(DEFAULT_DATETIME_FORMAT);
		}
		
		if($this->addLink($link)){
			return glink(parse($this->config->shortenerUrl, array('linkId' => $link->linkId)));
		}
		return false;
	}
	
	public function handleLink($linkId){
		$link = $this->getLinkById($linkId);
		if(!empty($link)){
			$this->setLinkAsClicked($link);
			redirect($link->url);
		}
		return false;
	}
	
	public function deleteExpiredLinks(){
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_LINKS'))
			->where($qb->expr()->less(new Field('expires'), new Func('NOW')));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function getLinkObjectFromData($data){
		$link = new Link();
		$link->id 				= $data['id'];
		$link->linkId 			= $data['link_id'];
		$link->url	 			= $data['url'];
		$link->date	 			= $data['date'];
		$link->expires 			= $data['expires'];
		$link->isClicked		= $data['is_clicked'];
		$link->dateClicked		= $data['date_clicked'];
		
		return $link;
	}
	
}

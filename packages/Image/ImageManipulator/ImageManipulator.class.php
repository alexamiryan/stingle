<?php
class ImageManipulator extends Model
{
	private $info;
	private $image_res;
	
	const DIMENSIONS_EQUAL 									= 1;
	const DIMENSIONS_SMALLER 								= 2;
	const DIMENSIONS_LARGER 								= 3;
	const DIMENSIONS_WIDTH_LARGER_HEIGHT_SMALLER 			= 4;
	const DIMENSIONS_HEIGHT_LARGER__WIDTH_SMALLER 			= 5;

	
	const CORNER_TOP_LEFT 		= 1;
	const CORNER_TOP_RIGHT 		= 2;
	const CORNER_BOTTOM_LEFT 	= 3;
	const CORNER_BOTTOM_RIGHT 	= 4;
	
	
	/**
	 * Image manipulation
	 * Supports jpg, gif, png
	 *
	 * @param string $filename
	 * @param wInfo $error
	 */
	public function __construct($filePath){
		if(empty($filePath)){
			throw new InvalidArgumentException("\$filename is empty!");
		}
		if(!file_exists($filePath)){
			throw new InvalidArgumentException("Given file $filePath does not exists.");
		}
		$this->image_res=$this->createImageRes($filePath);
		$this->info=getimagesize($filePath);
	}
	/**
	 * Destructor
	 */
	public function __destruct(){
		unset ($this->image_res);
	}

	/**
	 * Creat an image resource from file
	 *
	 * @param string $filename
	 */
	private function createImageRes($filename){
		if(($info=@getimagesize($filename)) == false or !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG))){
			throw new RuntimeException("Given file is not a image!");
		}
		switch ($info[2]){
			case IMAGETYPE_JPEG:
				$image_res=@imagecreatefromjpeg($filename);
				break;
			case IMAGETYPE_GIF:
				$image_res=@imagecreatefromgif($filename);
				break;
			case IMAGETYPE_PNG:
				$image_res=@imagecreatefrompng($filename);
				break;
		}
		if(!is_resource($image_res)){
			throw new RuntimeException("Unable to create resource from image!");
		}
		return $image_res;
	}

	/**
	 * Resize image
	 *
	 * @param int $width (Can be 0)
	 * @param int $height (Can be 0)
	 * @param bool $preserve_aspect_ratio
	 * @return bool
	 * 
	 * Only one of the width or height can be 0
	 */
	public function resize($width=0, $height=0, $preserve_aspect_ratio=true){
		if($preserve_aspect_ratio){
			$mode=0;

			if($width==0 and $height==0){
				return;
			}

			if($width==0){
				$mode=2;
			}
			elseif($height==0){
				$mode=1;
			}
			else{
				$future_width=$this->info[0]*$height/$this->info[1];
				$future_height=$this->info[1]*$width/$this->info[0];
				if($this->info[0]>=$width or $this->info[1]>=$height){
					if($this->info[0]>=$this->info[1]){
						if ($future_height<=$height) {
							$mode=1;
						}
						else{
							$mode=2;
						}
					}
					elseif($this->info[0]<$this->info[1]){
						if ($future_width<=$width) {
							$mode=2;
						}
						else{
							$mode=1;
						}
					}
				}
			}
			if($mode==1){
				$resized_image = imagecreatetruecolor($width, $this->info[1]*$width/$this->info[0]);
				if(!imagecopyresized($resized_image, $this->image_res, 0, 0, 0, 0, $width, $this->info[1]*$width/$this->info[0], $this->info[0], $this->info[1])){
					throw new RuntimeException("Unable to resize image!");
				}
				else{
					$this->info[1]=$this->info[1]*$width/$this->info[0];
					$this->info[0]=$width;
				}
			}
			elseif($mode==2){
				$resized_image = imagecreatetruecolor($this->info[0]*$height/$this->info[1], $height);
				if(!imagecopyresized($resized_image, $this->image_res, 0, 0, 0, 0, $this->info[0]*$height/$this->info[1], $height, $this->info[0], $this->info[1])){
					throw new RuntimeException("Unable to resize image!");
				}
				else{
					$this->info[0]=$this->info[0]*$height/$this->info[1];
					$this->info[1]=$height;
				}
			}
			else{
				$resized_image=$this->image_res;
			}
		}
		else{
			$resized_image = imagecreatetruecolor($width, $height);
			if(!imagecopyresized($resized_image, $this->image_res, 0, 0, 0, 0, $width, $height, $this->info[0], $this->info[1])){
				throw new RuntimeException("Unable to resize image!");
			}
			else{
				$this->info[0]=$width;
				$this->info[1]=$height;
			}
		}
		$this->image_res=$resized_image;
	}

	/**
	 * Write jpeg file
	 *
	 * @param string $to_filename (If is null, jpeg will be outputed directly)
	 * @param int $progrssive_jpeg (Set to 1 only for jpg outputs);
	 * @param int $quality (0-100)
	 * @return bool
	 */
	public function writeJpeg($to_filename='', $progrssive_jpeg=0, $quality=100){
		if($progrssive_jpeg){
			@imageinterlace($this->image_res, 1);
		}
		if($progrssive_jpeg){
			@imageinterlace($this->image_res, 0);
		}
		if(!imagejpeg($this->image_res, $to_filename, $quality)){
			throw new RuntimeException("Unable to write image file!");
		}
	}

	/**
	 * Write gif file
	 *
	 * @param string $to_filename (If is null, gif will be outputed directly)
	 * @return bool
	 */
	public function writeGif($to_filename=''){
		if(!imagegif($this->image_res, $to_filename)){
			throw new RuntimeException("Unable to write image file!");
		}
	}

	/**
	 * Write gif file
	 *
	 * @param string $to_filename (If is null, png will be outputed directly)
	 * @return bool
	 */
	public function writePng($to_filename=''){
		if(!imagepng($this->image_res, $to_filename)){
			throw new RuntimeException("Unable to write image file!");
		}
	}

	/**
	 * Stamps image with another image
	 *
	 * @param string $image_filename
	 * @param int $corner
	 * @param int $alpha (0-100)
	 * @return bool
	 */
	public function makeStamp($backgroundImagePath, $corner=self::CORNER_BOTTOM_RIGHT, $alpha=60){
		if(!in_array($corner, $this->getConstsArray('CORNER'))){
			throw new InvalidArgumentException("Invalid corner identifier given!");
		}
		if(!($alpha>=0 and $alpha<=100)){
			throw new InvalidArgumentException("Alpha needs to be between 0 and 100!");
		}

		$img_res=$this->createImageRes($backgroundImagePath);
		$img_info=getimagesize($backgroundImagePath);
		
		switch ($corner){
			case self::CORNER_TOP_LEFT:
				if(!imagecopymerge ($this->image_res, $img_res, 0, 0, 0, 0, $img_info[0], $img_info[1], $alpha)){
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
			case self::CORNER_TOP_RIGHT:
				if(!imagecopymerge ($this->image_res, $img_res, $this->info[0]-$img_info[0], 0, 0, 0, $img_info[0], $img_info[1], $alpha)){
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
			case self::CORNER_BOTTOM_LEFT:
				if(!imagecopymerge ($this->image_res, $img_res, 0, $this->info[1]-$img_info[1], 0, 0, $img_info[0], $img_info[1], $alpha)){
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
			case self::CORNER_BOTTOM_RIGHT:
				if(!imagecopymerge ($this->image_res, $img_res, $this->info[0]-$img_info[0], $this->info[1]-$img_info[1], 0, 0, $img_info[0], $img_info[1], $alpha)){
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
		}
	}

	/**
	 * Add background to the image
	 *
	 * @param string $background_filename
	 */
	public function addBackround($backgroundFilePath){
		$img_res=$this->createImageRes($backgroundFilePath);
		$img_info=getimagesize($backgroundFilePath);
		
		$checkResult = $this->checkDimensions($img_info[0],$img_info[1]);
		
		if($checkResult == self::DIMENSIONS_LARGER){
			throw new RuntimeException("Unable to add background because background is smaller than main image!");
		}
		
		$newImg=imagecreatetruecolor($img_info[0],$img_info[1]);

		if(!imagecopy ($newImg, $img_res,0,0,0,0,$img_info[0],$img_info[1])){
			throw new RuntimeException("Unable to add background!");
		}
		
		if(!imagecopy ($newImg, $this->image_res, ($img_info[0]-$this->info[0])/2,($img_info[1]-$this->info[1])/2,0,0,$this->info[0],$this->info[1])){
			throw new RuntimeException("Unable to add background!");
		}
		unset($this->image_res);
		$this->image_res=$newImg;
	}

	/**
	 * Crop image
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
	public function crop($x, $y, $width, $height){
		if(!is_numeric($x) or !is_numeric($y) or !is_numeric($width) or !is_numeric($height)){
			throw new InvalidArgumentException("Some of the parameters are not numeric!");
		}
		
		$cropped_image = imagecreatetruecolor($width, $height);
		if(!imagecopy($cropped_image, $this->image_res, 0, 0, $x, $y, $width, $height)){
			throw new RuntimeException("Unable to crop!");
		}
		
		@imagedestroy($this->image_res);
		$this->image_res=$cropped_image;
	}

	/**
	 * Check image dimensions
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return integer 
	 */
	public function checkDimensions($width, $height){
		if($this->info[0] == $width and $this->info[1] == $height){
			return self::DIMENSIONS_EQUAL;
		}
		elseif($this->info[0] >= $width or $this->info[1] >= $height){
				return self::DIMENSIONS_LARGER;
		}
		else{
			return self::DIMENSIONS_SMALLER;
		}
	}
	
	/**
	 * Check image dimensions
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return integer 
	 */
	public function checkDimensionsAdvanced($width, $height){
		if($this->info[0] == $width && $this->info[1] == $height){
			return self::DIMENSIONS_EQUAL;
		}
		elseif($this->info[0]>=$width){
			if($this->info[1]>=$height){
				return self::DIMENSIONS_LARGER;
			}
			else{
				return self::DIMENSIONS_WIDTH_LARGER_HEIGHT_SMALLER;
			}
		}
		else{
			if($this->info[1]>=$height){
				return self::DIMENSIONS_HEIGHT_LARGER__WIDTH_SMALLER;
			}
			else{
				return self::DIMENSIONS_SMALLER;
			}
		}
	}

	/**
	 * Return width and height of the image in array
	 * 
	 * @return array
	 */
	public function getDimensions(){
		return array($this->info[0], $this->info[1]);
	}
	/**
	 * Rotate image
	 *
	 * @param int $angle
	 * @param int $bg_red
	 * @param int $bg_green
	 * @param int $bg_blue
	 * @return bool
	 */
	public function rotate($angle, $bg_red=255, $bg_green=255, $bg_blue=255){
		if(!is_numeric($angle)){
			throw new InvalidArgumentException("\$angle have to have numeric value!");
		}
		if(!($bg_red>=0 and $bg_red<=255)){
			throw new InvalidArgumentException("\$bg_red needs to be between 0 and 255!");
		}
		if(!($bg_green>=0 and $bg_green<=255)){
			throw new InvalidArgumentException("\$bg_green needs to be between 0 and 255!");
		}
		if(!($bg_blue>=0 and $bg_blue<=255)){
			throw new InvalidArgumentException("\$bg_blue needs to be between 0 and 255!");
		}
		if(!($rotated_image=imagerotate($this->image_res, $angle, imagecolorexact($this->image_res, $bg_red, $bg_green, $bg_blue)))){
			throw new RuntimeException("Unable to rotate!");
		}

		@imagedestroy($this->image_res);
		$this->image_res=$rotated_image;
	}
}
?>
<?php
class ImageManipulator
{
	private $info;
	private $image_res;

	/**
	 * Image manipulation
	 * Supports jpg, gif, png
	 *
	 * @param string $filename
	 * @param wInfo $error
	 */
	public function __construct($filename){
		if(!($this->image_res=$this->createImageRes($filename))){
			throw new RuntimeException("Incorrect input file");
		}
		$this->info=getimagesize($filename);
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
		if(empty($filename) or
		!file_exists($filename) or
		!($info=@getimagesize($filename)) or
		!in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG))){
			return false;
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
			default:
				return false;
		}
		if(!is_resource($image_res)){
			return false;
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
				return false;
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
					return false;
				}
				else{
					$this->info[1]=$this->info[1]*$width/$this->info[0];
					$this->info[0]=$width;
				}
			}
			elseif($mode==2){
				$resized_image = imagecreatetruecolor($this->info[0]*$height/$this->info[1], $height);
				if(!imagecopyresized($resized_image, $this->image_res, 0, 0, 0, 0, $this->info[0]*$height/$this->info[1], $height, $this->info[0], $this->info[1])){
					return false;
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
				return false;
			}
			else{
				$this->info[0]=$width;
				$this->info[1]=$height;
			}
		}
		$this->image_res=$resized_image;
		
		return true;
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
			return false;
		}
		return true;
	}

	/**
	 * Write gif file
	 *
	 * @param string $to_filename (If is null, gif will be outputed directly)
	 * @return bool
	 */
	public function writeGif($to_filename=''){
		if(!imagegif($this->image_res, $to_filename)){
			return false;
		}
		return true;
	}

	/**
	 * Write gif file
	 *
	 * @param string $to_filename (If is null, png will be outputed directly)
	 * @return bool
	 */
	public function writePng($to_filename=''){
		if(!imagepng($this->image_res, $to_filename)){
			return false;
		}
		return true;
	}

	/**
	 * Stamps image with another image
	 *
	 * @param string $image_filename
	 * @param int $corner (1-top-left, 2-top-right, 3-bottom-left, 4-bottom-right)
	 * @param int $alpha (0-100)
	 * @return bool
	 */
	public function makeStamp($image_filename, $corner=4, $alpha=60){
		if(!($corner>=1 and $corner<=4)){
			return false;
		}
		if(!($alpha>=0 and $alpha<=100)){
			return false;
		}
		$img_res=$this->createImageRes($image_filename);
		if(!$img_res){
			return false;
		}
		$img_info=getimagesize($image_filename);
		switch ($corner){
			case 1:
				if(!imagecopymerge ($this->image_res, $img_res, 0, 0, 0, 0, $img_info[0], $img_info[1], $alpha)){
					return false;
				}
				break;
			case 2:
				if(!imagecopymerge ($this->image_res, $img_res, $this->info[0]-$img_info[0], 0, 0, 0, $img_info[0], $img_info[1], $alpha)){
					return false;
				}
				break;
			case 3:
				if(!imagecopymerge ($this->image_res, $img_res, 0, $this->info[1]-$img_info[1], 0, 0, $img_info[0], $img_info[1], $alpha)){
					return false;
				}
				break;
			case 4:
				if(!imagecopymerge ($this->image_res, $img_res, $this->info[0]-$img_info[0], $this->info[1]-$img_info[1], 0, 0, $img_info[0], $img_info[1], $alpha)){
					return false;
				}
				break;
		}
		return true;
	}

	/**
	 * Add background to the image
	 *
	 * @param string $background_filename
	 */
	public function addBackround($background_filename){
		if(!file_exists($background_filename)){
			return false;
		}
		$img_res=$this->createImageRes($background_filename);
		if(!is_resource($img_res)){
			return false;
		}
		$img_info=getimagesize($background_filename);
		if($this->checkDimensions($img_info[0],$img_info[1])!=6 and $this->checkDimensions($img_info[0],$img_info[1])!=7 and $this->checkDimensions($img_info[0],$img_info[1])!=2){
			return false;
		}
		$img=imagecreatetruecolor($img_info[0],$img_info[1]);
		if(!is_resource($img)){
			return false;
		}

		if(!imagecopy ($img, $img_res,0,0,0,0,$img_info[0],$img_info[1])){
			return false;
		}
		
		if(!imagecopy ($img, $this->image_res, ($img_info[0]-$this->info[0])/2,($img_info[1]-$this->info[1])/2,0,0,$this->info[0],$this->info[1])){
			return false;
		}
		unset($this->image_res);
		$this->image_res=$img;
		return true;
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
			return false;
		}
		$cropped_image = imagecreatetruecolor($width, $height);
		if(!imagecopy($cropped_image, $this->image_res, 0, 0, $x, $y, $width, $height)){
			return false;
		}
		@imagedestroy($this->image_res);
		$this->image_res=$cropped_image;
		return true;
	}

	/**
	 * Check image dimensions
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return 
	 * 1 if image dimansions equals to specified width and height<br>
	 * 2 if image smaller than specified width and height<br>
	 * 3 if image larger than specified width and height<br>
	 * 4 if width is larger or equals and height is smaller<br>
	 * 5 if height is larger or equals and width is smaller<br>
	 * 6 if width is larger and height is smaller or equals<br>
	 * 7 if height is larger and width is smaller or equals<br>
	 * 0 if none of ifs pass
	 */
	public function checkDimensions($width, $height){
		if($this->info[0]==$width && $this->info[1]==$height){
			return 1;
		}
		elseif($this->info[0]<$width && $this->info[1]<$height){
			return 2;
		}
		elseif($this->info[0]>$width && $this->info[1]>$height){
			return 3;
		}
		elseif($this->info[0]>=$width && $this->info[1]<$height){
			return 4;
		}
		elseif($this->info[0]<$width && $this->info[1]>=$height){
			return 5;
		}
		elseif($this->info[0]>$width && $this->info[1]<=$height){
			return 6;
		}
		elseif($this->info[0]<=$width && $this->info[1]>$height){
			return 7;
		}
		else{
			return 0;
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
			return false;
		}
		if(!($bg_red>=0 and $bg_red<=255)){
			return false;
		}
		if(!($bg_green>=0 and $bg_green<=255)){
			return false;
		}
		if(!($bg_blue>=0 and $bg_blue<=255)){
			return false;
		}
		if(!($rotated_image=imagerotate($this->image_res, $angle, imagecolorexact($this->image_res, $bg_red, $bg_green, $bg_blue)))){
			return false;
		}
		@imagedestroy($this->image_res);
		$this->image_res=$rotated_image;
		return true;
	}
}
?>
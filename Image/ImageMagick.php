<?php

class Shahid_Image_ImageMagick extends Shahid_Image_Abstract
{
	public function rotate($newFile, $angle) 
	{
		$imagick = new Imagick();
		$imagick->readImage($this->_file);

		$imagick->rotateImage(new ImagickPixel('#ffffff'), $angle);
		//$imagick->rotateImage(new ImagickPixel('transparent'), $angle);

		$imagick->writeImage($newFile);
		$imagick->clear();
		$imagick->destroy();
	}

	public function flip($newFile, $mode)
	{
		$imagick = new Imagick();
		$imagick->readImage($this->_file);

		switch ($mode) {
			case Shahid_Image_Abstract::FLIP_VERTICAL:
				$imagick->flipImage();
				break;
			case Shahid_Image_Abstract::FLIP_HORIZONTAL:
				$imagick->flopImage();
				break;
		}
		
		$imagick->writeImage($newFile);
		
		$imagick->clear();
		$imagick->destroy();
	}
	
	protected function _resize($newFile, $newWidth, $newHeight) 
	{
		$imagick = new Imagick();
		$imagick->readImage($this->_file);

		$imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);

		$imagick->writeImage($newFile);
		$imagick->clear();
		$imagick->destroy();
	}

	protected function _crop($newFile, $resizeWidth, $resizeHeight, $newWidth, $newHeight, $cropX, $cropY, $resize = true) 
	{
		$imagick = new Imagick();
		$imagick->readImage($this->_file);

		if ($resize) {
			/**
			 * Resize
			 */
			$imagick->resizeImage($resizeWidth, $resizeHeight, Imagick::FILTER_LANCZOS, 1);
		}

		/**
		 * Crop
		 */
		$imagick->cropImage($newWidth, $newHeight, $cropX, $cropY);

		$imagick->writeImage($newFile);
		$imagick->clear();
		$imagick->destroy();
	}	
	
	public function watermarkImage($overlayFile, $type)
	{
		$padding = 0;
		$imagick = new Imagick();
		$imagick->readImage($this->_file);
		
		$overlay = new Imagick();
		$overlay->readImage($overlayFile);
		
		$info = getimagesize($overlayFile);
		$overlayWidth  = $info[0];
		$overlayHeight = $info[1];
		if ($this->_width < $overlayWidth + $padding || $this->_height < $overlayHeight + $padding) {
			return false;
		}
		
		$positions = array();
		switch ($type) {
			case Shahid_Image_Abstract::POS_TOP_LEFT:
				$positions[] = array(0 + $padding, 0 + $padding);
				break;
			case Shahid_Image_Abstract::POS_TOP_RIGHT:
				$positions[] = array($this->_width - $overlayWidth - $padding, 0 + $padding);
				break;
			case Shahid_Image_Abstract::POS_BOTTOM_RIGHT:
				$positions[] = array($this->_width - $overlayWidth - $padding, $this->_height - $overlayHeight - $padding);
				break;
			case Shahid_Image_Abstract::POS_BOTTOM_LEFT:
				$positions[] = array(0 + $padding, $this->_height - $overlayHeight - $padding);
				break;
			case Shahid_Image_Abstract::POS_MIDDLE_CENTER:
				$positions[] = array(($this->_width - $overlayWidth - $padding) / 2, ($this->_height - $overlayHeight - $padding) / 2);
				break;
			default:
				throw new Exception('Do not support '.$position.' type of alignment');
				break;
		}
	
		$min = null;
		$minColors = 0;
	
		foreach($positions as $position) {
			$colors = $imagick->getImageRegion( $overlayWidth, $overlayHeight, $position[0], $position[1])->getImageColors();
	
			if ($min === null || $colors <= $minColors) {
				$min = $position;
				$minColors = $colors;
			}
		}
		
		$imagick->compositeImage($overlay, Imagick::COMPOSITE_OVER, $min[0], $min[1]);
			
	    $imagick->writeImage($this->_file);
	    $overlay->clear();
	    $overlay->destroy();
	    $imagick->clear();
            $imagick->destroy();
	}
	
	public function watermarkText($overlayText, $position, 
							$param = array('rotation' => 0, 'opacity' => 50, 'color' => 'FFF', 'size' => null))
	{
		$imagick = new Imagick();
		$imagick->readImage($this->_file);
		
		$size = null;
		if ($param['size']) {
			$size = $param['size'];
		} else {
			$text = new ImagickDraw();
			$text->setFontSize(12);
			$text->setFont($this->_watermarkFont);
			$im = new Imagick();
	
			$stringBox12 = $im->queryFontMetrics($text, $overlayText, false);
			$string12    = $stringBox12['textWidth'];
			$size        = (int)($this->_width / 2) * 12 / $string12;
			
			$im->clear();
			$im->destroy();
			$text->clear();
			$text->destroy();
		}
	
	    $draw = new ImagickDraw();	
	    $draw->setFont($this->_watermarkFont);
	    $draw->setFontSize($size);
	    $draw->setFillOpacity($param['opacity']);
	
		switch($position) {
			case Shahid_Image_Abstract::POS_TOP_LEFT:
				$draw->setGravity(Imagick::GRAVITY_NORTHWEST);
				break;
			case Shahid_Image_Abstract::POS_TOP_RIGHT:
				$draw->setGravity(Imagick::GRAVITY_NORTHEAST);
				break;
			case Shahid_Image_Abstract::POS_MIDDLE_CENTER:
				$draw->setGravity(Imagick::GRAVITY_CENTER);
				break;
			case Shahid_Image_Abstract::POS_BOTTOM_LEFT:
				$draw->setGravity(Imagick::GRAVITY_SOUTHWEST);				
				break;
			case Shahid_Image_Abstract::POS_BOTTOM_RIGHT:
				$draw->setGravity(Imagick::GRAVITY_SOUTHEAST);
				break;
			default:
				throw new Exception('Do not support '.$position.' type of alignment');
				break;
		}
	    
	    $draw->setFillColor('#'.$param['color']);	
	    $imagick->annotateImage($draw, 5, 5, $param['rotation'], $overlayText);
	
	    $imagick->writeImage($this->_file);
	    $imagick->clear();
	    $imagick->destroy();
	    $draw->clear();
	    $draw->destroy();
	}
}

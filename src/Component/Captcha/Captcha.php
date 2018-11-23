<?php

namespace Lite\Component\Captcha;

/**
 * @class Captcha
 * @brief 验证码生成类库
 */
class Captcha{
	private $GdFgColor;
	private $GdBgColor;
	private $GdShadowColor;
	
	/** Wave configuration in X and Y axes */
	private $YPeriod = 12;
	private $YAmplitude = 14;
	private $XPeriod = 11;
	private $XAmplitude = 5;
	
	/** GD image */
	private $im;
	
	/** Width of the image */
	public $width = 200;
	
	/** Height of the image */
	public $height = 70;
	
	/** Min word length (for non-dictionary random text generation) */
	public $minWordLength = 4;
	
	/**
	 * Max word length (for non-dictionary random text generation)
	 * Used for dictionary words indicating the word-length
	 * for font-size modification purposes
	 */
	public $maxWordLength = 5;
	
	/** Session name to store the original text */
	private static $session_var = '_lite_captcha_';
	
	/** Background color in RGB-array */
	public $background_color = array(255, 255, 255);
	
	/** Foreground colors in RGB-array */
	public $colors = array(
		array(27, 78, 181), // blue
		array(22, 163, 35), // green
		array(214, 36, 7),  // red
	);
	
	/** Shadow color in RGB-array or null */
	public $shadow_color = null; //array(0, 0, 0);
	public $font_size = 25;
	
	/**
	 * Font configuration
	 * - font: TTF file
	 * - spacing: relative pixel space between character
	 * - minSize: min font size
	 * - maxSize: max font size
	 */
	public $all_fonts = array(
		'Time' => array('spacing' => 2, 'minSize' => 22, 'maxSize' => 24, 'font' => 'font.ttf'),
		'AHGBold' => array('spacing' => 2, 'minSize' => 22, 'maxSize' => 24, 'font' => 'AHGBold.ttf'),
	);
	
	public $font = 'AHGBold';
	
	/** letter rotation clockwise */
	public $max_rotation = 8;
	
	/**
	 * Internal image size factor (for better image quality)
	 * 1: low, 2: medium, 3: high
	 */
	public $scale = 3;
	
	/**
	 * Blur effect for better image quality (but slower image processing).
	 * Better image results with scale=3
	 */
	public $blur = false;
	
	/** Debug? */
	public $debug = false;
	
	/** Image format: jpeg or png */
	public $image_format = 'jpeg';
	
	public function __construct(){
	}
	
	public function createImage(&$text = ''){
		$ini = microtime(true);
		/** Initialization */
		$this->imageAllocate();
		
		/** Text insertion */
		$text = $text ?: $this->getRandomCaptchaText();
		self::saveText($text);
		
		$font_config = $this->all_fonts[$this->font];
		$this->writeText($text, $font_config);
		
		/** Transformations */
		$this->waveImage();
		if($this->blur && function_exists('imagefilter')){
			imagefilter($this->im, IMG_FILTER_GAUSSIAN_BLUR);
		}
		$this->reduceImage();
		if($this->debug){
			imagestring($this->im, 1, 1, $this->height-8, "$text {$font_config['font']} ".round((microtime(true)-$ini)*1000)."ms", $this->GdFgColor);
		}
		
		/** Output */
		$this->writeImage();
		$this->cleanup();
	}
	
	/**
	 * Save captcha text in session
	 * @param $text
	 */
	private static function saveText($text){
		if(!headers_sent()){
			session_start();
		}
		$_SESSION[self::$session_var] = $text;
	}
	
	/**
	 * Get last captcha code store in session
	 * @return mixed
	 */
	public static function getLastCode(){
		if(!headers_sent()){
			session_start();
		}
		return $_SESSION[self::$session_var];
	}
	
	/**
	 * @param $code
	 * @return bool
	 */
	public static function validateCode($code){
		return $code && strtolower(self::getLastCode()) == strtolower($code);
	}
	
	/**
	 * Creates the image resources
	 */
	protected function imageAllocate(){
		// Cleanup
		if(!empty($this->im)){
			imagedestroy($this->im);
		}
		$this->im = imagecreatetruecolor($this->width*$this->scale, $this->height*$this->scale);
		// Background color
		$this->GdBgColor = imagecolorallocate($this->im, $this->background_color[0], $this->background_color[1], $this->background_color[2]);
		imagefilledrectangle($this->im, 0, 0, $this->width*$this->scale, $this->height*$this->scale, $this->GdBgColor);
		// Foreground color
		$color = $this->colors[mt_rand(0, sizeof($this->colors)-1)];
		$this->GdFgColor = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);
		// Shadow color
		if(!empty($this->shadow_color) && is_array($this->shadow_color) && sizeof($this->shadow_color)>=3){
			$this->GdShadowColor = imagecolorallocate($this->im, $this->shadow_color[0], $this->shadow_color[1], $this->shadow_color[2]);
		}
	}
	
	/**
	 * Random text generation
	 * @param int $length
	 * @return string Text
	 */
	protected function getRandomCaptchaText($length = 0){
		if(!$length){
			$length = rand($this->minWordLength, $this->maxWordLength);
		}
		$words = "abcdefghijlmnopqrstvwyz";
		$vocals = "aeiou";
		$text = "";
		$vocal = rand(0, 1);
		for($i = 0; $i<$length; $i++){
			if($vocal){
				$text .= substr($vocals, mt_rand(0, 4), 1);
			} else{
				$text .= substr($words, mt_rand(0, 22), 1);
			}
			$vocal = !$vocal;
		}
		return $text;
	}
	
	/**
	 * Text insertion
	 * @param $text
	 * @param array $font_config
	 */
	protected function writeText($text, $font_config = array()){
		if(empty($font_config)){
			$font_config = $this->all_fonts[$this->font];
		}
		
		// Full path of font file
		$font_file = dirname(__FILE__).'/'.$font_config['font'];
		
		//Increase font-size for shortest words: 9% for each glyp missing
		$lettersMissing = $this->maxWordLength-strlen($text);
		$font_size_factor = 1+($lettersMissing*0.09);
		
		//$fontspace = $this->width/strlen($text)-2;
		//$minSize = $fontspace;
		//$maxSize= $fontspace;
		// Text generation (char by char)
		$x = 20*$this->scale;
		$y = round(($this->height*27/40)*$this->scale);
		$length = strlen($text);
		for($i = 0; $i<$length; $i++){
			$degree = rand($this->max_rotation*-1, $this->max_rotation);
			$fontsize = rand($this->font_size+1, $this->font_size-1)*$this->scale*$font_size_factor;
			//$fontsize = $maxSize*$this->scale*$fontSizefactor;
			$letter = substr($text, $i, 1);
			if($this->shadow_color){
				imagettftext($this->im, $fontsize, $degree, $x+$this->scale, $y+$this->scale, $this->GdShadowColor, $font_file, $letter);
			}
			$coords = imagettftext($this->im, $fontsize, $degree, $x, $y, $this->GdFgColor, $font_file, $letter);
			$x += ($coords[2]-$x)+($font_config['spacing']*$this->scale);
		}
	}
	
	/**
	 * Wave filter
	 */
	protected function waveImage(){
		// X-axis wave generation
		$xp = $this->scale*$this->XPeriod*rand(1, 3);
		$k = rand(0, 100);
		for($i = 0; $i<($this->width*$this->scale); $i++){
			imagecopy($this->im, $this->im, $i-1, sin($k+$i/$xp)*($this->scale*$this->XAmplitude), $i, 0, 1, $this->height*$this->scale);
		}
		// Y-axis wave generation
		$k = rand(0, 100);
		$yp = $this->scale*$this->YPeriod*rand(1, 2);
		for($i = 0; $i<($this->height*$this->scale); $i++){
			imagecopy($this->im, $this->im, sin($k+$i/$yp)*($this->scale*$this->YAmplitude), $i-1, 0, $i, $this->width*$this->scale, 1);
		}
	}
	
	/**
	 * Reduce the image to the final size
	 */
	protected function reduceImage(){
		$imResampled = imagecreatetruecolor($this->width, $this->height);
		imagecopyresampled($imResampled, $this->im, 0, 0, 0, 0, $this->width, $this->height, $this->width*$this->scale, $this->height*$this->scale);
		imagedestroy($this->im);
		$this->im = $imResampled;
	}
	
	/**
	 * File generation
	 */
	protected function writeImage(){
		if($this->image_format == 'png' && function_exists('imagepng')){
			header("Content-type: image/png");
			imagepng($this->im);
		} else{
			header("Content-type: image/jpeg");
			imagejpeg($this->im, null, 90);
		}
	}
	
	/**
	 * Cleanup
	 */
	protected function cleanup(){
		imagedestroy($this->im);
	}
}
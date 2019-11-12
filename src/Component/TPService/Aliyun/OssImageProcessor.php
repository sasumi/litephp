<?php
namespace Lite\Component\TPService\Aliyun;

use Exception;

/**
 * 阿里云OSS图片处理器
 * Class OssImageProcessor
 * @package Lite\Component\TPService\Aliyun
 */
class OssImageProcessor {
	const OSS_QUERY_KEY = '?x-oss-process=image/';

	const PROCESSOR_RESIZE = self::PROCESSOR_RESIZE;
	
	const PROCESSOR_WATERMARK = self::PROCESSOR_WATERMARK;

	//OSS水印支持字体列表
	const FONT_TYPE_MAP = [
		'wqy-zenhei'        => '文泉驿正黑',
		'wqy-microhei'      => '文泉微米黑',
		'fangzhengshusong'  => '方正书宋',
		'fangzhengkaiti'    => '方正楷体',
		'fangzhengheiti'    => '方正黑体',
		'fangzhengfangsong' => '方正仿宋',
		'droidsansfallback' => 'DroidSansFallback',
	];

	//水印位置列表
	const WATERMARK_POSITION_MAP = [
		'nw'     => '左上',
		'north'  => '上',
		'ne'     => '右上',
		'west'   => '左',
		'center' => '中',
		'east'   => '右',
		'south'  => '下',
		'sw'     => '左下',
		'se'     => '右下',
	];

	/**
	 * Base64 Url安全编码
	 * @param $text
	 * @return mixed
	 */
	public static function base64UrlSafeEncode($text){
		return str_replace(['+', '/'], ['-', '_'], base64_encode($text));
	}

	/**
	 * 判断是否存在图片处理请求
	 * @param $image_src
	 * @return bool
	 */
	public static function existsProcessor($image_src){
		$pattern = preg_quote(self::OSS_QUERY_KEY, '/');
		return !!preg_match("/$pattern/", $image_src);
	}

	/**
	 * 解析图片URL，分析其中包含的处理器
	 * @param string $image_src
	 * @return array
	 */
	public static function resolveProcessorList($image_src){
		$config = [];
		if(!self::existsProcessor($image_src)){
			return [$image_src, $config];
		}

		$pos = stripos($image_src, self::OSS_QUERY_KEY);
		$tail_str = substr($image_src, $pos + strlen(self::OSS_QUERY_KEY));
		$tmp = explode('/', $tail_str);
		for($i = 0; $i < count($tmp); $i++){
			$all = explode(',', $tmp[$i]);
			$processor_name = array_shift($all);
			$param = [];
			foreach($all as $key => $item){
				list($k, $v) = explode('_', $item);
				$param[$k] = $v;
			}
			$config[$processor_name] = $param;
		}
		return [substr($image_src, 0, $pos), $config];
	}

	/**
	 * 构建处理器追加字符串
	 * @param array $processor_config
	 * @return string
	 */
	public static function buildProcessorConfig(array $processor_config){
		$str = [];
		foreach($processor_config as $processor_name => $config){
			$ps = [$processor_config];
			foreach($config as $key => $val){
				$ps[] = $key.'_'.$val;
			}

			$str[] = join(',', $ps);
		}
		return join('/', $str);
	}

	/**
	 * 移除所有图片处理器，获取原图地址
	 * @param string $image_src
	 * @return bool|string
	 */
	public static function getOriginalImage($image_src){
		if(!self::existsProcessor($image_src)){
			return $image_src;
		}
		$idx = stripos($image_src, self::OSS_QUERY_KEY);
		return substr($image_src, $idx);
	}

	/**
	 * 去除图片缩放
	 * @param string $image_src
	 * @return bool|string
	 */
	public static function removeImageResize($image_src){
		return self::removeImageProcessor($image_src, self::PROCESSOR_RESIZE);
	}

	/**
	 * 获取图片缩略图
	 * @param string $image_src
	 * @param $max_resize_w
	 * @param $max_resize_h
	 * @return string
	 */
	public static function getImageThumb($image_src, $max_resize_w, $max_resize_h = null){
		$max_resize_h = $max_resize_h ?: $max_resize_w;
		return self::addImageProcessor($image_src, self::PROCESSOR_RESIZE, [
			'm' => 'lfit',
			'h' => $max_resize_h,
			'w' => $max_resize_w,
		]);
	}

	/**
	 * 添加图片处理器
	 * @param string $image_src
	 * @param string $processor_name
	 * @param array $param
	 * @return string
	 */
	public static function addImageProcessor($image_src, $processor_name, array $param = []){
		$image_src = self::removeImageProcessor($image_src, $processor_name);
		$c = [];
		$c[$processor_name] = $param;
		if(!self::existsProcessor($image_src)){
			return $image_src.self::OSS_QUERY_KEY.self::buildProcessorConfig($c);
		}
		return $image_src.'/'.self::buildProcessorConfig($c);
	}

	/**
	 * 移除图片处理器
	 * @param string $image_src
	 * @param string $processor_name
	 * @return bool|string
	 */
	public static function removeImageProcessor($image_src, $processor_name){
		if(!self::existsProcessor($image_src)){
			return $image_src;
		}
		list($image_origin_src, $processor_config) = self::resolveProcessorList($image_src);
		foreach($processor_config as $p => $v){
			if($processor_name === $p){
				unset($processor_config[$p]);
				break;
			}
		}
		$process_str = self::buildProcessorConfig($processor_config);
		if(!$process_str){
			return self::getOriginalImage($image_src);
		}
		return $image_origin_src.self::OSS_QUERY_KEY.$process_str;
	}

	/**
	 * 去除URL中HOST部分
	 * @param string $src
	 * @return string
	 */
	public static function trimHost($src){
		$tmp = parse_url($src);
		$pos = stripos($src, $tmp['host']);
		if($pos >= 0){
			return substr($src, $pos + strlen($tmp['host'])+1);
		}
		return $src;
	}

	/**
	 * 确保两个链接在同一个域名中
	 * @param string $url1
	 * @param string $url2
	 * @return bool
	 */
	private static function assertSameHost($url1, $url2){
		$tmp1 = parse_url($url1);
		$tmp2 = parse_url($url2);
		return strcasecmp($tmp1['host'], $tmp2['host']) === 0;
	}

	/**
	 * 移除水印
	 * @param string $image_src
	 * @return bool|string
	 */
	public static function removeWatermark($image_src){
		return self::removeImageProcessor($image_src, self::PROCESSOR_WATERMARK);
	}

	/**
	 * 添加图片水印
	 * @param string $image_src 图片地址，注意：如果是缩略图，则返回缩略图添加完水印的效果图
	 * @param string $watermark_src
	 * @param array $cfg
	 * @return string
	 */
	public static function patchWatermarkWithImage($image_src, $watermark_src, $cfg){
		if(!self::assertSameHost($image_src, $watermark_src)){
			throw new Exception("图片与水印必须在同一个域名中");
		}
		$config = array_merge([
			'opacity'         => 100,
			'position'        => 'nw',
			'percent'         => 30,
			'horizonPadding'  => 0, //水平内边距
			'verticalPadding' => 0, //垂直内边距
		], $cfg);

		if(!self::WATERMARK_POSITION_MAP[$config['position']]){
			throw new Exception("图片水印配置中：“位置”设置错误(".$config['position'].')');
		}

		$watermark_src = self::trimHost($watermark_src);
		$watermark_src = self::addImageProcessor($watermark_src, self::PROCESSOR_RESIZE, [
			'P' => $config['percent'],
		]);

		return self::addImageProcessor($image_src, self::PROCESSOR_WATERMARK, [
			'image' => base64_encode($watermark_src),
			't'     => $config['opacity'],
			'g'     => $config['position'],
			'x'     => $config['horizonPadding'],
			'y'     => $config['verticalPadding'],
		]);
	}

	/**
	 * 添加文字水印
	 * @param string $image_src 图片地址，注意：如果是缩略图，则返回缩略图添加完水印的效果图
	 * @param string $text
	 * @param array $cfg
	 * @return string
	 */
	public static function patchWatermarkWithText($image_src, $text, $cfg){
		$config = array_merge([
			'font'            => 'wqy-zenhei', //字体
			'color'           => '000000', //颜色，不带#
			'size'            => 40, //大小（像素）
			'shadow'          => 50, //阴影透明度（0~100）
			'rotate'          => 0, //旋转角度 （0~360）
			'fill'            => 0, //是否铺满图片（0，1）
			'opacity'         => 100,
			'position'        => 'se',
			'percent'         => 30,
			'horizonPadding'  => 0, //水平内边距
			'verticalPadding' => 0, //垂直内边距
		], $cfg);

		if(!self::FONT_TYPE_MAP[$config['font']]){
			throw new Exception("Text type no support:".$config['font']);
		}
		//原图
		$image_src = self::removeWatermark($image_src);
		return self::addImageProcessor($image_src, self::PROCESSOR_WATERMARK, [
			'text'   => base64_encode($text),
			'type'   => base64_encode($config['font']),
			'shadow' => $config['shadow'],
			'rotate' => $config['rotate'],
			'fill'   => $config['fill'],
			'size'   => $config['size'],
			'color'  => str_replace('#', '', $config['color']),

			't'     => $config['opacity'],
			'g'     => $config['position'],
			'x'     => $config['horizonPadding'],
			'y'     => $config['verticalPadding'],
		]);
	}
}
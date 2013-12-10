<?php
class HtmlExt {
	public static function showJsonp($msg, $type, $data, $callbackName='_Callback'){

	}

	/**
	 * show iframe message
	 * @param string $msg
	 * @param integer $type
	 * @param string $succ_callback 成功回调js函数
	 * @param string $fail_callback 失败回调js函数
	 * @return string
	 */
	public static function showIframeMsg($msg="''", $code='err', $data=null){
		$data = $data ? json_encode($data) : 'null';

		$html =
		'<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
            <title>response</title>
        </head>
        <body>
            <h1>'.$msg.'</h1>
            <script type="text/javascript">
                frameElement.callback("'.$msg.'","'.$code.'",'.$data.');
            </script>
        </body>
        </html>';
		echo($html);
	}

	/**
	 * json 格式输出消息
	 * @param string $message
	 * @param bool $type
	 * @param mix $data
	 * @return string
	 */
	public static function showStandJson($message, $type=false, $data=null){
		$result = array(
				'm'=>$message,
				't' => $type ? 'success' : 'fail',
				'd' => $data
		);
		echo(json_encode($result));
	}

	/**
	 * 输出js
	 */
	public static function showEvalJs($jscode){
		$html =
		'<!DOCTYPE html">
    	<html>
    	<head>
    		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    		<title></title>
    	</head>
    	<body>
    		<script type="text/javascript">'.$jscode.'</script>
    	</body>
    	</html>';
		echo($html);
	}
}
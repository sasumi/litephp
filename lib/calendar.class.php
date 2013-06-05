<?php
class Calendar {
	private static $instance;

	public $init_date;

	private $data;
	private $config;

	public function __construct($date = null) {
		$this->init_date = !empty($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

		$this->config = array(
			'week_str' => 'sun,mon,tue,wed,the,fri,sat,wk',
			'month_str' => 'january,february,marth,april,may,june,july,auguest,september,october,november,december',
			'class' => 'calendar',
			'class_pre' => 'cc_',
			'show_week_index' => false
		);
	}

	public static function getSingleton($date = null){
		if(!self::$instance){
			self::$instance = new self($date);
		}

		return self::$instance;
	}

	public function setConfig($config){
		if(empty($config) || !is_array($config)){
			return false;
		}

		$this->config = array_merge($this->config, $config);
		return true;
	}

	public function getDateSerial($date){
		$start_day = date('w',strtotime(date('Y-m-01', strtotime($date))));
		$cur_month_date_num = date('t', strtotime($date));
		$last_month = $this->getLastMonth($date);
		$last_month_date_num = date('t', strtotime($last_month));

		for($i=0; $i<42; $i++){
			//last month
			if($start_day > $i){
				$date_serial[$i] = $last_month_date_num - ($start_day - $i)+1;
			}

			//current month
			else if($start_day <= $i && $i<$cur_month_date_num+$start_day){
				$date_serial[$i] = $i-$start_day+1;
			}

			//next month
			else if($i>=$cur_month_date_num){
				$date_serial[$i] = $i-$cur_month_date_num-$start_day+1;
			}
		}
		return $date_serial;
	}

	/**
	 * 获取上一个月的日期
	 * @param string $date
	 * @return string
	 */
	function getLastMonth($date){
		$date = date('Y-m-d',strtotime($date));
		list($y, $m, $d) = explode('-', $date);

		if($m > 1){
			return $y.'-'.($m-1).'-'.$d;
		} else {
			return ($y-1).'-12-'.$d;
		}
	}

	/**
	 * 获取下一个月的日期
	 * @param string $date
	 * @return string
	 */
	function getNextMonth($date){
		$date = date('Y-m-d',strtotime($date));
		list($y, $m, $d) = explode('-', $date);

		if($m == 12){
			return ($y+1).'-01-'.$d;
		} else {
			return $y.'-'.($m+1).'-'.$d;
		}
	}

	function getWeekIndex($date){
		$date = date('Y-m-d', strtotime($date));
		list($y, $m, $d) = explode('-', $date);

		$cur_month_date_num = date('t', strtotime($date));
		$next_month = $this->getNextMonth($date);
		list($null, $next_m) = explode('-',$next_month);

		for($i=0; $i<6; $i++){
			$d = ($i*7+1);
			if($d > $cur_month_date_num){
				$d = $d - $cur_month_date_num;
				$week_index[] = intval(date('W', strtotime("$y-$next_m-$d")));
			} else {
				$week_index[] = intval(date('W', strtotime("$y-$m-$d")));
			}
		}
		return $week_index;
	}

	function mergeWeekIndex($week_index, $date_serial){
		$week_index = array_reverse($week_index);
		$result = array();

		for($i=0; $i<count($date_serial); $i++){
			if($i == 0){
				$result[] = array_pop($week_index);
				$result[] = $date_serial[0];
			}

			else if($i % 7 == 0){
				$result[] = array_pop($week_index);
				$result[] = $date_serial[$i];
			}

			else {
				$result[] = $date_serial[$i];
			}
		}

		return $result;

		$result[] = array();
		$result_len = count($week_index) + count($date_serial) - 1;
		$result = array_fill(0, $result_len, '');

		$row = count($date_serial) / 7;

		$week_index = array_slice($week_index, 0, $row);

		//fill week index
		foreach($week_index as $key=>$wi){
			$i = intval($wi);
			if(!empty($i))
			$result[$key*8] = intval($wi);
		}

		//fill date serial
		$_date_serial = array_reverse($date_serial);

		foreach($result as $key=>$item){
			if(!empty($item)){
				continue;
			} else {
				$result[$key] = array_pop($_date_serial);
			}
		}
		return $result;
	}

	public function isCurrentMonth(){
		list($y, $m) = array_map('intval', explode('-', $this->init_date));
		if($y == date('Y') && $m == intval(date('m'))){
			return true;
		}
		return false;
	}

	function genHtml(){
		//merge week index
		$dates = $this->mergeWeekIndex($this->getWeekIndex($this->init_date), $this->getDateSerial($this->init_date));

		$weeks = explode(',',$this->config['week_str']);
		$months = explode(',',$this->config['month_str']);
		$cur_month = $months[date('n',strtotime($this->init_date))-1];

		$html = '<table class="'.$this->config['class'].'">';
		$html .= '<caption>'.$cur_month.'</caption>';
		$html .= '<colgroup>' .
					($this->config['show_week_index'] ? '<col class="'.$this->config['class_pre'].'wi"></col>' : '').
					'<col class="'.$this->config['class_pre'].'sun"></col>'.
					'<col class="'.$this->config['class_pre'].'mon"></col>'.
					'<col class="'.$this->config['class_pre'].'tue"></col>'.
					'<col class="'.$this->config['class_pre'].'wed"></col>'.
					'<col class="'.$this->config['class_pre'].'thu"></col>'.
					'<col class="'.$this->config['class_pre'].'fri"></col>'.
					'<col class="'.$this->config['class_pre'].'sat"></col>' .
				'</colgrop>';
		$html .= '<thead><tr>';
		$html .= $this->config['show_week_index'] ? '<th class="'.$this->config['class_pre'].'wi">'.$weeks[7].'</th>' : '';

		for($i=0; $i<7; $i++){
			$html .= '<th>'.$weeks[$i].'</th>';
		}
		$html .= '</tr></thead>';

		//tbody
		$html .= '<tbody>';
		for($i=0; $i<count($dates); $i++){
			if($i==0){
				$html .= "\r\n<tr>";
			} else if($i == count($dates)){
				$html .= "</tr>";
			} else if($i%8 == 0){
				$html .= "</tr>\r\n<tr>";
			}

			$cls = '';
			//prev month style
			if($i%8!=0 && $i<=8 && $dates[$i] > 8){
				$cls = ' class="prev_month"';
				$html .= '<td'.$cls.'><span>'.intval($dates[$i]).'</span></td>';
			}

			//next month style
			else if($i%8!=0 && $i>=24 && $dates[$i] < 15){
				$cls = ' class="next_month"';
				$html .= '<td'.$cls.'><span>'.intval($dates[$i]).'</span></td>';
			}

			//no wi
			else if($i%8 == 0 && !$this->config['show_week_index']){
				continue;
			}

			//wi style
			else if($i%8 == 0 && $this->config['show_week_index']){
				$html .= '<th class="'.$this->config['class_pre'].'wi">'.intval($dates[$i]).'</th>';
			}

			//normal
			else {
				list($y, $m) = array_map('intval',explode('-',$this->init_date));
				$today = $dates[$i] == date('d') && $y == date('Y') && $m == date('m') ? 'today' : null;
				$html .= '<td class="'.$today.'"><span>'.intval($dates[$i]).'</span></td>';
			}
		}
		$html .= '</tbody></table>';

		return $html;
	}

	function getOffsetDate($y=0, $m=0, $d=0, $date=null){
		$date = $date ? $date : $this->init_date;
		$date = date('Y-m-d', strtotime($date));

		list($ori_y, $ori_m, $ori_d) = explode('-', $date);

		$ori_y += $y;
		if(!empty($m)){
			$ori_y += intval(($m+$ori_m-1) / 12);
			$ori_m = abs(($m+$ori_m)) % 12;
		}

		$result_date = "$ori_y-$ori_m-$ori_d";

		if(!empty($d)){
			$result_date = date('Y-m-d', strtotime($result_date) + $d*86400);
		}

		return $result_date;
	}

	/**
	 * 绑定数据到日期上
	 * 数据格式：
	 *
	 * $data = array(
	 * 		'2009-08-23' => '<span class="ev_imp">重要事件</span>',
	 * 		'2008-01-30' => '<span class="ev_holiday">假日事件</span>'
	 * );
	 */
	function bindData($data = array()){
		if(empty($data) || is_array($data)){
			return false;
		}

		$this->data = $data;
	}
}
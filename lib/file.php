<?php
/**
 * 获取模块文件夹列表
 * @param string $dir
 * @return array
**/
function get_file_list($dir) {
    $file_list = array();
    if(false != ($handle = opendir($dir))) {
        $i=0;
        while(false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".."&&!strpos($file,".")) {
                $file_list[$i]=$file;
                $i++;
            }
        }
        closedir ($handle);
    }
    return $file_list;
}

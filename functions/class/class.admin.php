<?php
    error_reporting(E_ALL^E_NOTICE^E_WARNING^E_DEPRECATED);
    // 载入配置文件
    include_once("../config.php");

    class Admin{
        var $config;
        var $database;
        function __construct($config,$database) {
            $this->config = $config;
            $this->database = $database;
            $user1 = $config['user'].md5("imgurl".$config['password']);
            // echo $user1;
            //COOKIES里面的信息
            $user2 = $_COOKIE['user'].$_COOKIE['password'];
            
            //如果两者信息相符，说明已登录
            if($user1 != $user2) {
                echo '权限不足，请先登录！';
                //清理cookie
                setcookie("user", '', time()-3600,"/");
                setcookie("password", '', time()-3600,"/");
                header('Location:login.php');
                exit;
            } 
        }
        //查询图片
        function querypic($type,$page){
            $config = $this->config;
            $database = $this->database;

            //分页计算
            $start = ($page - 1) * 12;
            //$end = $page * 12;

            if(($page == '') || (!isset($page))) {
                $page = 1;
            }
            
            //要查询的条数
            $num = 12;

            //判断类型
            switch ($type) {
                case 'user':
                    // echo 'dsd';
                    $datas = $database->select("imginfo", "*", [
                        "dir" => $config['userdir'],
                        "ORDER" => ["id" => "DESC"],
	                    "LIMIT" => [$start,$num]
                    ]);
                    // var_dump( $database->log() );
                    // exit;
                    return $datas;  
                    break;
                case 'admin':
                    $datas = $database->select("imginfo", "*", [
                        "dir" => $config['admindir'],
                        "ORDER" => ["id" => "DESC"],
                        "LIMIT" => [$start,$num]
                    ]);
                    return $datas;
                    break; 
                case 'dubious':
                    $datas = $database->select("imginfo", "*", [
                        "level" => 3,
                        "ORDER" => ["id" => "DESC"],
                        "LIMIT" => [$start,$num]
                    ]);
                    return $datas;
                    break; 
                default:
                    echo 'dsddsd';
                    break;
            }
        }
        //删除一张图片
        function delete($id){
            $config = $this->config;
            $database = $this->database;
            //先查询数据库获取图片路径
            $path = $database->get("imginfo","path",[
                "id"    =>  $id
            ]);
            
            
            //完整的图片路径
            $imgpath = APP.$path;
            //如果图片删除成功，将再次删除数据库
            
            if(unlink($imgpath)) {
                $del = $database->delete("imginfo", [
                    "AND" => [
                        "id" => $id
                    ]
                ]);
                echo 'ok';
            }
            else{
                echo '删除失败！';
            }
            
        }
        //统计数据
        function data() {
            //获取当前月份
            $themonth = date('Y-m',time());
            //获取当天时间
            $theday = date('Y-m-d',time());
            
            //统计本月上传图片数量
            $month = $this->database->count("imginfo",[
                "date[~]"  =>  $themonth
            ]);
            
            $day = $this->database->count("imginfo",[
                "date"  =>  $theday
            ]);
            
            //统计可疑图片
            $level = $this->database->count("imginfo",[
                "level"  =>  3
            ]);
            
            //返回数据
            $redata = array(
                "month" =>  $month,
                "day"   =>  $day,
                "level" =>  $level
            );
            return $redata;
        }
        //取消图片可疑状态
        function cdubious($id){
            $database = $this->database;
            $database->update("imginfo",[
                "level"     =>  1
            ],[
                "id"        =>  $id
            ]);
            echo 'ok';
        }
        //对某张图片进行压缩，未开发完成
        function compress($id,$tinypng){
            $database = $this->database;
            $config = $this->config;
            if($tinypng['option'] != true){
                $compress['code'] = 0;
                $compress['msg'] = "未开启图片压缩功能！";
            }
            else{
                $getdata = $database->get("imginfo","*",[
                    "id"    =>  $id
                ]);
                
            }
        }
        //查询SM.MS图片
        //查询图片
        function querysm($page){
            $config = $this->config;
            $database = $this->database;

            //分页计算
            $start = ($page - 1) * 12;
            //$end = $page * 12;

            if(($page == '') || (!isset($page))) {
                $page = 1;
            }
            
            //要查询的条数
            $num = 12;

            //判断类型
            $datas = $database->select("sm", "*", [
                "ORDER" => ["id" => "DESC"],
                "LIMIT" => [$start,$num]
            ]);
            return $datas;
        }
        //删除SM.MS图片
        function deletesm($id){
            $config = $this->config;
            $database = $this->database;
            //先查询数据库
            $query = $database->get("sm","*",[
                "id"    =>  $id
            ]);
            $delete = $database->delete("sm", [
                "AND" => [
                    "id" => $id
                ]
            ]);
            //请求接口删除图片
            $curl = curl_init($query['delete']);

            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36");
            curl_setopt($curl, CURLOPT_FAILONERROR, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            #设置超时时间，最小为1s（可选）
            curl_setopt($curl , CURLOPT_TIMEOUT, 2);

            $html = curl_exec($curl);
            curl_close($curl);
            echo 'ok';
        }
    }

    $pic = new Admin($config,$database);
?>
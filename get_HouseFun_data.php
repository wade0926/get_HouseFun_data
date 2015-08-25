<?php
require_once("../db.php");
set_time_limit(0);
mysql_query("SET NAMES 'UTF8'");
date_default_timezone_set('Asia/Taipei');

//設定要執行的步驟
$act = 2;

if($act == 3)
{
	header("Content-disposition: filename=abc123.xls");
	header("Content-type:application/vnd.ms-excel;charset=UTF-8");
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
}
else
{
	?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>get HouseFun</title>
    </head>
    <body>
    <?php	
}

switch($act)
{
	//Step 1：抓list 的detail links
	case 1:
		//設定總頁數
		$total_pages = 887;	
		get_detail_links_from_list($total_pages);						
	break;
	
	//Step 2：至房屋detail 抓broker id
	case 2:
		//設定抓取的限制次數
		$get_limit = 3;
		
		//抓個人詳細資料
		get_broker_id($get_limit);
	break;
	
	case 3:		
		//查詢要畫table 的資料
	    $sql = "SELECT * FROM 591_link_data WHERE 5_name != '' AND 5_del = 0";	
	    $res = mysql_query($sql);		
		?>
		<table>
			<?php			
            while($row = mysql_fetch_array($res))
            {
                ?>                
                <tr>
                	<td><?php echo $row['5_name'];?></td>
                    <td><?php echo $row['5_company'];?></td>
                    <td><?php echo $row['5_cell_phone'];?></td>
                    <td><?php echo $row['5_serve_area'];?></td>
                    <td><?php echo $row['5_mail'];?></td>
                </tr>                
                <?php
            }
            ?>
		</table>
        <?php
		exit;
	break;
}	
?>
</body>
</html>

<?php
//========== 函式區 op ==========
//Step 1：抓list 的detail links
function get_detail_links_from_list($total_pages)
{	
	for($i = 1;$i <= $total_pages;$i++)
	{		
		echo '執行第'.$i.' 頁<br />';		
		
		$target_url = 'http://buy.housefun.com.tw/%E8%B2%B7%E5%B1%8B/%E5%8F%B0%E5%8C%97%E5%B8%82?hd_CityID=0000&hd_Sequence=Sequp&hd_SearchGroup=Group01&hd_PM='.$i.'&hd_Tab=1&hd_SID=0';
		$page_data = file_get_contents($target_url);
		
		//找detail 的link id
		preg_match_all('/<a href=\"\/buy\/house\/(.*?)\" target=\"_blank\" class=\"discount-price ng-item ga_click_trace\"/',$page_data,$link_data);
		
		foreach($link_data[1] as $row)
		{	
			$sql = "SELECT hdl_id FROM housefun_detail_link WHERE hdl_link_id = '".$row."' LIMIT 1";	
			$res = mysql_query($sql);	
			$rows = mysql_num_rows($res);
			
			//設定area id
			$area_id = 0;
			
			if($area_id == 0)
			{
				echo '此區已寫入過了';
				exit;
			}
			
			//如果沒有重覆
			if($rows != 1)
			{
				$sql = "INSERT INTO housefun_detail_link (hdl_link_id,hdl_area_id) VALUES ('".$row."',".$area_id.")";
				mysql_query($sql);
			}			
		}		
	}
}

//Step 2：至房屋detail 抓broker id
function get_broker_id($get_limit)
{
	//查詢housefun_detail_link
	$sql = "SELECT hdl_link_id FROM housefun_detail_link WHERE hdl_del = 0 LIMIT ".$get_limit;	
	$res = mysql_query($sql);
					
	while($row = mysql_fetch_array($res))
	{
		//拿	query-shop-id			
		$target_url = 'http://buy.housefun.com.tw/buy/house/'.$row['hdl_link_id'];		
		$page_data = file_get_contents($target_url);		
		preg_match('/<input id=\"query-shop-id\" type=\"hidden\" value=\"(.*?)\" \/>/',$page_data,$query_shop_id);
		$query_shop_id = $query_shop_id[1];
			
		//用query-shop-id 拿broker id
		$url = 'http://buy.housefun.com.tw/ashx/Buy/New/AgentInfo.ashx?RequestPackage={"Method":"Inquire","Data":{"UserMode":1,"HFID":"123","CaseID":"123","WebAgentID":"'.$query_shop_id.'"}}';
		$res = file_get_contents($url);
		$res = json_decode($res,true);		
		preg_match('/http:\/\/i.housefun.com.tw\/(.*)/',$res['Data']['AGHomePageURL'],$preg_data);
		$broker_id = $preg_data[1];
		
		//查詢該broker id 是否重覆
		$sql = "SELECT hb_id FROM housefun_broker WHERE hb_broker_id = '".$broker_id."' AND hb_del = 0 LIMIT 1";	
		$res = mysql_query($sql);	
		$rows = mysql_num_rows($res);
		
		//如果沒有重覆
		if($rows != 1)
		{
			//用broker id 獲取broker deatil page
			$url = 'http://i.housefun.com.tw/'.$broker_id;			
			$page_data = file_get_contents($url);
			
			//========== 擷取broker 資料 op ==========
			//獲取姓名			
			preg_match('/<span id=\"ctl00_AGFullName\">(.*?)<\/span>/',$page_data,$broker_name);
			$broker_name = $broker_name[1];
			
			
			
			echo $broker_name;exit;
			
			$sql = "INSERT INTO housefun_detail_link (hdl_link_id) VALUES ('".$row."')";
			mysql_query($sql);
			//========== 擷取broker 資料 op ==========			
		}
		
		exit;
		
		
											
		//找姓名
		preg_match("/<span class=\"name\">.*?<h3>(.*?)<\/h3>/s",$page_data,$name);			
		$name = $name[1];			
		
		//找就職公司
		preg_match("/<li>就職公司：<span>(.*?)<\/span> <span>(.*?)<\/span><\/li>/",$page_data,$company);			
		$company = $company[1].' '.$company[2];
		
		//找行動電話
		preg_match("/<li>行動電話：<span id=\"phone-num\" class=\"org\">(.*?)<\/span><\/li>/",$page_data,$cell_phone);
		$cell_phone = $cell_phone[1];
				
		//找服務區域
		preg_match("/<li>服務區域：<span>(.*?)<\/span><\/li>/",$page_data,$serve_area);
		$serve_area = $serve_area[1];
		
		//找E-mail
		preg_match("/<li class=\"long\">E-mail：<span>(.*?)<\/span><\/li>/",$page_data,$mail);			
		$mail = $mail[1];
							
		$sql = 'UPDATE 591_link_data SET 5_name = "'.$name.'",5_company = "'.$company.'",5_cell_phone = "'.$cell_phone.'",5_serve_area = "'.$serve_area.'",5_mail = "'.$mail.'" WHERE 5_id = '.$row['5_id'];		
		mysql_query($sql);				
	}
	
	echo 'done';	
}	

function exec_curl($target_url)
{
	$ch = curl_init($target_url); 
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 	
	$res = curl_exec ($ch);
	curl_close ($ch);
	
	return $res;
}
//========== 函式區 ed ==========
?>
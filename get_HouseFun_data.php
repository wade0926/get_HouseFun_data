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
		$get_limit = 10;
		
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
	if($get_limit != 0)
	{
		$set_limit = 'LIMIT '.$get_limit;
	}
		
	//查詢housefun_detail_link
	$sql = "SELECT hdl_link_id FROM housefun_detail_link WHERE hdl_del = 0 ".$set_limit;		
	$res_detail_link = mysql_query($sql);
						
	while($row = mysql_fetch_array($res_detail_link))
	{
		//try
		$row['hdl_link_id'] = 1703298;
				
		//拿	query-shop-id			
		$target_url = 'http://buy.housefun.com.tw/buy/house/'.$row['hdl_link_id'];		
		$page_data = file_get_contents($target_url);		
		preg_match('/<input id=\"query-shop-id\" type=\"hidden\" value=\"(.*?)\" \/>/',$page_data,$query_shop_id);
		$query_shop_id = $query_shop_id[1];
			
		//用query-shop-id 拿broker id
		$url = 'http://buy.housefun.com.tw/ashx/Buy/New/AgentInfo.ashx?RequestPackage={"Method":"Inquire","Data":{"UserMode":1,"HFID":"123","CaseID":"123","WebAgentID":"'.$query_shop_id.'"}}';
		$res = file_get_contents($url);
		$res = json_decode($res,true);
		
		//
		//preg_match('/potrait\/.*?\/(.*?)\./',$res['Data']['Potrait'],$preg_data);
		//echo $preg_data[1];exit;		
		
		
		
						
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
			//獲取「姓名」
			preg_match('/<span id=\"ctl00_AGFullName\">(.*?)<\/span>/',$page_data,$broker_name);
			$broker_name = $broker_name[1];
			
			//====== 獲取「就職公司」 及 「分店名稱」 op ======
			preg_match('/<span id=\"ctl00_ShopFullShopName\">(.*?)<\/span>/',$page_data,$preg_match_data);
			$preg_match_data = $preg_match_data[1];
			
			//如果有<br />，把它濾掉
			$preg_match_data = str_replace('<br />','',$preg_match_data);			
			$preg_match_data = explode(" ",$preg_match_data);			
			$broker_company = $preg_match_data[0];
			$broker_company_branch = $preg_match_data[1];						
			//====== 獲取「就職公司」 及 「分店名稱」 ed ======
			
			//擷取電話			
			preg_match('/<span id=\"ctl00_AGMobilePhone\">(.*?)<\/span>/',$page_data,$broker_phone);
			$broker_phone = $broker_phone[1];	
			
			//擷取第一個房屋地區						
			$house_ajax_info = get_house_info_ajax($broker_id);
			preg_match('/<li><span style=\"float: left\">(.*?)<\/span><\/li>/',$house_ajax_info,$broker_serve_area);
			$broker_serve_area = substr($broker_serve_area[1],0,18);			
			
			$sql = "INSERT INTO housefun_broker (hb_broker_id,hb_name,hb_company,hb_company_branch,hb_phone,hb_serve_area) VALUES 
			        ('".$broker_id."','".$broker_name."','".$broker_company."','".$broker_company_branch."','".$broker_phone."','".$broker_serve_area."')";
			mysql_query($sql);
			//========== 擷取broker 資料 op ==========			
		}		
	}
	
	echo 'done';	
}	

function get_house_info_ajax($broker_id)
{
	$target_url = 'http://i.housefun.com.tw/Ashx/HouseFunAgent/AGGetHouseList.ashx';			
	$post_data = 'ShopID='.$broker_id.'&order=1&ordertype=up&srhchannel=undefined&srhprice=0&srhpu=0&srhct=0&PM=1';
	
	$ch = curl_init($target_url); 
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);	
	$res = curl_exec ($ch);
	curl_close ($ch);
	
	return $res;
}
//========== 函式區 ed ==========
?>
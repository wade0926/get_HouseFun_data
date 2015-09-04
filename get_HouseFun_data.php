<?php
exit;
require_once("../db.php");
set_time_limit(0);
mysql_query("SET NAMES 'UTF8'");
date_default_timezone_set('Asia/Taipei');

//設定要執行的步驟
$act = 3;

//========== 設定area id、area op ==========
//$area_name = '台北市';$area_id = '0000';			
//$area_name = '新北市';$area_id = '0001';
//$area_name = '基隆市';$area_id = '0002';
//$area_name = '連江縣';$area_id = '0003';

$arr_set[0]['area_name'] = '宜蘭縣';
$arr_set[0]['area_id'] = '0004';
$arr_set[0]['total_pages'] = 338;

$arr_set[1]['area_name'] = '新竹市';
$arr_set[1]['area_id'] = '0006';
$arr_set[1]['total_pages'] = 495;

$arr_set[2]['area_name'] = '新竹縣';
$arr_set[2]['area_id'] = '0007';
$arr_set[2]['total_pages'] = 511;

$arr_set[3]['area_name'] = '桃園市';
$arr_set[3]['area_id'] = '0008';
$arr_set[3]['total_pages'] = 1661;

$arr_set[4]['area_name'] = '苗栗縣';
$arr_set[4]['area_id'] = '0009';
$arr_set[4]['total_pages'] = 158;

$arr_set[5]['area_name'] = '台中市';
$arr_set[5]['area_id'] = '0010';
$arr_set[5]['total_pages'] = 2843;

$arr_set[6]['area_name'] = '彰化縣';
$arr_set[6]['area_id'] = '0012';
$arr_set[6]['total_pages'] = 281;

$arr_set[7]['area_name'] = '南投縣';
$arr_set[7]['area_id'] = '0013';
$arr_set[7]['total_pages'] = 175;

$arr_set[8]['area_name'] = '嘉義市';
$arr_set[8]['area_id'] = '0014';
$arr_set[8]['total_pages'] = 204;

$arr_set[9]['area_name'] = '嘉義縣';
$arr_set[9]['area_id'] = '0015';
$arr_set[9]['total_pages'] = 170;

$arr_set[10]['area_name'] = '雲林縣';
$arr_set[10]['area_id'] = '0016';
$arr_set[10]['total_pages'] = 105;

$arr_set[11]['area_name'] = '台南市';
$arr_set[11]['area_id'] = '0017';
$arr_set[11]['total_pages'] = 681;

$arr_set[12]['area_name'] = '高雄市';
$arr_set[12]['area_id'] = '0019';
$arr_set[12]['total_pages'] = 2409;

$arr_set[13]['area_name'] = '澎湖縣';
$arr_set[13]['area_id'] = '0022';
$arr_set[13]['total_pages'] = 18;

$arr_set[14]['area_name'] = '金門縣';
$arr_set[14]['area_id'] = '0023';
$arr_set[14]['total_pages'] = 5;

$arr_set[14]['area_name'] = '屏東縣';
$arr_set[14]['area_id'] = '0024';
$arr_set[14]['total_pages'] = 168;

$arr_set[15]['area_name'] = '台東縣';
$arr_set[15]['area_id'] = '0025';
$arr_set[15]['total_pages'] = 118;

$arr_set[16]['area_name'] = '花蓮縣';
$arr_set[16]['area_id'] = '0026';
$arr_set[16]['total_pages'] = 210;
//========== 設定area id、area op ==========

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
		foreach($arr_set as $row)
		{
			get_detail_links_from_list($row['total_pages'],$row['area_name'],$row['area_id']);						
		}		
	break;
	
	//Step 2：至房屋detail 抓broker id
	case 2:
		$set_limit = 0;
		
		//抓個人詳細資料
		foreach($arr_set as $row)
		{
			get_broker_id($row['area_id'],$set_limit);
		}		
	break;
	
	case 3:		
		//查詢要畫table 的資料
	    $sql = "SELECT * FROM housefun_broker WHERE hb_broker_id != '' AND hb_name != '' AND hb_del = 0";	
	    $res = mysql_query($sql);		
		?>      
        
		<table>
			<?php			
            while($row = mysql_fetch_array($res))
            {
                ?>                
                <tr>
                	<td><?php echo $row['hb_name'];?></td>
                    <td><?php echo $row['hb_company'];?></td>
                    <td><?php echo $row['hb_company_branch'];?></td>
                    <td><?php echo '&nbsp;'.$row['hb_phone'];?></td>
                    <td><?php echo $row['hb_serve_area'];?></td>                    
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
function get_detail_links_from_list($total_pages,$area_name,$area_id)
{	
	for($i = 1;$i <= $total_pages;$i++)
	{				
		echo '執行第'.$i.' 頁<br />';		
		$target_url = 'http://buy.housefun.com.tw/%E8%B2%B7%E5%B1%8B/'.urlencode($area_name).'?hd_CityID='.$area_id.'&hd_Sequence=Sequp&hd_SearchGroup=Group01&hd_PM='.$i.'&hd_Tab=1';				
		$page_data = file_get_contents($target_url);		
				
		//找detail 的link id
		preg_match_all('/<a href=\"\/buy\/house\/(.*?)\" target=\"_blank\" class=\"discount-price ng-item ga_click_trace\"/',$page_data,$link_data);		
		
		foreach($link_data[1] as $row)
		{			
			$sql = "SELECT hdl_id FROM housefun_detail_link WHERE hdl_link_id = '".$row."' LIMIT 1";	
			$res = mysql_query($sql);	
			$rows = mysql_num_rows($res);			
			
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
function get_broker_id($area_id,$set_limit)
{	
	if($set_limit == 0 || $set_limit == '')
	{
		$set_limit = '';
	}
	else
	{
		$set_limit = ' LIMIT '.$set_limit;
	}
			
	//查詢housefun_detail_link
	$sql = "SELECT hdl_id,hdl_link_id FROM housefun_detail_link WHERE hdl_del = 0 AND hdl_area_id =".(int)$area_id.$set_limit;
	$res_detail_link = mysql_query($sql);
						
	while($row = mysql_fetch_array($res_detail_link))
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
			
			//如果多個空格			
			if($preg_match_data[1] == '')
			{				
				$broker_company_branch = $preg_match_data[2];
			}
			else
			{
				$broker_company_branch = $preg_match_data[1];
			}			
			//====== 獲取「就職公司」 及 「分店名稱」 ed ======
			
			//擷取電話			
			preg_match('/<span id=\"ctl00_AGMobilePhone\">(.*?)<\/span>/',$page_data,$broker_phone);
			$broker_phone = $broker_phone[1];
			
			//====== 擷取第一個房屋地區 op ======
			//找ajax 用的broker id(因為有時候會跟原本的broker id 不一樣)
			preg_match('/<input type=\"hidden\" name=\"ctl00\$ContentPlaceHolder1\$ShopID\" id=\"ShopID\" value=\"(.*?)\" \/>/',$page_data,$for_ajax_broker_id);
			$for_ajax_broker_id = $for_ajax_broker_id[1];			
								
			$house_ajax_info = get_house_info_ajax($for_ajax_broker_id);
			preg_match('/<li><span style=\"float: left\">(.*?)<\/span><\/li>/',$house_ajax_info,$broker_serve_area);
			$broker_serve_area = substr($broker_serve_area[1],0,18);			
			//====== 擷取第一個房屋地區 ed ======
			
			$sql = "INSERT INTO housefun_broker (hb_broker_id,hb_name,hb_company,hb_company_branch,hb_phone,hb_serve_area,now_number,hb_area_id) VALUES 
			        ('".$broker_id."','".$broker_name."','".$broker_company."','".$broker_company_branch."','".$broker_phone."','".$broker_serve_area."','".$row['hdl_id']."','".$area_id."')";					
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
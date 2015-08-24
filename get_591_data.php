<?php
require_once("../db.php");
set_time_limit(0);
mysql_query("SET NAMES 'UTF8'");
date_default_timezone_set('Asia/Taipei');

//設定要執行的步驟
$act = 1;

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
    <title>get 591</title>
    </head>
    <body>
    <?php	
}

switch($act)
{
	//Step 1： 抓591 id
	case 1:		
		$url = 'http://buy.housefun.com.tw/%E8%B2%B7%E5%B1%8B/%E5%8F%B0%E5%8C%97%E5%B8%82?hd_CityID=0000&hd_Sequence=Sequp&hd_SearchGroup=Group01&hd_PM=1&hd_Tab=1&hd_SID=0';
		$res = file_get_contents($url);
		echo $res;
		
		exit;
			
		$query_shop_id = 'Z001Z000AG00011657';
		//$query_shop_id = 'Z001A000BGA031989';
		
		$url = 'http://buy.housefun.com.tw/ashx/Buy/New/AgentInfo.ashx?RequestPackage={"Method":"Inquire","Data":{"UserMode":1,"HFID":"123","CaseID":"123","WebAgentID":"'.$query_shop_id.'"}}';
		//$url = 'http://buy.housefun.com.tw/';
		$res = file_get_contents($url);
		$res = json_decode($res,true);
		
		echo $res['Data']['AGHomePageURL'];
		exit;
		
		echo '<pre>';
		print_r($res);
		exit;
			
		//get_591_id($para_totalRows = 15903);		
	break;
	
	//Step 2： 抓個人詳細資料
	case 2:
		//設定撈取的限制次數
		$get_limit = 200;
		
		//抓個人詳細資料
		get_detail($get_limit);
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
//Step 1：抓591 id
function get_591_id($para_totalRows)
{
	//總頁數
	$total_pages = ceil($para_totalRows / 15);
	
	$j = 1;
	
	for($i = 1;$i <= $total_pages;$i++)
	{
		$para_firstRow = ($i - 1) * 15;
		echo '執行第'.$i.' 頁<br />';
		
		//======== op 
		$target_url = 'http://www.591.com.tw/index.php?firstRow='.$para_firstRow.'&totalRows='.$para_totalRows.'&?&m=0&o=12&module=shop&action=list';				
		$page_data = exec_curl($target_url);
				
		//或用file_get_contents 即可			
		//$page_data = file_get_contents($target_url);
								
		//找出該頁每個人的id
		preg_match_all("/<li link=\"(.*?)\" class/",$page_data,$link_data);		
										
		foreach($link_data[1] as $row)
		{	
			$sql = "SELECT 5_id FROM 591_link_data WHERE 5_name_id = '".$row."' LIMIT 1";	
			$res = mysql_query($sql);	
			$rows = mysql_num_rows($res);
			
			//如果沒有重覆
			if($rows != 1)
			{
				$sql = "INSERT INTO 591_link_data (5_name_id) VALUES ('".$row."')";
				mysql_query($sql);
			}
			else
			{
				//echo $j.'. page：'.$i.' 的'.$row.' 重覆了<br />';
				$j++;
			}
		}	
	}
}

//Step 2：抓個人詳細資料
function get_detail($get_limit)
{
	//查詢「姓名」為空的
	$sql = "SELECT 5_id,5_name_id,5_name FROM 591_link_data WHERE 5_name = '' AND 5_del = 0 LIMIT ".$get_limit;	
	$res = mysql_query($sql);
					
	while($row = mysql_fetch_array($res))
	{
		if($row['5_name'] == '')
		{					
			$target_url = 'http://www.591.com.tw/'.$row['5_name_id'];		
			$page_data = exec_curl($target_url);
												
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
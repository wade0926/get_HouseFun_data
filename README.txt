1. link：
   http://buy.housefun.com.tw/

2. 其它：
 (1) 靠firefox 的找javascript 中的 showlist 及搭配'tamper data'才找到 ajax 規則，如：
     $post_data = 'ShopID=Z001A000BGU025540&order=1&ordertype=up&srhchannel=undefined&srhprice=0&srhpu=0&srhct=0&PM=1';
 (2) 取得ajax 的此頁request方法(用tamper data找到的，http://buy.housefun.com.tw/buy/house/1572045)：
   A. 原始：
      http://buy.housefun.com.tw/ashx/Buy/New/AgentInfo.ashx?RequestPackage={"Method":"Inquire","Data":{"UserMode":1,"HFID":"1572045","CaseID":"e18f244b-42ee-4125-b967-a607e0a241cd","WebAgentID":"Z001A000BGA031989","CasePosterAgentID":""}}
   B. final：
      http://buy.housefun.com.tw/ashx/Buy/New/AgentInfo.ashx?RequestPackage={"Method":"Inquire","Data":{"UserMode":1,"HFID":"123","CaseID":"123","WebAgentID":"Z001A000BGA031989"}}
      如該頁的：
      <input id="query-shop-id" type="hidden" value="Z001A000BGA031989" />
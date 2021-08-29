<?php

//Sessionスタート
session_start();

//関数を呼び出す
require_once('funcs.php');

//ログインチェック
loginCheck();
$user_name = $_SESSION['name'];
$id = $_SESSION['id'];
$plan = $_SESSION['plan'];


//電力メニューと契約アンペアを取得


//以降はログインユーザーのみ

//DB接続（電力量データ）
$pdo = db_conn(); 

//IDと一致するテーブル名を作成する
$table_name = "id".$id;

//今月の使用量合計
$this_month = date('Y-m-d 00:00:00', strtotime('first day of this month'));
$today = date('Y-m-d H:i:s', strtotime('now'));
$stmt2 =$pdo->prepare 
("SELECT SUM(wh/1000) as wh FROM $table_name WHERE plot_date_time BETWEEN '$this_month' AND '$today'");
$status = $stmt2->execute();
if($row2 = $stmt2 -> fetch()){
    $wh_this_month = $row2['wh'];
    }
    echo $wh_this_month;
    exit();
//基本料金取得
$stmt11 =$pdo->prepare 
("SELECT fixed FROM tepco_standard WHERE plot_date_time = '00:00:00'");
$status = $stmt11->execute();
if($row11 = $stmt11 -> fetch()){
    $fixed = $row11['fixed'];
    }
 
//従量料金単価（１段目）取得
$stmt12 =$pdo->prepare 
("SELECT var_s1 FROM tepco_standard WHERE plot_date_time = '00:00:00'");
$status = $stmt12->execute();
if($row12 = $stmt12 -> fetch()){
    $var_s1 = $row12['var_s1'];
    }

//従量料金単価（2段目）取得
$stmt13 =$pdo->prepare 
(" SELECT var_s2 FROM tepco_standard WHERE plot_date_time = '00:00:00' ");
$status = $stmt13->execute();
if($row13 = $stmt13 -> fetch()){
    $var_s2 = $row13['var_s2'];
    }

 //従量料金単価（3段目）取得
$stmt14 =$pdo->prepare 
(" SELECT var_s3 FROM tepco_standard WHERE plot_date_time = '00:00:00' ");
$status = $stmt14->execute();
if($row14 = $stmt14 -> fetch()){
    $var_s3 = $row14['var_s3'];
    }

//今月の電気代取得
echo $wh_this_month;
exit();
let $this_month_bill = $fixed + $wh_this_month * $var_s1;
echo $this_month_bill;
exit();

if ($wh_this_month < 120) {
    $this_month_bill = $fixed + $wh_this_month * $var_s1
} else if ($wh_this_month < 300){
    $this_month_bill = $fixed + 120 * $var_s1 + ($wh_this_month-120) * $var_s2
} else {
    $this_month_bill = $fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_this_month-300) * $var_s3
};
 echo $this_month_bill;
 exit();

 //今月の電気料金（時間帯別単価の場合）
$stmt9 =$pdo->prepare 
("SELECT 
   sum(tepco_night8.var_s1 * $table_name.wh/(2*1000)) AS bill
FROM
	$table_name
LEFT JOIN 
    tepco_night8
ON 
    DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_night8.plot_date_time, '%H:%i:%s')
WHERE
DATE_FORMAT($table_name.plot_date_time, '%Y-%m-%d %H:%i:%s')

BETWEEN '$this_month' AND '$today'");

$status = $stmt9->execute();
if($row9 = $stmt9 -> fetch()){
    $bill_this_month = $row9['bill'];
    }

?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <title>でんき料金サマリー</title>
</head>
    <body>
    <h2>最近のでんきの使い方は？</h2>
    
    <!-- <table class="result">
        <tr>
        <th>時間</th>
        <th>基本料金</th>
        <th>従量料金1段目</th>
        </tr>
        <?= $view ?>
    </table> -->
    
    <canvas id="chart" height="100" width="200"></canvas>
    
    <p><span id="today"></span>までの電気料金： <?= $bill_this_month ?></p>
    <!-- <p>先月の電気料金： <?= $bill_last_month ?></p> -->
    
    <p class="return"><a href="index.php">トップに戻る</a></p>
    
    
    
    <!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- JQuery -->
    
    <script>
    
    //年月表示の整理
    let today = new Date();
    let year = today.getFullYear();
    let month =today.getMonth()+1;
    let date = today.getDate();
    let latest_day = '<p>'+year+'/'+month+'/'+date+'</p>'; 
    $("#today").html(latest_day); 
    
    let this_month = '<?= $this_year ?>'+'/'+'<?= $this_month ?>'
    let one_month_before = '<?= $this_year ?>'+'/'+'<?= $one_month_before ?>'
    let two_month_before = '<?= $this_year ?>'+'/'+'<?= $two_month_before ?>'
    let three_month_before = '<?= $this_year ?>'+'/'+'<?= $three_month_before ?>'
    
    //Chart.jsで棒グラフを描く
    jQuery (function ()
    {const config = {
            type: 'bar',
            data: barChartData,
            responsive : true
            }
    
        const context = jQuery("#chart")
        const chart = new Chart(context,config)
    })
    
    const barChartData = {
        labels : [three_month_before, two_month_before, one_month_before, this_month],
        datasets : [
            {
            label: "電気使用量(kWh)",
            backgroundColor: "rgba(60,179,113,0.5)",
            data : [<?= $wh_three_month_before ?>,<?= $wh_two_month_before ?>,<?= $wh_last_month ?>,<?= $wh_this_month ?>]
            },   
        ]
    }
    
    </script>
    
    </body>
    </html>
<?php
//Sessionスタート
session_start();

//関数を呼び出す
require_once('funcs.php');

//ログインチェック、電力メニューと契約アンペアを取得
// loginCheck();
$user_name = $_SESSION['name'];
$id = $_SESSION['id'];
$plan = $_SESSION['plan'];
$ampere = $_SESSION['ampere'];
$polling = $_SESSION['polling'];

//以降はログインユーザーのみ

//検索条件取得
$year =$_POST["year"];
$month = $_POST["month"];
// $date = $_POST["date"];

//DB接続（電力量データ）
$pdo = db_conn(); 

//IDと一致するテーブル名を作成する
$table_name = "id".$id;

//検索に用いる日時（月初と月末）を定義する
$input_month = $year.'-'.$month.'-'.'1';
$search_month = date('Y-m-d 00:00:00', strtotime($input_month));
$end_month = date('Y-m-d 23:59:59', strtotime('last day of '. $input_month.'23:59:59'));

//ログインした人のデータが登録されているテーブルから検索条件に当てはまるものを探す

//基本料金取得
$stmt11 = $pdo->prepare 
("SELECT fixed FROM $plan WHERE plot_date_time = '00:00:00'");
$status = $stmt11->execute();
if($row11 = $stmt11 -> fetch()){
    $fixed = $row11['fixed'] * ($ampere/10);
    }

// 従量料金単価（１段目）取得
$stmt12 =$pdo->prepare 
("SELECT var_s1 FROM $plan WHERE plot_date_time = '00:00:00'");
$status = $stmt12->execute();
if($row12 = $stmt12 -> fetch()){
    $var_s1 = $row12['var_s1'];
    }

//従量料金単価（2段目）取得
$stmt13 =$pdo->prepare 
("SELECT var_s2 FROM $plan WHERE plot_date_time = '00:00:00' ");
$status = $stmt13->execute();
if($row13 = $stmt13 -> fetch()){
    $var_s2 = $row13['var_s2'];
    }

 //従量料金単価（3段目）取得
$stmt14 =$pdo->prepare 
("SELECT var_s3 FROM $plan WHERE plot_date_time = '00:00:00' ");
$status = $stmt14->execute();
if($row14 = $stmt14 -> fetch()){
    $var_s3 = $row14['var_s3'];
    }


//指定した月の日別の合計値の取得（1分値と30分値で計算式を分ける）
$daily_sum = array();
if($polling == "1min"){
    $stmt = $pdo->prepare ("SELECT DATE_FORMAT(plot_date_time, '%Y-%m-%d') AS plot_date_time, SUM(wh/(1000*60)) AS daily_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$search_month' AND '$end_month' GROUP BY DATE_FORMAT(plot_date_time, '%Y-%m-%d') ");  
} else if ($polling == "30min"){
        $stmt = $pdo->prepare ("SELECT DATE_FORMAT(plot_date_time, '%Y-%m-%d') AS plot_date_time, SUM(wh/1000) AS daily_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$search_month' AND '$end_month' GROUP BY DATE_FORMAT(plot_date_time, '%Y-%m-%d') ");
} else {
        exit ("データ粒度を登録してください");
    }

$status = $stmt->execute();
if ($status == false) {
    sql_error($status);
} else {
    $daily_sum = $stmt->fetchAll();
    }

//配列をJSON形式に変更    
$json_array = json_encode($daily_sum);

//指定した月毎の合計
if($polling == "1min")
    {$stmt1 =$pdo->prepare ("SELECT SUM(wh/1000/60) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$search_month' AND '$end_month' ");
} else {
    $stmt1 =$pdo->prepare ("SELECT SUM(wh/1000) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$search_month' AND '$end_month' ");
}
$status = $stmt1->execute();
if($row1 = $stmt1 -> fetch()){
    $wh_selected_month = $row1['wh'];
    }

$wh_selected_month_r = round ($wh_selected_month);

//時間帯別料金との分岐点
if ($plan == "tepco_night8"){
    //指定した月の電気料金（時間帯別単価の場合）
$stmt9 =$pdo->prepare 
("SELECT 
   sum(tepco_night8.var_s1 * $table_name.wh/1000/60) AS bill
FROM
	$table_name
LEFT JOIN 
    tepco_night8
ON 
    DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_night8.plot_date_time, '%H:%i:%s')
WHERE wh>0 AND
DATE_FORMAT($table_name.plot_date_time, '%Y-%m-%d %H:%i:%s')

BETWEEN '$search_month' AND '$end_month'");

$status = $stmt9->execute();
if($row9 = $stmt9 -> fetch()){
    $selected_month_bill = round($row9['bill']+ $fixed);
    }
} else {

// 指定した月の電気代取得
//東京ガスは従量バーが違う
    if($plan =="tokyogas"){
            if ($wh_selected_month < 140) {
                $selected_month_bill = round($fixed + $wh_selected_month * $var_s1);
            } else if ($wh_selected_month < 350){
                $selected_month_bill = round($fixed + 140 * $var_s1 + ($wh_selected_month-140) * $var_s2);
            } else {
                $selected_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_selected_month-350) * $var_s3);
            }
        } else {
            if ($wh_selected_month < 120) {
                $selected_month_bill = round($fixed + $wh_selected_month * $var_s1);
            } else if ($wh_selected_month < 300){
                $selected_month_bill = round($fixed + 120 * $var_s1 + ($wh_selected_month-120) * $var_s2);
            } else {
                $selected_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_selected_month-300) * $var_s3);
            }
        }
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
<main>
<header>
    <nav class="header-wrapper">
        <ul class="inner">
            <li><a href="index.php">トップに戻る</a></li>
            <li><a href="logout.php">ログアウト</a></li>
            <li>こんにちは、<?= $user_name ?>さん</a></li>
        </ul>
    </nav>
    <p class="return"></p>
</header>

<body>
<div class ="select-title"><?= $year?>年<?= $month?>月のでんきの使い方は？</div>

<div class="tables">
    <p class ="month1-before">
    <?= $year?>年<?= $month?>月の電気料金：<?= $selected_month_bill ?> 円
    <br>
    <?= $year?>年<?= $month?>月の電気使用量：<?= $wh_selected_month_r ?> kWh
    </p>

</div>
<div class="chart-center">
    <canvas id="chart" height="100" width="200" style="display: block; margin: auto;"></canvas>
</div>
</main>   

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
    
let this_month = year+'/'+ month;
let one_month_before = year+'/'+ (month - 1);
let two_month_before = year+'/'+ (month - 2);
let three_month_before = year+'/'+ (month - 3);
    
let js_array = <?php echo $json_array; ?>;
console.log(js_array);

date_array = [];
kwh_array = [];
for(key in js_array){
 date_array.push(js_array[key][0]);
kwh_array.push(js_array[key][1]);
}
console.log(date_array);
console.log(kwh_array);
 

//Chart.jsで棒グラフを描く
jQuery (function ()
{const config = {
        type: 'line',
        data: barChartData,
        responsive : true
        }

    const context = jQuery("#chart")
    const chart = new Chart(context,config)
})

const barChartData = {
    labels : date_array,
    datasets : [
        {
        label: "日別電気使用量(kWh)",
        backgroundColor: "rgba(60,179,113,0.5)",
        data : kwh_array
        },   
    ]
}

</script>

</body>
</html>
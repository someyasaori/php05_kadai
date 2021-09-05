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

//DB接続（電力量データ）
$pdo = db_conn(); 

//IDと一致するテーブル名を作成する
$table_name = "id".$id;

//今月の今日時点までの定義
$this_month = date('Y-m-d 00:00:00', strtotime('first day of this month'));
$today = date('Y-m-d H:i:s', strtotime('now'));

//先月の定義
$one_month_before = date('Y-m-d H:i:s', strtotime(date('Y-m-1') . '-1 month'));
$end_month_one = date('Y-m-d 23:59:59', strtotime('last day of '. $one_month_before));

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

//今月の使用量合計（1分値と30分値で計算式を分ける）
if($polling == '1min'){
    $stmt2 =$pdo->prepare 
    ("SELECT SUM(wh/1000/60) as monthly_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$this_month' AND '$today'");
    } else if ($polling == '30min'){
        $stmt2 =$pdo->prepare 
    ("SELECT SUM(wh/1000) as monthly_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$this_month' AND '$today'");
    } else {
        exit ("データ粒度を登録してください");
    }
    
    $status = $stmt2->execute();
    if($row2 = $stmt2 -> fetch()){
        $wh_this_month = $row2['monthly_wh'];
        }
    $wh_this_month_r = round($wh_this_month);

//先月の使用量合計
if($polling == "1min"){
    $stmt3 =$pdo->prepare ("SELECT SUM(wh/1000/60) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$one_month_before' AND '$end_month_one' ");
} else {
    $stmt3 =$pdo->prepare ("SELECT SUM(wh/1000) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$one_month_before' AND '$end_month_one' ");
}
$status = $stmt3->execute();

if($row3 = $stmt3 -> fetch()){
    $wh_last_month = $row3['wh'];
    }

$wh_last_month_r = round($wh_last_month);

//2か月前の使用量合計
// $two_month_before= $this_this_month - 2;
// $month_two  = $this_year.'-'.$two_month_before.'-'.'1';
// $end_month_two = date('Y-m-d H:i:s', strtotime('last day of '. $month_two.'23:59:59'));

$two_month_before = date('Y-m-d H:i:s', strtotime(date('Y-m-1') . '-2 month'));
$end_month_two = date('Y-m-d 23:59:59', strtotime('last day of '. $two_month_before));
if($polling == "1min"){
$stmt4 =$pdo->prepare ("SELECT SUM(wh/1000/60) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$two_month_before' AND '$end_month_two' ");
    } else {
        $stmt4 =$pdo->prepare("SELECT SUM(wh/1000) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$two_month_before' AND '$end_month_two' ");
}

$status = $stmt4->execute();
if($row4 = $stmt4 -> fetch()){
    $wh_two_month_before = round($row4['wh']);
    }

//3か月前の使用量合計
// $this_month = date('m', strtotime('this month')) ;
// $this_year =  date('Y', strtotime('this month')) ;
// $three_month_before= $this_this_month - 3;
// $month_three  = $this_year.'-'.$three_month_before.'-'.'1';
// $end_month_three = date('Y-m-d H:i:s', strtotime('last day of '. $month_three.'23:59:59'));

$three_month_before = date('Y-m-d H:i:s', strtotime(date('Y-m-1') . '-3 month'));
$end_month_three = date('Y-m-d 23:59:59', strtotime('last day of '. $three_month_before));
if($polling == "1min"){
$stmt5 =$pdo->prepare ("SELECT SUM(wh/1000/60) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$three_month_before' AND '$end_month_three' ");
} else {
$stmt5 =$pdo->prepare ("SELECT SUM(wh/1000) as wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$three_month_before' AND '$end_month_three' ");
}
$status = $stmt5->execute();

if($row5 = $stmt5 -> fetch()){
    $wh_three_month_before = round($row5['wh']);
    }

//時間帯別料金との分岐点（従量料金のバーが違うメニュー未反映！！）

if ($plan == "tepco_night8"){

    //今月の電気料金（時間帯別単価の場合）
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

    BETWEEN '$this_month' AND '$today'");

    $status = $stmt9->execute();
    if($row9 = $stmt9 -> fetch()){
        $bill_this_month = round($row9['bill']+ $fixed);
        }

    //先月の電気料金（時間帯別単価の場合）
    $stmt10 =$pdo->prepare 
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
    BETWEEN
    '$one_month_before' AND '$end_month_one'");

    $status = $stmt10->execute();

    if($row10 = $stmt10 -> fetch()){
        $last_month_bill = round($row10['bill'] + $fixed);
        }

    //2か月前の電気料金（時間帯別単価の場合）
    $stmt10 =$pdo->prepare 
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
    BETWEEN
    '$two_month_before' AND '$end_month_two'");

    $status = $stmt10->execute();

    if($row10 = $stmt10 -> fetch()){
        $bill_two_month_before = round($row10['bill'] + $fixed);
        }

    //3か月月の電気料金（時間帯別単価の場合）
    $stmt10 =$pdo->prepare 
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
    BETWEEN
    '$three_month_before' AND '$end_month_three'");

    $status = $stmt10->execute();

    if($row10 = $stmt10 -> fetch()){
        $bill_three_month_before = round($row10['bill']+ $fixed);
        }

} else {
    //東京ガスは従量のバーが異なる   
        // 今月の電気代取得
    if($plan == "tokyogas"){
        if ($wh_this_month < 140) {
            $this_month_bill = round($fixed + $wh_this_month * $var_s1);
        } else if ($wh_this_month < 350){
            $this_month_bill = round($fixed + 140 * $var_s1 + ($wh_this_month-140) * $var_s2);
        } else {
            $this_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_this_month-350) * $var_s3);
        };

        // 先月の電気代取得
        if ($wh_last_month < 140) {
            $last_month_bill = round($fixed + $wh_last_month * $var_s1);
        } else if ($wh_last_month < 350){
            $last_month_bill = round($fixed + 140 * $var_s1 + ($wh_last_month-140) * $var_s2);
        } else {
            $last_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_last_month-350) * $var_s3);
        }

        // 2か月前の電気代取得
        if ($wh_two_month_before < 140) {
            $bill_two_month_before = round($fixed + $wh_two_month_before * $var_s1);
        } else if ($wh_two_month_before < 350){
            $bill_two_month_before = round($fixed + 140 * $var_s1 + ($wh_two_month_before-140) * $var_s2);
        } else {
            $bill_two_month_before = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_two_month_before-350) * $var_s3);
        }    

        // 3か月前の電気代取得
        if ($wh_three_month_before < 140) {
            $bill_three_month_before = round($fixed + $wh_three_month_before * $var_s1);
        } else if ($wh_three_month_before < 350){
            $bill_three_month_before = round($fixed + 140 * $var_s1 + ($wh_three_month_before-140) * $var_s2);
        } else {
            $bill_three_month_before = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_three_month_before-350) * $var_s3);
        }    
    
    } else {
        if ($wh_this_month < 120) {
            $this_month_bill = round($fixed + $wh_this_month * $var_s1);
        } else if ($wh_this_month < 300){
            $this_month_bill = round($fixed + 120 * $var_s1 + ($wh_this_month-120) * $var_s2);
        } else {
            $this_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_this_month-300) * $var_s3);
        };

        // 先月の電気代取得
        if ($wh_last_month < 120) {
            $last_month_bill = round($fixed + $wh_last_month * $var_s1);
        } else if ($wh_last_month < 300){
            $last_month_bill = round($fixed + 120 * $var_s1 + ($wh_last_month-120) * $var_s2);
        } else {
            $last_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_last_month-300) * $var_s3);
        }

        // 2か月前の電気代取得
        if ($wh_two_month_before < 120) {
            $bill_two_month_before = round($fixed + $wh_two_month_before * $var_s1);
        } else if ($wh_two_month_before < 300){
            $bill_two_month_before = round($fixed + 120 * $var_s1 + ($wh_two_month_before-120) * $var_s2);
        } else {
            $bill_two_month_before = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_two_month_before-300) * $var_s3);
        }    

        // 3か月前の電気代取得
        if ($wh_three_month_before < 120) {
            $bill_three_month_before = round($fixed + $wh_three_month_before * $var_s1);
        } else if ($wh_three_month_before < 300){
            $bill_three_month_before = round($fixed + 120 * $var_s1 + ($wh_three_month_before-120) * $var_s2);
        } else {
            $bill_three_month_before = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_three_month_before-300) * $var_s3);
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
<h2>最近のでんきの使い方は？</h2>

<canvas id="chart" height="100" width="200"></canvas>

<p><span id="today"></span>までの電気料金： <?= $this_month_bill ?>円</p>
<p>先月の電気料金： <?= $last_month_bill ?>円</p>
<p>2か月前の電気料金： <?= $bill_two_month_before ?>円</p>
<p>3か月前の電気料金： <?=  $bill_three_month_before ?>円</p>

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

let this_month = year+'/'+ month;
let one_month_before = year+'/'+ (month - 1);
let two_month_before = year+'/'+ (month - 2);
let three_month_before = year+'/'+ (month - 3);

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
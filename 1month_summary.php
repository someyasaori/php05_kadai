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

//idをrankingテーブルに入力する
$stmt15 = $pdo->prepare("INSERT INTO ranking(id) VALUES($id)");
$status = $stmt15->execute();

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

//日別の合計値の取得（1分値と30分値で計算式を分ける）
$daily_sum = array();
if($polling == "1min"){
    $stmt = $pdo->prepare ("SELECT DATE_FORMAT(plot_date_time, '%Y-%m-%d') AS plot_date_time, SUM(wh/(1000*60)) AS daily_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$this_month' AND '$today' GROUP BY DATE_FORMAT(plot_date_time, '%Y-%m-%d') ");  
} else if ($polling == "30min"){
        $stmt = $pdo->prepare ("SELECT DATE_FORMAT(plot_date_time, '%Y-%m-%d') AS plot_date_time, SUM(wh/1000) AS daily_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$this_month' AND '$today' GROUP BY DATE_FORMAT(plot_date_time, '%Y-%m-%d') ");
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


//今月の累積wh計算。まず前月までの合計値を計算。
if($polling == "1min"){
    $stmt16 =$pdo->prepare 
    ("SELECT SUM(wh/1000/60) as precum_sum FROM $table_name WHERE wh>0 AND plot_date_time <= '$this_month'");
    } else {
        $stmt16 =$pdo->prepare 
    ("SELECT SUM(wh/1000) as precum_sum FROM $table_name WHERE wh>0 AND plot_date_time <= '$this_month'");
    }
 
$status = $stmt16->execute();
if($row16 = $stmt16 -> fetch()){
    $precum_sum = $row16['precum_sum'];
    }

//今月の累積wh計算。前月までの合計値を引く。
$cum_sum = array();

if($polling == "1min"){
$stmt17 =$pdo->prepare 
("SELECT temp1.plot_date_time, temp1.wh, (SUM(temp2.wh/1000/60) - $precum_sum) AS cum_sum 
 FROM $table_name temp1 
 INNER JOIN $table_name temp2 
 ON temp1.plot_date_time >= temp2.plot_date_time 
 AND temp1.wh>0 AND temp1.plot_date_time BETWEEN '$this_month' AND '$today' 
 GROUP BY temp1.plot_date_time, temp1.wh
 ORDER BY temp1.plot_date_time "); 
} else {
    $stmt17 =$pdo->prepare 
    ("SELECT temp1.plot_date_time, temp1.wh, (SUM(temp2.wh/1000) - $precum_sum) AS cum_sum 
    FROM $table_name temp1 
    INNER JOIN $table_name temp2 
    ON temp1.plot_date_time >= temp2.plot_date_time 
    AND temp1.wh>0 AND temp1.plot_date_time BETWEEN '$this_month' AND '$today' 
    GROUP BY temp1.plot_date_time, temp1.wh
    ORDER BY temp1.plot_date_time "); 
}

$status = $stmt17->execute();
if ($status == false) {
    sql_error($status);
} else {
    $cum_sum = $stmt17->fetchAll();
    }

//配列をJSON形式に変更    
$json_array2 = json_encode($cum_sum);


//先月の累積wh計算。まず先々月までの合計値を計算
if($polling == "1min"){
    $stmt18 =$pdo->prepare 
    ("SELECT SUM(wh/1000/60) as precum_sum_last FROM $table_name WHERE wh>0 AND plot_date_time <= '$one_month_before'");
    } else {
        $stmt18 =$pdo->prepare 
    ("SELECT SUM(wh/1000) as precum_sum_last FROM $table_name WHERE wh>0 AND plot_date_time <= '$one_month_before'");
    }
 
$status = $stmt18->execute();
if($row18 = $stmt18 -> fetch()){
    $precum_sum_last = $row18['precum_sum_last'];
    }

//先月の累積wh計算。先々月までの合計値を差し引く
$cum_sum_last = array();

if($polling == "1min"){
$stmt19 =$pdo->prepare 
("SELECT temp1.plot_date_time, temp1.wh, (SUM(temp2.wh/1000/60) - $precum_sum_last) AS cum_sum_last 
 FROM $table_name temp1 
 INNER JOIN $table_name temp2 
 ON temp1.plot_date_time >= temp2.plot_date_time 
 AND temp1.wh>0 AND temp1.plot_date_time BETWEEN '$one_month_before' AND '$end_month_one' 
 GROUP BY temp1.plot_date_time, temp1.wh
 ORDER BY temp1.plot_date_time "); 
} else {
    $stmt19 =$pdo->prepare 
    ("SELECT temp1.plot_date_time, temp1.wh, (SUM(temp2.wh/1000) - $precum_sum_last) AS cum_sum_last 
    FROM $table_name temp1 
    INNER JOIN $table_name temp2 
    ON temp1.plot_date_time >= temp2.plot_date_time 
    AND temp1.wh>0 AND temp1.plot_date_time BETWEEN '$one_month_before' AND '$end_month_one'
    GROUP BY temp1.plot_date_time, temp1.wh
    ORDER BY temp1.plot_date_time "); 
}

$status = $stmt19->execute();
if ($status == false) {
    sql_error($status);
} else {
    $cum_sum_last = $stmt19->fetchAll();
    }

//配列をJSON形式に変更。日付表示を「日」だけにしたいがうまくいかない…    
$json_array3 = json_encode($cum_sum_last);


//今月の使用量合計（1分値と30分値で計算式を分ける）
if($polling == "1min"){
$stmt2 =$pdo->prepare 
("SELECT SUM(wh/1000/60) as monthly_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$this_month' AND '$today'");
} else {
    $stmt2 =$pdo->prepare 
("SELECT SUM(wh/1000) as monthly_wh FROM $table_name WHERE wh>0 AND plot_date_time BETWEEN '$this_month' AND '$today'");
}

$status = $stmt2->execute();
if($row2 = $stmt2 -> fetch()){
    $wh_this_month = $row2['monthly_wh'];
    }
//小数点以下四捨五入
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
//小数点以下四捨五入
$wh_last_month_r = round($wh_last_month);


//時間帯別料金との分岐点
if ($plan == "tepco_night8"){
//今月の電気料金（時間帯別単価の場合）
$stmt9 =$pdo->prepare 
("SELECT 
   sum(tepco_night8.var_s1 * $table_name.wh/(1000*60)) AS bill
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
    $this_month_bill = round($row9['bill'] + $fixed);
    }

    
//先月の電気料金（時間帯別単価の場合）全て再エネ賦課金追加
$stmt10 =$pdo->prepare 
("SELECT 
   sum(tepco_night8.var_s1 * $table_name.wh/(1000*60)) AS bill
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
    $last_month_bill = round($row10['bill'] + $fixed +3.36*$wh_this_month);
    }
} else {
    //東京ガスは従量バーが違う
    if($plan =="tokyogas"){
        // 今月の電気代取得
        if ($wh_this_month < 140) {
            $this_month_bill = round($fixed + $wh_this_month * $var_s1 + 3.36*$wh_this_month);
        } else if ($wh_this_month < 350){
            $this_month_bill = round($fixed + 140 * $var_s1 + ($wh_this_month-140) * $var_s2 + 3.36*$wh_this_month);
        } else {
            $this_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_this_month-350) * $var_s3 +3.36*$wh_this_month);
        }

        // 先月の電気代取得
        if ($wh_last_month < 140) {
            $last_month_bill = round($fixed + $wh_last_month * $var_s1+3.36*$wh_last_month);
        } else if ($wh_this_month < 300){
            $last_month_bill = round($fixed + 140 * $var_s1 + ($wh_last_month-140) * $var_s2+3.36*$wh_last_month);
        } else {
            $last_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_last_month-350) * $var_s3+3.36*$wh_last_month);
        }
    } else {
        // 今月の電気代取得
        if ($wh_this_month < 120) {
            $this_month_bill = round($fixed + $wh_this_month * $var_s1+3.36*$wh_this_month);
        } else if ($wh_this_month < 300){
            $this_month_bill = round($fixed + 120 * $var_s1 + ($wh_this_month-120) * $var_s2+3.36*$wh_this_month);
        } else {
            $this_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_this_month-300) * $var_s3+3.36*$wh_this_month);
        }

        // 先月の電気代取得
        if ($wh_last_month < 120) {
            $last_month_bill = round($fixed + $wh_last_month * $var_s1+3.36*$wh_last_month);
        } else if ($wh_this_month < 300){
            $last_month_bill = round($fixed + 120 * $var_s1 + ($wh_last_month-120) * $var_s2+3.36*$wh_last_month);
        } else {
            $last_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_last_month-300) * $var_s3+3.36*$wh_last_month);
        }
    }
}

//排出係数(CO2 キロ/kWh) 杉の木1本で年間14kgのCO2を吸収
$stmt20 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = '$plan' ");
$status = $stmt20->execute();
if($row20 = $stmt20 -> fetch()){
    $emission_rate = $row20['rate']*1000;
    }
//先月の排出量
$emission_last_month = $wh_last_month * $emission_rate;

//２か月前の排出量と差分の計算
$emission_two_month_before = $wh_two_month_before * $emission_rate;
$emission_comparison = round(abs($emission_last_month - $emission_two_month_before));

if ($emission_last_month < $emission_two_month_before){
    $message = "トン減りました";
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
</header>

    <body>
    <div class ="sub-title">今月のでんきの使い方は？</div>

    <div class ="display-outer">
        <div class="in-outer">
            <p class ="month1-before">今日までの電気料金（推定）： <?= $this_month_bill ?> 円</p>
            <p class ="month2-before">先月の電気料金：<?= $last_month_bill ?> 円</p>
        </div>

        <div class="in-outer">
            <p class ="month1-before">今日までの電気使用量： <?= $wh_this_month_r ?> kWh</p>
            <p class ="month2-before">先月の電気使用量：<?= $wh_last_month_r ?> kWh</p>
        </div>
    </div>

    <div class ="display-outer">
         <!-- グラフ -->
         <canvas id="chart" height="80" width="150"></canvas>

        <!-- ひとことアドバイス -->
        <div class="message-wrapper-cum">
            <img src="img/advice.png" alt="advice" width ="300px">
            <div id="message"  style="text-align: center">先月や昨年同月と使い方を比較してみましょう。</div>
        </div>
    </div>
    


    
       
    <!-- <table>
        <tr>
            <th>今日までの電気料金
                <th> <?= $this_month_bill ?> 円</th>
            </th>   
        </tr>
        <tr>
            <td>先月の電気料金
                <td><?= $last_month_bill ?> 円</td>
            </td>
        </tr>
    </table> -->

    <!-- <table>
        <tr>
            <th>今日までの電気使用量
                <th> <?= $wh_this_month_r ?> kWh</th>
            </th>   
        </tr>
        <tr>
            <td>先月の電気使用量
                <td><?= $wh_last_month_r ?> kWh</td>
            </td>
        </tr>
    </table> -->
   

      

</main>

<!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.js"></script>
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

//メッセージ用（工事中）
// if (<?= $bill_two_month_before ?> < <?= $last_month_bill ?> ){
//     $("#message").html ("消し忘れはないですか？今月は省エネを心がけてみましょう。");
// } else {
//     $("#message").html ("この調子でさらに省エネをめざしましょう！");
// }

//グラフ作成
let js_array = <?php echo $json_array2; ?>;//今月分
let js_array_last = <?php echo $json_array3; ?>;//先月分
console.log(js_array);

date_array = [];
kwh_array = [];
date_array_last = [];
kwh_array_last = [];

for(key in js_array){
 date_array.push(js_array[key][0]);
kwh_array.push(js_array[key][2]);
}

for(key in js_array_last){
 date_array_last.push(js_array_last[key][0]);
kwh_array_last.push(js_array_last[key][2]);
}

// var cum_date = new Date(date_array_last);
// var cum_date_r = cum_date.getDate();
// // date_array_last = getDay(date_array_last);

// console.log(cum_date_r);
// console.log(kwh_array);
 

//Chart.jsで折れ線グラフを描く
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
    labels : date_array_last,
    datasets : [
        {
        label: "先月の累積使用量(kWh)",
        borderColor: "#4169e1",
        // backgroundColor: "rgba(60,179,113,0.5)",
        borderWidth:2,
        pointRadius:0,
        data : kwh_array_last
        },  
        {
        label: "今月の累積使用量(kWh)",
        borderColor: "#3cb371",
        // backgroundColor: "rgba(60,179,113,0.5)",
        borderWidth:2,
        pointRadius:0,
        data : kwh_array
        } 
    ]
    
}


</script>

</body>
</html>
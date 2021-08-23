<?php
//Sessionスタート
session_start();

//関数を呼び出す
require_once('funcs.php');

//ログインチェック
loginCheck();
$user_name = $_SESSION['name'];
$id = $_SESSION['id'];

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
$search_month = date('Y-m-d', strtotime($input_month));
$end_month = date('Y-m-d H:i:s', strtotime('last day of '. $input_month.'23:59:59'));

//ログインした人のデータが登録されているテーブル全て
// $stmt = $pdo->prepare("SELECT * FROM $table_name");

//ログインした人のデータが登録されているテーブルから検索条件に当てはまるものを探す

//指定した月毎の合計
$stmt1 =$pdo->prepare ("SELECT SUM(wh) as wh FROM $table_name WHERE plot_date_time BETWEEN '$search_month' AND '$end_month' ");
$status = $stmt1->execute();
if($row1 = $stmt1 -> fetch()){
    $sum_selected_month = $row1['wh'];
    }

//今月の合計
$this_month = date('Y-m-d', strtotime('first day of this month'));
$today = date('Y-m-d H:i:s', strtotime('now'));
$stmt2 =$pdo->prepare ("SELECT SUM(wh) as wh FROM $table_name WHERE plot_date_time BETWEEN '$this_month' AND '$today' ");
$status = $stmt2->execute();
if($row2 = $stmt2 -> fetch()){
    $sum_this_month = $row2['wh'];
    }

//先月の合計
// $last_month = date('Y-m-d', strtotime('first day of last month'));
// $end_of_last_month = date('Y-m-d H:i:s', strtotime('last day of '. $last_month.'23:59:59'));
$this_this_month = date('m', strtotime('this month')) ;
$this_year =  date('Y', strtotime('this month')) ;
$one_month_before= $this_this_month - 1;
$month_one  = $this_year.'-'.$one_month_before.'-'.'1';
$end_month_one = date('Y-m-d H:i:s', strtotime('last day of '. $month_one.'23:59:59'));

$stmt3 =$pdo->prepare ("SELECT SUM(wh) as wh FROM $table_name WHERE plot_date_time BETWEEN '$month_one' AND '$end_month_one' ");

$status = $stmt3->execute();

if($row3 = $stmt3 -> fetch()){
    $sum_last_month = $row3['wh'];
    }

//2か月前の合計
$two_month_before= $this_this_month - 2;
$month_two  = $this_year.'-'.$two_month_before.'-'.'1';
$end_month_two = date('Y-m-d H:i:s', strtotime('last day of '. $month_two.'23:59:59'));

$stmt4 =$pdo->prepare ("SELECT SUM(wh) as wh FROM $table_name WHERE plot_date_time BETWEEN '$month_two' AND '$end_month_two' ");
$status = $stmt4->execute();

if($row4 = $stmt4 -> fetch()){
    $sum_two_month_before = $row4['wh'];
    }

//3か月前の合計
$this_month = date('m', strtotime('this month')) ;
$this_year =  date('Y', strtotime('this month')) ;
$three_month_before= $this_this_month - 3;
$month_three  = $this_year.'-'.$three_month_before.'-'.'1';
$end_month_three = date('Y-m-d H:i:s', strtotime('last day of '. $month_three.'23:59:59'));

$stmt5 =$pdo->prepare ("SELECT SUM(wh) as wh FROM $table_name WHERE plot_date_time BETWEEN '$month_three' AND '$end_month_three' ");
$status = $stmt5->execute();

if($row5 = $stmt5 -> fetch()){
    $sum_three_month_before = $row5['wh'];
    }

//DB接続（電気料金メニューデータ）
$pdo0 = db_conn2(); 

$stmt9 =$pdo0->prepare
("ALTER TABLE power_db.$table_name ADD bill INT(16);
SELECT power_db.$table_name.plot_date_time,
 tepco.standard.var_s1,
 power_db.$table_name.wh
FROM power_db.$table_name LEFT JOIN tepco.standard ON DATE_FORMAT(power_db.$table_name.plot_date_time, '%Y-%m-%d %H:%i:%s') = tepco.standard.plot_date_time;
SELECT * FROM power_db.$table_name
-- SET power_db.$table_name.bill = tepco.standard.var_s1 * power_db.$table_name.wh/2;
-- SELECT SUM(bill) as bill FROM power_db.$table_name WHERE plot_date_time BETWEEN '$this_month' AND '$today' ");
$status = $stmt9->execute();

$view="";

if($stmt9==false){
    sql_error($stmt9);
}else{

if($status9==false){
    $error9 = $stmt9->errorInfor();
    exit("ErrorQuery:". $error9[2]);
}else{
    while ($result9 = $stmt9->fetch(PDO::FETCH_ASSOC)){
        $view .= "<tr>";
        $view .= "<td>".h($result9['plot_date_time']).'</td><td>'.h($result9['wh']).'</td><td>'.h($result7['bill']);
        $view .= "</tr>";
    }

    }
}

// if($row9 = $stmt9 -> fetch()){
//     $bill_this_month = $row9['bill'];
//     }

// SELECT
//   id2.plot_date_time,
//   standard.var_s1,
//   id2.wh
// FROM id2 LEFT JOIN standard ON DATE_FORMAT(id2.plot_date_time, '%Y-%m-%d %H:%i:%s') = standard.plot_date_time



// echo $bill_this_month;
// exit();

//登録されているテーブル全て
// $stmt0 = $pdo0->prepare("SELECT * FROM standard");
// //実行
// $status = $stmt0->execute();

// //電力量データと電気料金メニューデータの掛け合わせ
// //時刻フラグのカラムを追記
// // $stmt6 =$pdo->prepare ("ALTER TABLE standard ADD hour INT(10) FIRST");
// // $status = $stmt6->execute();


// $stmt7 =$pdo->prepare
//  ("ALTER TABLE standard ADD hour INT(10) FIRST;
// UPDATE standard SET hour=0 WHERE TIME(time) BETWEEN '00:00:00' AND '00:59:59';
// UPDATE standard SET hour=1 WHERE TIME(time) BETWEEN '01:00:00' AND '01:59:59';
// UPDATE standard SET hour=2 WHERE TIME(time) BETWEEN '02:00:00' AND '02:59:59';
// UPDATE standard SET hour=3 WHERE TIME(time) BETWEEN '03:00:00' AND '03:59:59';
// UPDATE standard SET hour=4 WHERE TIME(time) BETWEEN '04:00:00' AND '04:59:59';
// UPDATE standard SET hour=5 WHERE TIME(time) BETWEEN '05:00:00' AND '05:59:59';
// UPDATE standard SET hour=6 WHERE TIME(time) BETWEEN '06:00:00' AND '06:59:59';
// UPDATE standard SET hour=7 WHERE TIME(time) BETWEEN '07:00:00' AND '07:59:59';
// UPDATE standard SET hour=8 WHERE TIME(time) BETWEEN '08:00:00' AND '08:59:59';
// UPDATE standard SET hour=9 WHERE TIME(time) BETWEEN '09:00:00' AND '09:59:59';
// UPDATE standard SET hour=10 WHERE TIME(time) BETWEEN '10:00:00' AND '10:59:59';
// UPDATE standard SET hour=11 WHERE TIME(time) BETWEEN '11:00:00' AND '11:59:59';
// UPDATE standard SET hour=12 WHERE TIME(time) BETWEEN '12:00:00' AND '12:59:59';
// UPDATE standard SET hour=13 WHERE TIME(time) BETWEEN '13:00:00' AND '13:59:59';
// UPDATE standard SET hour=14 WHERE TIME(time) BETWEEN '14:00:00' AND '14:59:59';
// UPDATE standard SET hour=15 WHERE TIME(time) BETWEEN '15:00:00' AND '15:59:59';
// UPDATE standard SET hour=16 WHERE TIME(time) BETWEEN '16:00:00' AND '16:59:59';
// UPDATE standard SET hour=17 WHERE TIME(time) BETWEEN '17:00:00' AND '17:59:59';
// UPDATE standard SET hour=18 WHERE TIME(time) BETWEEN '18:00:00' AND '18:59:59';
// UPDATE standard SET hour=19 WHERE TIME(time) BETWEEN '19:00:00' AND '19:59:59';
// UPDATE standard SET hour=20 WHERE TIME(time) BETWEEN '20:00:00' AND '20:59:59';
// UPDATE standard SET hour=21 WHERE TIME(time) BETWEEN '21:00:00' AND '21:59:59';
// UPDATE standard SET hour=22 WHERE TIME(time) BETWEEN '22:00:00' AND '22:59:59';
// UPDATE standard SET hour=23 WHERE TIME(time) BETWEEN '23:00:00' AND '23:59:59' ");

// $status = $stmt7->execute();

//時刻フラグのカラムを追記
// $stmt8 =$pdo->prepare ("ALTER TABLE $table_name ADD hour INT(10) FIRST;
// ALTER TABLE $table_name ADD bill INT(16);
// UPDATE $table_name SET hour=0 WHERE TIME(time) BETWEEN '00:00:00' AND '00:59:59';
// UPDATE $table_name SET hour=1 WHERE TIME(time) BETWEEN '01:00:00' AND '01:59:59';
// UPDATE $table_name SET hour=2 WHERE TIME(time) BETWEEN '02:00:00' AND '02:59:59';
// UPDATE $table_name SET hour=3 WHERE TIME(time) BETWEEN '03:00:00' AND '03:59:59';
// UPDATE $table_name SET hour=4 WHERE TIME(time) BETWEEN '04:00:00' AND '04:59:59';
// UPDATE $table_name SET hour=5 WHERE TIME(time) BETWEEN '05:00:00' AND '05:59:59';
// UPDATE $table_name SET hour=6 WHERE TIME(time) BETWEEN '06:00:00' AND '06:59:59';
// UPDATE $table_name SET hour=7 WHERE TIME(time) BETWEEN '07:00:00' AND '07:59:59';
// UPDATE $table_name SET hour=8 WHERE TIME(time) BETWEEN '08:00:00' AND '08:59:59';
// UPDATE $table_name SET hour=9 WHERE TIME(time) BETWEEN '09:00:00' AND '09:59:59';
// UPDATE $table_name SET hour=10 WHERE TIME(time) BETWEEN '10:00:00' AND '10:59:59';
// UPDATE $table_name SET hour=11 WHERE TIME(time) BETWEEN '11:00:00' AND '11:59:59';
// UPDATE $table_name SET hour=12 WHERE TIME(time) BETWEEN '12:00:00' AND '12:59:59';
// UPDATE $table_name SET hour=13 WHERE TIME(time) BETWEEN '13:00:00' AND '13:59:59';
// UPDATE $table_name SET hour=14 WHERE TIME(time) BETWEEN '14:00:00' AND '14:59:59';
// UPDATE $table_name SET hour=15 WHERE TIME(time) BETWEEN '15:00:00' AND '15:59:59';
// UPDATE $table_name SET hour=16 WHERE TIME(time) BETWEEN '16:00:00' AND '16:59:59';
// UPDATE $table_name SET hour=17 WHERE TIME(time) BETWEEN '17:00:00' AND '17:59:59';
// UPDATE $table_name SET hour=18 WHERE TIME(time) BETWEEN '18:00:00' AND '18:59:59';
// UPDATE $table_name SET hour=19 WHERE TIME(time) BETWEEN '19:00:00' AND '19:59:59';
// UPDATE $table_name SET hour=20 WHERE TIME(time) BETWEEN '20:00:00' AND '20:59:59';
// UPDATE $table_name SET hour=21 WHERE TIME(time) BETWEEN '21:00:00' AND '21:59:59';
// UPDATE $table_name SET hour=22 WHERE TIME(time) BETWEEN '22:00:00' AND '22:59:59';
// UPDATE $table_name SET hour=23 WHERE TIME(time) BETWEEN '23:00:00' AND '23:59:59'");

// $status = $stmt8->execute();

//今月の電気料金を計算したい
// $stmt9 =$pdo->prepare
// ("UPDATE power_db.$table_name LEFT JOIN tepco.standard 
// ON power_db.$table_name.hour = tepco.standard.hour
// SET power_db.$table_name.bill = tepco.standard.var_s1 * power_db.$table_name.wh/2;
// SELECT SUM(bill) as bill FROM $table_name WHERE time BETWEEN '$this_month' AND '$today' ");

// UPDATE power_db.id2 LEFT JOIN tepco.standard 
// ON power_db.id2.hour = tepco.standard.hour
// SET power_db.id2.bill = tepco.standard.var_s1 * power_db.id2.wh/2

// $view="";

// if($stmt7==false){
//     sql_error($stmt7);
// }else{

// if($status7==false){
//     $error7 = $stmt7->errorInfor();
//     exit("ErrorQuery:". $error7[2]);
// }else{
//     while ($result7 = $stmt7->fetch(PDO::FETCH_ASSOC)){
//         $view .= "<tr>";
//         $view .= "<td>".h($result7['hour']).'</td><td>'.h($result7['time']).'</td><td>'.h($result7['var_s1']);
//         $view .= "</tr>";
//     }

//     }
// }

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

<table class="result">
    <tr>
    <th>時間</th>
	<th>基本料金</th>
	<th>従量料金1段目</th>
    </tr>
    <?= $view ?>
</table>

<!-- <?= $bill_this_month ?> -->

<canvas id="chart" height="100" width="200"></canvas>

<p class="return"><a href="index.php">トップに戻る</a></p>



<!-- JQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<!-- JQuery -->

<script>

//年月表示の整理
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
        data : [<?= $sum_three_month_before ?>,<?= $sum_two_month_before ?>,<?= $sum_last_month ?>,<?= $sum_this_month ?>]
        },   
    ]
}

</script>

</body>
</html>
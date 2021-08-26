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
$stmt2 =$pdo->prepare 
("SELECT SUM(wh) as wh FROM $table_name WHERE plot_date_time BETWEEN '$this_month' AND '$today'");
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
// $pdo0 = db_conn2(); 

// $mysql =['host'=>'localhost', 'dbname'=>'power_db', 'user'=>'root', 'pass'=>'root'];

// //connects with PDO 
// try {
//     $conn = new PDO('mysql:host='. $mysql['host'] .'; dbname='. $mysql['dbname'], $mysql['user'], $mysql['pass']);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //set the PDO error mode to exception
//   }
//   catch(PDOException $e){ exit('Connection failed: '. $e->getMessage());}


// $stmt9 =$pdo->prepare 
// ("SELECT $table_name.plot_date_time, tepco_standard.var_s1, $table_name.wh, SUM(tepco_standard.var_s1 * $table_name.wh/2) as bill
//     FROM $table_name LEFT JOIN tepco_standard ON DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_standard.plot_date_time, '%H:%i:%s') 
//     AND var_s1
//     WHERE plot_date_time BETWEEN '$this_month' AND '$today' ");
// $status = $stmt9->execute();
// if($row9 = $stmt9 -> fetch()){
//     $bill_this_month = $row9['bill'];
//     }

// var_dump ($row9);

$stmt9 =$pdo->prepare 
("SELECT 
   sum(tepco_standard.var_s1 * $table_name.wh/2) AS bill
FROM
	$table_name
LEFT JOIN 
	tepco_standard 
ON 
    DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_standard.plot_date_time, '%H:%i:%s')
WHERE
DATE_FORMAT($table_name.plot_date_time, '%Y-%m-%d %H:%i:%s')

BETWEEN '2021-08-01 00:00:00' AND '2021-08-26 22:00:00'");

$status = $stmt9->execute();
if($row9 = $stmt9 -> fetch()){
    $bill_this_month = $row9['bill'];
    }


$stmt10 =$pdo->prepare 
("SELECT 
   sum(tepco_standard.var_s1 * $table_name.wh/2) AS bill
FROM
	$table_name
LEFT JOIN 
	tepco_standard 
ON 
    DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_standard.plot_date_time, '%H:%i:%s')
WHERE
DATE_FORMAT($table_name.plot_date_time, '%Y-%m-%d %H:%i:%s')
BETWEEN
'$month_two' AND '$end_month_two'");

$status = $stmt10->execute();

if($row10 = $stmt10 -> fetch()){
    $bill_last_month = $row10['bill'];
    }

var_dump($bill_last_month);

// $stmt9 =$pdo->prepare 
// ("SELECT $table_name.plot_date_time, tepco_standard.var_s1, $table_name.wh
//     FROM $table_name LEFT JOIN tepco_standard ON DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_standard.plot_date_time, '%H:%i:%s') ");

// $status = $stmt9->execute();

// $stmt10 =$pdo->prepare 
// ("SELECT  tepco_standard.var_s1 * $table_name.wh/2 AS bill FROM $table_name LEFT JOIN tepco_standard ON var_s1");
// $status = $stmt10->execute();


// $stmt11 =$pdo->prepare 
// ("SELECT SUM(bill) as bill FROM $table_name WHERE plot_date_time BETWEEN '$this_month' AND '$today' ");
// $status = $stmt11->execute();
// if($row11 = $stmt11 -> fetch()){
//     $bill_this_month = $row11['bill'];
//     }

// var_dump ($row11);


//   $sql ="SELECT $table_name.plot_date_time,
//     tepco_standard.var_s1,
//     $table_name.wh
//     FROM $table_name LEFT JOIN tepco_standard ON DATE_FORMAT($table_name.plot_date_time, '%H:%i:%s') = DATE_FORMAT(tepco_standard.plot_date_time, '%H:%i:%s') ;
//     ALTER TABLE power_db.$table_name ADD bill INT(16);
//     SET power_db.$table_name.bill = tepco.standard.var_s1 * power_db.$table_name.wh/2;
//     SELECT SUM(bill) as bill FROM power_db.$table_name WHERE plot_date_time BETWEEN '$this_month' AND '$today' ";

//  $stmt9 = $conn->query($sql);

 
// echo $bill_this_month;
// exit();


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
<p>先月の電気料金： <?= $bill_last_month ?></p>

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
        data : [<?= $sum_three_month_before ?>,<?= $sum_two_month_before ?>,<?= $sum_last_month ?>,<?= $sum_this_month ?>]
        },   
    ]
}

</script>

</body>
</html>
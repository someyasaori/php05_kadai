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

//idをrankingテーブルに入力する（ランキング実装できず）
// $stmt15 = $pdo->prepare("INSERT INTO ranking(id) VALUES($id)");
// $status = $stmt15->execute();

//今日時点までの定義
$this_month = date('Y-m-d 00:00:00', strtotime('first day of this month'));
$today = date('Y-m-d H:i:s', strtotime('now'));

//先月の定義
$one_month_before = date('Y-m-d H:i:s', strtotime(date('Y-m-1') . '-1 month'));
$end_month_one = date('Y-m-d 23:59:59', strtotime('last day of '. $one_month_before));


//アンペア計算：電流（A）＝電力（W）÷100V
$ampere_data = array();
if($polling == "1min"){
    $stmt = $pdo->prepare ("SELECT DATE_FORMAT(plot_date_time, '%Y-%m-%d') AS plot_date_time, wh/100 AS ampere_data 
    FROM $table_name WHERE wh>0 
    AND plot_date_time BETWEEN '$this_month' AND '$today'
    ORDER BY plot_date_time");  
} else {
    exit("このサービスはご利用できません");
    } 

$status = $stmt->execute();
if ($status == false) {
    sql_error($status);
} else {
    $ampere_data = $stmt->fetchAll();
    }

//配列をJSON形式に変更    
$json_array = json_encode($ampere_data);



?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <title>でんきの使い方サマリー</title>
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
    <h2>最適なでんき料金プランは？</h2>
    <p class ="month1-before">現在の契約アンペア数：<?= $ampere ?> A</p>
        <canvas id="chart" height="100" width="200" style="display: block; margin: auto;"></canvas>
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
 

//Chart.jsで線グラフを描く
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
        label: "消費アンペア(A)",   
        borderColor: "#3cb371",
        // backgroundColor: "rgba(60,179,113,0.5)",
        borderWidth:2,
        pointRadius:0,
        data : kwh_array
        },   
    ]
}

</script>

</body>
</html>
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

//3か月前の排出量
$emission_three_month_before = $wh_three_month_before * $emission_rate;

//再エネ賦課金単価を定義
$re = 3.36 ;

//時間帯別料金との分岐点

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
        $bill_this_month = round($row9['bill']+ $fixed + $re*$wh_this_month);
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
        $last_month_bill = round($row10['bill'] + $fixed+ $re*$wh_last_month);
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
        $bill_two_month_before = round($row10['bill'] + $fixed + $re*$wh_two_month_before);
        }

    //3か月前の電気料金（時間帯別単価の場合）
    $stmt15 =$pdo->prepare 
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

    $status = $stmt15->execute();

    if($row15 = $stmt15 -> fetch()){
        $bill_three_month_before = round($row15['bill']+ $fixed + $re*$wh_three_month_before);
        }

} else {
    //東京ガスは従量のバーが異なる   
        // 今月の電気代取得
    if($plan == "tokyogas"){
        if ($wh_this_month < 140) {
            $this_month_bill = round($fixed + $wh_this_month * $var_s1+ $re*$wh_this_month);
        } else if ($wh_this_month < 350){
            $this_month_bill = round($fixed + 140 * $var_s1 + ($wh_this_month-140) * $var_s2+ $re*$wh_this_month);
        } else {
            $this_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_this_month-350) * $var_s3+ $re*$wh_this_month);
        };

        // 先月の電気代取得
        if ($wh_last_month < 140) {
            $last_month_bill = round($fixed + $wh_last_month * $var_s1+ $re*$wh_last_month);
        } else if ($wh_last_month < 350){
            $last_month_bill = round($fixed + 140 * $var_s1 + ($wh_last_month-140) * $var_s2+ $re*$wh_last_month);
        } else {
            $last_month_bill = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_last_month-350) * $var_s3+ $re*$wh_last_month);
        }

        // 2か月前の電気代取得
        if ($wh_two_month_before < 140) {
            $bill_two_month_before = round($fixed + $wh_two_month_before * $var_s1+ + $re*$wh_two_month_before);
        } else if ($wh_two_month_before < 350){
            $bill_two_month_before = round($fixed + 140 * $var_s1 + ($wh_two_month_before-140) * $var_s2 + $re*$wh_two_month_before);
        } else {
            $bill_two_month_before = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_two_month_before-350) * $var_s3 + $re*$wh_two_month_before);
        }    

        // 3か月前の電気代取得
        if ($wh_three_month_before < 140) {
            $bill_three_month_before = round($fixed + $wh_three_month_before * $var_s1 + $re*$wh_three_month_before);
        } else if ($wh_three_month_before < 350){
            $bill_three_month_before = round($fixed + 140 * $var_s1 + ($wh_three_month_before-140) * $var_s2 + $re*$wh_three_month_before);
        } else {
            $bill_three_month_before = round($fixed + 140 * $var_s1 + (350 - 140) *$var_s2 +($wh_three_month_before-350) * $var_s3 + $re*$wh_three_month_before);
        }    
    
    } else {
        if ($wh_this_month < 120) {
            $this_month_bill = round($fixed + $wh_this_month * $var_s1+ $re*$wh_this_month);
        } else if ($wh_this_month < 300){
            $this_month_bill = round($fixed + 120 * $var_s1 + ($wh_this_month-120) * $var_s2+ $re*$wh_this_month);
        } else {
            $this_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_this_month-300) * $var_s3+ $re*$wh_this_month);
        };

        // 先月の電気代取得
        if ($wh_last_month < 120) {
            $last_month_bill = round($fixed + $wh_last_month * $var_s1+ $re*$wh_last_month);
        } else if ($wh_last_month < 300){
            $last_month_bill = round($fixed + 120 * $var_s1 + ($wh_last_month-120) * $var_s2+ $re*$wh_last_month);
        } else {
            $last_month_bill = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_last_month-300) * $var_s3+ $re*$wh_last_month);
        }

        // 2か月前の電気代取得
        if ($wh_two_month_before < 120) {
            $bill_two_month_before = round($fixed + $wh_two_month_before * $var_s1 + $re*$wh_two_month_before);
        } else if ($wh_two_month_before < 300){
            $bill_two_month_before = round($fixed + 120 * $var_s1 + ($wh_two_month_before-120) * $var_s2 + $re*$wh_two_month_before);
        } else {
            $bill_two_month_before = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_two_month_before-300) * $var_s + $re*$wh_two_month_before3);
        }    

        // 3か月前の電気代取得
        if ($wh_three_month_before < 120) {
            $bill_three_month_before = round($fixed + $wh_three_month_before * $var_s1 + $re*$wh_three_month_before);
        } else if ($wh_three_month_before < 300){
            $bill_three_month_before = round($fixed + 120 * $var_s1 + ($wh_three_month_before-120) * $var_s2 + $re*$wh_three_month_before);
        } else {
            $bill_three_month_before = round($fixed + 120 * $var_s1 + (300 - 120) *$var_s2 +($wh_three_month_before-300) * $var_s3 + $re*$wh_three_month_before);
        }    
    }
}

//user_tableに先月の電気料金を記録
    // $stmt16 = $pdo->prepare ("UPDATE ranking SET last_month_bill = $last_month_bill) WHERE id = $id");
    // $status = $stmt16->execute();

//過去3か月の使い方からおすすめの電力メニューとエコな電力メニュー
//各社の料金体系で電気料金試算
//東京電力
    //基本料金取得
    $stmt17 = $pdo->prepare 
    ("SELECT fixed FROM tepco_standard WHERE plot_date_time = '00:00:00'");
    $status = $stmt17->execute();
    if($row17 = $stmt17 -> fetch()){
        $fixed_tepco_standard = $row17['fixed'] * ($ampere/10);
        }

    // 従量料金単価（１段目）取得
    $stmt18 =$pdo->prepare 
    ("SELECT var_s1 FROM tepco_standard WHERE plot_date_time = '00:00:00'");
    $status = $stmt18->execute();
    if($row18 = $stmt18 -> fetch()){
        $var_s1_tepco_standard = $row18['var_s1'];
        }

    //従量料金単価（2段目）取得
    $stmt19 =$pdo->prepare 
    ("SELECT var_s2 FROM tepco_standard WHERE plot_date_time = '00:00:00' ");
    $status = $stmt19->execute();
    if($row19 = $stmt19 -> fetch()){
        $var_s2_tepco_standard = $row19['var_s2'];
        }

    //従量料金単価（3段目）取得
    $stmt20 =$pdo->prepare 
    ("SELECT var_s3 FROM tepco_standard WHERE plot_date_time = '00:00:00' ");
    $status = $stmt20->execute();
    if($row20 = $stmt20 -> fetch()){
        $var_s3_tepco_standard = $row20['var_s3'];
        }

    // 先月の電気代取得
    if ($wh_last_month < 120) {
            $last_month_bill_tepco_standard = round($fixed_tepco_standard + $wh_last_month * $var_s1_tepco_standard+ $re*$wh_last_month);
        } else if ($wh_last_month < 300){
            $last_month_bill_tepco_standard = round($fixed_tepco_standard + 120 * $var_s1_tepco_standard + ($wh_last_month-120) * $var_s2_tepco_standard+ $re*$wh_last_month);
        } else {
            $last_month_bill_tepco_standard = round($fixed_tepco_standard + 120 * $var_s1_tepco_standard + (300 - 120) *$var_s2_tepco_standard +($wh_last_month-300) * $var_s3_tepco_standard+ $re*$wh_last_month);
        }

    // 2か月前の電気代取得
        if ($wh_two_month_before < 120) {
            $bill_two_month_before_tepco_standard = round($fixed_tepco_standard + $wh_two_month_before * $var_s1_tepco_standard + $re*$wh_two_month_before );
        } else if ($wh_two_month_before < 300){
            $bill_two_month_before_tepco_standard = round($fixed_tepco_standard + 120 * $var_s1_tepco_standard + ($wh_two_month_before-120) * $var_s2_tepco_standard+ $re*$wh_two_month_before );
        } else {
            $bill_two_month_before_tepco_standard = round($fixed_tepco_standard + 120 * $var_s1_tepco_standard + (300 - 120) *$var_s2_tepco_standard +($wh_two_month_before-300) * $var_s3_tepco_standard+ $re*$wh_two_month_before );
        }    

    // 3か月前の電気代取得
        if ($wh_three_month_before < 120) {
            $bill_three_month_before_tepco_standard = round($fixed_tepco_standard + $wh_three_month_before * $var_s1_tepco_standard + $re*$wh_three_month_before);
        } else if ($wh_three_month_before < 300){
            $bill_three_month_before_tepco_standard = round($fixed_tepco_standard + 120 * $var_s1_tepco_standard + ($wh_three_month_before-120) * $var_s2_tepco_standard + $re*$wh_three_month_before);
        } else {
            $bill_three_month_before_tepco_standard = round($fixed_tepco_standard + 120 * $var_s1_tepco_standard + (300 - 120) *$var_s2_tepco_standard +($wh_three_month_before-300) * $var_s3_tepco_standard + $re*$wh_three_month_before);
        } 
    
    //3か月の平均電気代
    $result_tepco_standard = round(($last_month_bill_tepco_standard + $bill_two_month_before_tepco_standard + $bill_three_month_before_tepco_standard)/3);
    
    //排出係数
    $stmt41 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = 'tepco_standard' ");
    $status = $stmt41->execute();
    if($row41 = $stmt41 -> fetch()){
        $emission_rate_tepco = $row41['rate']*1000;
        }
    //３か月のCO2排出量計
    $emission_tepco_standard = ($wh_last_month + $wh_two_month_before + $wh_three_month_before) * $emission_rate_tepco;
    

//東京電力夜間


//東京ガス
    //基本料金取得
    $stmt21 = $pdo->prepare 
    ("SELECT fixed FROM tokyogas WHERE plot_date_time = '00:00:00'");
    $status = $stmt21->execute();
    if($row21 = $stmt21 -> fetch()){
        $fixed_tokyogas = $row21['fixed'] * ($ampere/10);
        }

    // 従量料金単価（１段目）取得
    $stmt22 =$pdo->prepare 
    ("SELECT var_s1 FROM tokyogas WHERE plot_date_time = '00:00:00'");
    $status = $stmt22->execute();
    if($row22 = $stmt22 -> fetch()){
        $var_s1_tokyogas = $row22['var_s1'];
        }

    //従量料金単価（2段目）取得
    $stmt23 =$pdo->prepare 
    ("SELECT var_s2 FROM tokyogas WHERE plot_date_time = '00:00:00' ");
    $status = $stmt23->execute();
    if($row23 = $stmt23 -> fetch()){
        $var_s2_tokyogas = $row23['var_s2'];
        }

    //従量料金単価（3段目）取得
    $stmt24 =$pdo->prepare 
    ("SELECT var_s3 FROM tokyogas WHERE plot_date_time = '00:00:00' ");
    $status = $stmt24->execute();
    if($row24 = $stmt24 -> fetch()){
        $var_s3_tokyogas = $row24['var_s3'];
        }

    // 先月の電気代取得
    if ($wh_last_month < 140) {
        $last_month_bill_tokyogas = round($fixed_tokyogas + $wh_last_month * $var_s1_tokyogas+$re*$wh_last_month);
    } else if ($wh_last_month < 350){
        $last_month_bill_tokyogas = round($fixed_tokyogas + 140 * $var_s1_tokyogas + ($wh_last_month-140) * $var_s2_tokyogas+$re*$wh_last_month);
    } else {
        $last_month_bill_tokyogas = round($fixed_tokyogas + 140 * $var_s1_tokyogas + (350 - 140) *$var_s2_tokyogas +($wh_last_month-350) * $var_s3_tokyogas+$re*$wh_last_month);
    }
    //2か月前の電気代取得
    if ($wh_two_month_before < 140) {
        $bill_two_month_before_tokyogas = round($fixed_tokyogas + $wh_two_month_before * $var_s1_tokyogas+ $re*$wh_two_month_before );
    } else if ($wh_two_month_before < 350){
        $bill_two_month_before_tokyogas = round($fixed_tokyogas + 140 * $var_s1_tokyogas + ($wh_two_month_before-140) * $var_s2_tokyogas+ $re*$wh_two_month_before );
    } else {
        $bill_two_month_before_tokyogas = round($fixed_tokyogas + 140 * $var_s1_tokyogas + (350 - 140) *$var_s2_tokyogas +($wh_two_month_before-350) * $var_s3_tokyogas+ $re*$wh_two_month_before );
    }    

    // 3か月前の電気代取得
    if ($wh_three_month_before < 140) {
        $bill_three_month_before_tokyogas = round($fixed_tokyogas + $wh_three_month_before * $var_s1_tokyogas + $re*$wh_three_month_before);
    } else if ($wh_three_month_before < 350){
        $bill_three_month_before_tokyogas = round($fixed_tokyogas + 140 * $var_s1_tokyogas + ($wh_three_month_before-140) * $var_s2_tokyogas + $re*$wh_three_month_before);
    } else {
        $bill_three_month_before_tokyogas = round($fixed_tokyogas + 140 * $var_s1_tokyogas + (350 - 140) *$var_s2_tokyogas +($wh_three_month_before-350) * $var_s3_tokyogas + $re*$wh_three_month_before);
    } 

    //3か月の平均電気代
    $result_tokyogas = round(($last_month_bill_tokyogas + $bill_two_month_before_tokyogas + $bill_three_month_before_tokyogas)/3);

    //排出係数
    $stmt42 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = 'tokyogas' ");
    $status = $stmt42->execute();
    if($row42 = $stmt42 -> fetch()){
        $emission_rate_tokyogas = $row42['rate']*1000;
        }
    //３か月のCO2排出量計
    $emission_tokyogas = ($wh_last_month + $wh_two_month_before + $wh_three_month_before) * $emission_rate_tokyogas;


//まちエネ
    //基本料金取得
    $stmt25 = $pdo->prepare 
    ("SELECT fixed FROM mcre WHERE plot_date_time = '00:00:00'");
    $status = $stmt25->execute();
    if($row25 = $stmt25 -> fetch()){
        $fixed_mcre = $row25['fixed'] * ($ampere/10);
        }

    // 従量料金単価（１段目）取得
    $stmt26 =$pdo->prepare 
    ("SELECT var_s1 FROM mcre WHERE plot_date_time = '00:00:00'");
    $status = $stmt26->execute();
    if($row26 = $stmt26 -> fetch()){
        $var_s1_mcre = $row26['var_s1'];
        }
      
    //従量料金単価（2段目）取得
    $stmt27 =$pdo->prepare 
    ("SELECT var_s2 FROM mcre WHERE plot_date_time = '00:00:00' ");
    $status = $stmt27->execute();
    if($row27 = $stmt27 -> fetch()){
        $var_s2_mcre = $row27['var_s2'];
        }

    //従量料金単価（3段目）取得
    $stmt28 =$pdo->prepare 
    ("SELECT var_s3 FROM mcre WHERE plot_date_time = '00:00:00' ");
    $status = $stmt28->execute();
    if($row28 = $stmt28 -> fetch()){
        $var_s3_mcre = $row28['var_s3'];
        }

    // 先月の電気代取得
    if ($wh_last_month < 120) {
        $last_month_bill_mcre = round($fixed_mcre + $wh_last_month * $var_s1_mcre + $re*$wh_last_month);
    } else if ($wh_last_month < 300){
        $last_month_bill_mcre = round($fixed_mcre + 120 * $var_s1_mcre + ($wh_last_month-120) * $var_s2_mcre + $re*$wh_last_month);
    } else {
        $last_month_bill_mcre = round($fixed_mcre + 120 * $var_s1_mcre + (300 - 120) *$var_s2_mcre +($wh_last_month-300) * $var_s3_mcre + $re*$wh_last_month);
    }

    // 2か月前の電気代取得
    if ($wh_two_month_before < 120) {
        $bill_two_month_before_mcre = round($fixed_mcre + $wh_two_month_before * $var_s1_mcre+ $re*$wh_two_month_before );
    } else if ($wh_two_month_before < 300){
        $bill_two_month_before_mcre = round($fixed_mcre + 120 * $var_s1_mcre + ($wh_two_month_before-120) * $var_s2_mcre+ $re*$wh_two_month_before );
    } else {
        $bill_two_month_before_mcre = round($fixed_mcre + 120 * $var_s1_mcre + (300 - 120) *$var_s2_mcre +($wh_two_month_before-300) * $var_s3_mcre+ $re*$wh_two_month_before );
    }    

    // 3か月前の電気代取得
    if ($wh_three_month_before < 120) {
        $bill_three_month_before_mcre = round($fixed_mcre + $wh_three_month_before * $var_s1_mcre + $re*$wh_three_month_before);
    } else if ($wh_three_month_before < 300){
        $bill_three_month_before_mcre = round($fixed_mcre + 120 * $var_s1_mcre + ($wh_three_month_before-120) * $var_s2_mcre + $re*$wh_three_month_before);
    } else {
        $bill_three_month_before_mcre = round($fixed_mcre + 120 * $var_s1_mcre + (300 - 120) *$var_s2_mcre +($wh_three_month_before-300) * $var_s3_mcre + $re*$wh_three_month_before);
    } 

    //3か月の平均電気代
    $result_mcre = round(($last_month_bill_mcre + $bill_two_month_before_mcre + $bill_three_month_before_mcre)/3);
  
    //排出係数
   $stmt43 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = 'mcre' ");
   $status = $stmt43->execute();
   if($row43 = $stmt43 -> fetch()){
       $emission_rate_mcre = $row43['rate']*1000;
       }
   //３か月のCO2排出量計
   $emission_mcre = ($wh_last_month + $wh_two_month_before + $wh_three_month_before) * $emission_rate_mcre;


//auでんき
    //基本料金取得
    $stmt29 = $pdo->prepare 
    ("SELECT fixed FROM kddi WHERE plot_date_time = '00:00:00'");
    $status = $stmt29->execute();
    if($row29 = $stmt29 -> fetch()){
        $fixed_kddi = $row29['fixed'] * ($ampere/10);
        }

    // 従量料金単価（１段目）取得
    $stmt30 =$pdo->prepare 
    ("SELECT var_s1 FROM kddi WHERE plot_date_time = '00:00:00'");
    $status = $stmt30->execute();
    if($row30 = $stmt30 -> fetch()){
        $var_s1_kddi = $row30['var_s1'];
        }

    //従量料金単価（2段目）取得
    $stmt31 =$pdo->prepare 
    ("SELECT var_s2 FROM kddi WHERE plot_date_time = '00:00:00' ");
    $status = $stmt31->execute();
    if($row31 = $stmt31 -> fetch()){
        $var_s2_kddi = $row31['var_s2'];
        }

    //従量料金単価（3段目）取得
    $stmt32 =$pdo->prepare 
    ("SELECT var_s3 FROM kddi WHERE plot_date_time = '00:00:00' ");
    $status = $stmt32->execute();
    if($row32 = $stmt32 -> fetch()){
        $var_s3_kddi = $row32['var_s3'];
        }

    // 先月の電気代取得
    if ($wh_last_month < 120) {
        $last_month_bill_kddi = round($fixed_kddi + $wh_last_month * $var_s1_kddi + $re*$wh_last_month);
    } else if ($wh_last_month < 300){
        $last_month_bill_kddi = round($fixed_kddi + 120 * $var_s1_kddi + ($wh_last_month-120) * $var_s2_kddi + $re*$wh_last_month);
    } else {
        $last_month_bill_kddi = round($fixed_kddi + 120 * $var_s1_kddi + (300 - 120) *$var_s2_kddi +($wh_last_month-300) * $var_s3_kddi + $re*$wh_last_month);
    }

    // 2か月前の電気代取得
    if ($wh_two_month_before < 120) {
        $bill_two_month_before_kddi = round($fixed_kddi + $wh_two_month_before * $var_s1_kddi+ $re*$wh_two_month_before );
    } else if ($wh_two_month_before < 300){
        $bill_two_month_before_kddi = round($fixed_kddi + 120 * $var_s1_kddi + ($wh_two_month_before-120) * $var_s2_kddi+ $re*$wh_two_month_before );
    } else {
        $bill_two_month_before_kddi = round($fixed_kddi + 120 * $var_s1_kddi + (300 - 120) *$var_s2_kddi +($wh_two_month_before-300) * $var_s3_kddi+ $re*$wh_two_month_before );
    }    

    // 3か月前の電気代取得
    if ($wh_three_month_before < 120) {
        $bill_three_month_before_kddi = round($fixed_kddi + $wh_three_month_before * $var_s1_kddi + $re*$wh_three_month_before);
    } else if ($wh_three_month_before < 300){
        $bill_three_month_before_kddi = round($fixed_kddi + 120 * $var_s1_kddi + ($wh_three_month_before-120) * $var_s2_kddi + $re*$wh_three_month_before);
    } else {
        $bill_three_month_before_kddi = round($fixed_kddi + 120 * $var_s1_kddi + (300 - 120) *$var_s2_kddi +($wh_three_month_before-300) * $var_s3_kddi + $re*$wh_three_month_before);
    } 

    //3か月の平均電気代
    $result_kddi = round(($last_month_bill_kddi + $bill_two_month_before_kddi + $bill_three_month_before_kddi)/3);

    //排出係数
    $stmt44 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = 'kddi' ");
    $status = $stmt44->execute();
    if($row44 = $stmt44 -> fetch()){
        $emission_rate_kddi = $row44['rate']*1000;
        }
    //３か月のCO2排出量計
    $emission_kddi = ($wh_last_month + $wh_two_month_before + $wh_three_month_before) * $emission_rate_kddi;

//Softbankでんき
    //基本料金取得
    $stmt33 = $pdo->prepare 
    ("SELECT fixed FROM softbank WHERE plot_date_time = '00:00:00'");
    $status = $stmt33->execute();
    if($row33 = $stmt33 -> fetch()){
        $fixed_softbank = $row33['fixed'] * ($ampere/10);
        }

    // 従量料金単価（１段目）取得
    $stmt34 =$pdo->prepare 
    ("SELECT var_s1 FROM softbank WHERE plot_date_time = '00:00:00'");
    $status = $stmt34->execute();
    if($row34 = $stmt34 -> fetch()){
        $var_s1_softbank = $row34['var_s1'];
        }

    //従量料金単価（2段目）取得
    $stmt35 =$pdo->prepare 
    ("SELECT var_s2 FROM softbank WHERE plot_date_time = '00:00:00' ");
    $status = $stmt35->execute();
    if($row35 = $stmt35 -> fetch()){
        $var_s2_softbank = $row35['var_s2'];
        }

    //従量料金単価（3段目）取得
    $stmt36 =$pdo->prepare 
    ("SELECT var_s3 FROM softbank WHERE plot_date_time = '00:00:00' ");
    $status = $stmt36->execute();
    if($row36 = $stmt36 -> fetch()){
        $var_s3_softbank = $row36['var_s3'];
        }

    // 先月の電気代取得
    if ($wh_last_month < 120) {
        $last_month_bill_softbank = round($fixed_softbank + $wh_last_month * $var_s1_softbank + $re*$wh_last_month);
    } else if ($wh_last_month < 300){
        $last_month_bill_softbank = round($fixed_softbank + 120 * $var_s1_softbank + ($wh_last_month-120) * $var_s2_softbank + $re*$wh_last_month);
    } else {
        $last_month_bill_softbank = round($fixed_softbank + 120 * $var_s1_softbank + (300 - 120) *$var_s2_softbank +($wh_last_month-300) * $var_s3_softbank + $re*$wh_last_month);
    }

    // 2か月前の電気代取得
    if ($wh_two_month_before < 120) {
        $bill_two_month_before_softbank = round($fixed_softbank + $wh_two_month_before * $var_s1_softbank+ $re*$wh_two_month_before );
    } else if ($wh_two_month_before < 300){
        $bill_two_month_before_softbank = round($fixed_softbank + 120 * $var_s1_softbank + ($wh_two_month_before-120) * $var_s2_softbank+ $re*$wh_two_month_before );
    } else {
        $bill_two_month_before_softbank = round($fixed_softbank + 120 * $var_s1_softbank + (300 - 120) *$var_s2_softbank +($wh_two_month_before-300) * $var_s3_softbank+ $re*$wh_two_month_before );
    }    

    // 3か月前の電気代取得
    if ($wh_three_month_before < 120) {
        $bill_three_month_before_softbank = round($fixed_softbank + $wh_three_month_before * $var_s1_softbank + $re*$wh_three_month_before);
    } else if ($wh_three_month_before < 300){
        $bill_three_month_before_softbank = round($fixed_softbank + 120 * $var_s1_softbank + ($wh_three_month_before-120) * $var_s2_softbank + $re*$wh_three_month_before);
    } else {
        $bill_three_month_before_softbank = round($fixed_softbank + 120 * $var_s1_softbank + (300 - 120) *$var_s2_softbank +($wh_three_month_before-300) * $var_s3_softbank + $re*$wh_three_month_before);
    } 

    //3か月の平均電気代
    $result_softbank = round(($last_month_bill_softbank + $bill_two_month_before_softbank + $bill_three_month_before_softbank)/3);
    
    //排出係数
    $stmt45 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = 'softbank' ");
    $status = $stmt45->execute();
    if($row45 = $stmt45 -> fetch()){
        $emission_rate_softbank = $row45['rate']*1000;
        }
    //３か月のCO2排出量計
    $emission_softbank = ($wh_last_month + $wh_two_month_before + $wh_three_month_before) * $emission_rate_softbank;

//looop
    //基本料金取得
    $stmt37 = $pdo->prepare 
    ("SELECT fixed FROM looop WHERE plot_date_time = '00:00:00'");
    $status = $stmt37->execute();
    if($row37 = $stmt37 -> fetch()){
        $fixed_looop = $row37['fixed'] * ($ampere/10);
        }

    // 従量料金単価（１段目）取得
    $stmt38 =$pdo->prepare 
    ("SELECT var_s1 FROM looop WHERE plot_date_time = '00:00:00'");
    $status = $stmt38->execute();
    if($row38 = $stmt38 -> fetch()){
        $var_s1_looop = $row38['var_s1'];
        }

    //従量料金単価（2段目）取得
    $stmt39 =$pdo->prepare 
    ("SELECT var_s2 FROM looop WHERE plot_date_time = '00:00:00' ");
    $status = $stmt39->execute();
    if($row39 = $stmt39 -> fetch()){
        $var_s2_looop = $row39['var_s2'];
        }

    //従量料金単価（3段目）取得
    $stmt40 =$pdo->prepare 
    ("SELECT var_s3 FROM looop WHERE plot_date_time = '00:00:00' ");
    $status = $stmt40->execute();
    if($row40 = $stmt40 -> fetch()){
        $var_s3_looop = $row40['var_s3'];
        }

    // 先月の電気代取得
    if ($wh_last_month < 120) {
        $last_month_bill_looop = round($fixed_looop + $wh_last_month * $var_s1_looop + $re*$wh_last_month);
    } else if ($wh_last_month < 300){
        $last_month_bill_looop = round($fixed_looop + 120 * $var_s1_looop + ($wh_last_month-120) * $var_s2_looop + $re*$wh_last_month);
    } else {
        $last_month_bill_looop = round($fixed_looop + 120 * $var_s1_looop + (300 - 120) *$var_s2_looop +($wh_last_month-300) * $var_s3_looop + $re*$wh_last_month);
    }

    // 2か月前の電気代取得
    if ($wh_two_month_before < 120) {
        $bill_two_month_before_looop = round($fixed_looop + $wh_two_month_before * $var_s1_looop+ $re*$wh_two_month_before );
    } else if ($wh_two_month_before < 300){
        $bill_two_month_before_looop = round($fixed_looop + 120 * $var_s1_looop + ($wh_two_month_before-120) * $var_s2_looop+ $re*$wh_two_month_before );
    } else {
        $bill_two_month_before_looop = round($fixed_looop + 120 * $var_s1_looop + (300 - 120) *$var_s2_looop +($wh_two_month_before-300) * $var_s3_looop+ $re*$wh_two_month_before );
    }    

    // 3か月前の電気代取得
    if ($wh_three_month_before < 120) {
        $bill_three_month_before_looop = round($fixed_looop + $wh_three_month_before * $var_s1_looop + $re*$wh_three_month_before);
    } else if ($wh_three_month_before < 300){
        $bill_three_month_before_looop = round($fixed_looop + 120 * $var_s1_looop + ($wh_three_month_before-120) * $var_s2_looop + $re*$wh_three_month_before);
    } else {
        $bill_three_month_before_looop = round($fixed_looop + 120 * $var_s1_looop + (300 - 120) *$var_s2_looop +($wh_three_month_before-300) * $var_s3_looop + $re*$wh_three_month_before);
    } 

    //3か月の平均電気代
    $result_looop = round(($last_month_bill_looop + $bill_two_month_before_looop + $bill_three_month_before_looop)/3);

    //排出係数
    $stmt46 =$pdo->prepare ("SELECT rate FROM emission WHERE plan = 'looop' ");
    $status = $stmt46->execute();
    if($row46 = $stmt46 -> fetch()){
        $emission_rate_looop = $row46['rate']*1000;
        }
    //３か月のCO2排出量計
    $emission_looop = ($wh_last_month + $wh_two_month_before + $wh_three_month_before) * $emission_rate_looop;


    //比較結果
    $cheapest_result =min($result_tepco_standard, $result_tokyogas, $result_mcre, $result_kddi, $result_softbank, $result_looop);
    
    //eco比較結果
    $eco_result =min($emission_tepco_standard, $emission_tokyogas, $emission_mcre, $emission_kddi, $emission_softbank, $emission_looop);


    //電気代・電気メニュー名確認
    switch ($cheapest_result) {
        case $result_tepco_standard:
            $cheapest_result = "東京電力";
            break;
        case $result_tokyogas:
            $cheapest_result = "東京ガス";
            break;
        case $result_mcre:
            $cheapest_result = "まちエネ";
            break;
        case $result_kddi:
            $cheapest_result = "auでんき";
            break;
        case $result_softbank:
            $cheapest_result = "Softbankでんき";
            break;
        case $result_looop:
            $cheapest_result = "Looop";
            break;
    }

    //Eco・電気メニュー名確認
    switch ($eco_result) {
        case $emission_tepco_standard:
            $eco_result = "東京電力";
            break;
        case $emission_tokyogas:
            $eco_result = "東京ガス";
            break;
        case $emission_mcre:
            $eco_result = "まちエネ";
            break;
        case $emission_kddi:
            $eco_result = "auでんき";
            break;
        case $emission_softbank:
            $eco_result = "Softbankでんき";
            break;
        case $emission_looop:
            $eco_result = "Looop";
            break;
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

    <div class ="bill-outer">
        
        <canvas id="bill-chart" height="150" width="200"></canvas>
        <div class="bill-wrpper">
        <h2>最近のでんきの使い方は？</h2>
            <p class ="month2-before"><span id="three_month_before"></span>の電気料金：<?=  $bill_three_month_before ?> 円</p>
            <p class ="month2-before"><span id="two_month_before"></span>の電気料金：<?= $bill_two_month_before ?> 円</p>
            <p class ="month2-before"><span id="one_month_before"></span>の電気料金：<?= $last_month_bill ?> 円</p>
         <!-- ひとことアドバイス -->
        <div class="message-wrapper">
            <img src="img/advice.png" alt="advice" width ="300px">
            <div class ="recommend">最近の使い方から、<br> おすすめの電気料金メニューは        
            <span class="outstand"><?=  $cheapest_result ?></span>です
            <!-- 比較詳細へ。POST通信でデータ移管 -->  
            <form method="POST" action="comparison.php">
                <p><input type="hidden" name ="tepco_standard1" value= "<?= $last_month_bill_tepco_standard ?>"></p>
                <p><input type="hidden" name ="tepco_standard2" value= "<?= $bill_two_month_before_tepco_standard ?>"></p>
                <p><input type="hidden" name ="tepco_standard3" value= "<?= $bill_three_month_before_tepco_standard ?>"></p>
                <p><input type="hidden" name ="tokyogas1" value= "<?= $last_month_bill_tokyogas ?>"></p>
                <p><input type="hidden" name ="tokyogas2" value= "<?= $bill_two_month_before_tokyogas ?>"></p>
                <p><input type="hidden" name ="tokyogas3" value= "<?= $bill_three_month_before_tokyogas ?>"></p>
                <p><input type="hidden" name ="mcre1" value= "<?= $last_month_bill_mcre ?>"></p>
                <p><input type="hidden" name ="mcre2" value= "<?= $bill_two_month_before_mcre ?>"></p>
                <p><input type="hidden" name ="mcre3" value= "<?= $bill_three_month_before_mcre ?>"></p>
                <p><input type="hidden" name ="kddi1" value= "<?= $last_month_bill_kddi ?>"></p>
                <p><input type="hidden" name ="kddi2" value= "<?= $bill_two_month_before_kddi ?>"></p>
                <p><input type="hidden" name ="kddi3" value= "<?= $bill_three_month_before_kddi ?>"></p>
                <p><input type="hidden" name ="softbank1" value= "<?= $last_month_bill_softbank ?>"></p>
                <p><input type="hidden" name ="softbank2" value= "<?= $bill_two_month_before_softbank ?>"></p>
                <p><input type="hidden" name ="softbank3" value= "<?= $bill_three_month_before_softbank ?>"></p>
                <p><input type="hidden" name ="looop1" value= "<?= $last_month_bill_looop ?>"></p>
                <p><input type="hidden" name ="looop2" value= "<?= $bill_two_month_before_looop ?>"></p>
                <p><input type="hidden" name ="looop3" value= "<?= $bill_three_month_before_looop ?>"></p>
             <!-- エコデータ移管 -->  
                <p><input type="hidden" name ="emission" value= "<?=  $eco_result ?>"></p>
                <p class="centering"><input type="submit" id="submit" value="詳細"></p>
            </form>

            <div class ="recommend">
                契約アンペアは適切でしょうか？確認してみましょう。
                <p id="recent"><a href="weekly_summary.php">詳細</a></p>
            </div>
        </div>   


        </div>
    </div>
    
    <!-- <div class="bill-wrapper">
            <table>
                    <tr>
                        <th>3か月前の電気料金</th>   
                    </tr>
                    <tr>
                        <td><?=  $bill_three_month_before ?> 円</td>
                    </tr>
            </table>
        <div class ="arrow"><img src="img/arrow.png" alt="arrow"></div>
            <table>
                <tr>
                    <th>2か月前の電気料金</th>   
                </tr>
                <tr>
                    <td> <?= $bill_two_month_before ?> 円</td>
                </tr>
            </table>
        <div class ="arrow"><img src="img/arrow.png" alt="arrow"></div>
            <table>
                    <tr>
                        <th>先月の電気料金</th>   
                    </tr>
                    <tr>
                        <td><?= $last_month_bill ?> 円</td>
                    </tr>
            </table>
    </div> -->

            

<!-- <p class="return"><a href="index.php">トップに戻る</a></p> -->

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

//月表示
$("#one_month_before").html(one_month_before); 
$("#two_month_before").html(two_month_before); 
$("#three_month_before").html(three_month_before); 

//CO2排出量メッセージ
let emission_comparison = <?= $emission_comparison ?>;
let emission_last_month = <?= $emission_last_month ?>;
let emission_two_month_before = <?= $emission_two_month_before ?>;
let tree = emission_comparison/14;

if (emission_last_month < emission_two_month_before){
   $("#emission").html ("先月はCO2排出量が"+emission_comparison +"トン減りました！なんと杉の木"+tree+"本が１年間に吸収する量と同じ！");
} else {
    $("#emission").html ("先月はCO2排出量が"+emission_comparison +"トン増えてしまいました・・・");
}

//Chart.jsで棒グラフを描く
jQuery (function ()
{const config = {
        type: 'bar',
        data: barChartData,
        responsive : true
        }

    const context = jQuery("#bill-chart")
    const chart = new Chart(context,config)
})

const barChartData = {
    labels : [three_month_before, two_month_before, one_month_before],
    datasets : [
        {
        label: "電気使用量(kWh)",
        backgroundColor: "rgba(60,179,113,0.5)",
        data : [<?= $wh_three_month_before ?>,<?= $wh_two_month_before ?>,<?= $wh_last_month_r ?>]
        },   
    ]
}

</script>

</body>
</html>
<?php
//Sessionスタート
session_start();

//関数を呼び出す
require_once('funcs.php');

//ログインチェック、電力メニューと契約アンペアを取得
// loginCheck();
$user_name = $_SESSION['name'];
$id = $_SESSION['id'];

//user_detail.phpで入力したPOSTデータを取得
$tepco_standard1 = $_POST["tepco_standard1"];
$tepco_standard2 = $_POST["tepco_standard2"];
$tepco_standard3 = $_POST["tepco_standard3"];
$tokyogas1 = $_POST["tokyogas1"];
$tokyogas2 = $_POST["tokyogas2"];
$tokyogas3 = $_POST["tokyogas3"];
$rakuten1 = $_POST["rakuten1"];
$rakuten2 = $_POST["rakuten2"];
$rakuten3 = $_POST["rakuten3"];
$kddi1 = $_POST["kddi1"];
$kddi2 = $_POST["kddi2"];
$kddi3 = $_POST["kddi3"];
$softbank1 = $_POST["softbank1"];
$softbank2 = $_POST["softbank2"];
$softbank3 = $_POST["softbank3"];
$looop1 = $_POST["looop1"];
$looop2 = $_POST["looop2"];
$looop3 = $_POST["looop3"];

?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <title>電気料金メニュー比較</title>
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

<table border =1>
        <tr>
            <th>月
                <th id="month1"> </th>
                <th id="month2"> </th>
                <th id="month3"> </th>
            </th>   
        </tr>
        <tr>
            <td>東京電力標準
                <td><?= $tepco_standard1 ?> 円</td>
                <td><?= $tepco_standard2 ?> 円</td>
                <td><?= $tepco_standard3 ?> 円</td>
            </td>
        </tr>
        <tr>
            <td>東京ガス
                <td><?= $tokyogas1 ?> 円</td>
                <td><?= $tokyogas2 ?> 円</td>
                <td><?= $tokyogas3 ?> 円</td>
            </td>
        </tr>
        <tr>
            <td>楽天でんき
                <td><?= $rakuten1 ?> 円</td>
                <td><?= $rakuten2 ?> 円</td>
                <td><?= $rakuten3 ?> 円</td>
            </td>
        </tr>
        <tr>
            <td>auでんき
                <td><?= $kddi1 ?> 円</td>
                <td><?= $kddi2 ?> 円</td>
                <td><?= $kddi3 ?> 円</td>
            </td>
        </tr>
        <tr>
            <td>Softbankでんき
                <td><?= $softbank1 ?> 円</td>
                <td><?= $softbank2 ?> 円</td>
                <td><?= $softbank3 ?> 円</td>
            </td>
        </tr>
        <tr>
            <td>looop
                <td><?= $looop1 ?> 円</td>
                <td><?= $looop2 ?> 円</td>
                <td><?= $looop3 ?> 円</td>
            </td>
        </tr>
        <tr>
            <th>一番安かったのは・・・
                <th id="cheapest_result1"> </th>
                <th id="cheapest_result2"> </th>
                <th id="cheapest_result3"> </th>
            </th>   
        </tr>
        <!-- <tr>
            <td>一番安かったのは・・・
                <td id="cheapest_result1"></td>
                <td id="cheapest_result2"></td>
                <td id="cheapest_result3"></td>
            </td>
        </tr> -->
    </table>

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

$("#month1").html(one_month_before);
$("#month2").html(two_month_before);
$("#month3").html(three_month_before);

//各月の最安値
let cheapest_result1 =Math.min(<?= $tepco_standard1 ?>, <?= $tokyogas1 ?>, <?= $rakuten1 ?>, <?= $kddi1 ?>, <?= $softbank1 ?>, <?= $looop1 ?>);
let cheapest_result2=Math.min(<?= $tepco_standard2 ?>, <?= $tokyogas2 ?>, <?= $rakuten2 ?>, <?= $kddi2 ?>, <?= $softbank2 ?>, <?= $looop2 ?>);
let cheapest_result3 =Math.min(<?= $tepco_standard3 ?>, <?= $tokyogas3 ?>, <?= $rakuten3 ?>, <?= $kddi3 ?>, <?= $softbank3 ?>, <?= $looop3 ?>);

//電気メニュー名確認
switch (cheapest_result1) {
        case <?= $tepco_standard1 ?>:
            cheapest_result1 = "東京電力";
            break;
        case <?= $tokyogas1 ?>:
            cheapest_result1 = "東京ガス";
            break;
        case <?= $rakuten1 ?>:
            cheapest_result1 = "楽天でんき";
            break;
        case <?= $kddi1 ?>:
            cheapest_result1 = "auでんき";
            break;
        case <?= $softbank1 ?>:
            cheapest_result1 = "Softbankでんき";
            break;
        case <?= $looop1 ?>:
            cheapest_result1 = "Looop";
            break;
    }
$("#cheapest_result1").html(cheapest_result1);

switch (cheapest_result1) {
        case <?= $tepco_standard1 ?>:
            cheapest_result1 = "東京電力";
            break;
        case <?= $tokyogas1 ?>:
            cheapest_result1 = "東京ガス";
            break;
        case <?= $rakuten1 ?>:
            cheapest_result1 = "楽天でんき";
            break;
        case <?= $kddi1 ?>:
            cheapest_result1 = "auでんき";
            break;
        case <?= $softbank1 ?>:
            cheapest_result1 = "Softbankでんき";
            break;
        case <?= $looop1 ?>:
            cheapest_result1 = "Looop";
            break;
    }
$("#cheapest_result1").html(cheapest_result1);

switch (cheapest_result2) {
        case <?= $tepco_standard2 ?>:
            cheapest_result2 = "東京電力";
            break;
        case <?= $tokyogas2 ?>:
            cheapest_result2 = "東京ガス";
            break;
        case <?= $rakuten2 ?>:
            cheapest_result2 = "楽天でんき";
            break;
        case <?= $kddi2 ?>:
            cheapest_result2 = "auでんき";
            break;
        case <?= $softbank2 ?>:
            cheapest_result2 = "Softbankでんき";
            break;
        case <?= $looop2 ?>:
            cheapest_result2 = "Looop";
            break;
    }
$("#cheapest_result2").html(cheapest_result2);

switch (cheapest_result3) {
        case <?= $tepco_standard3 ?>:
            cheapest_result3 = "東京電力";
            break;
        case <?= $tokyogas3 ?>:
            cheapest_result3 = "東京ガス";
            break;
        case <?= $rakuten3 ?>:
            cheapest_result3 = "楽天でんき";
            break;
        case <?= $kddi3 ?>:
            cheapest_result3 = "auでんき";
            break;
        case <?= $softbank3 ?>:
            cheapest_result3 = "Softbankでんき";
            break;
        case <?= $looop3 ?>:
            cheapest_result3 = "Looop";
            break;
    }
$("#cheapest_result3").html(cheapest_result3);

</script>

</body>
</html>
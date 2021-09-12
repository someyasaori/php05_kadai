<?php
// //Sessionスタート
session_start();

//外部ファイルから関数を読み込みDB接続（funcs.php）
require_once('funcs.php');
$pdo = db_conn();

// //ログインチェック
loginCheck();
$user_name = $_SESSION['name'];
$id = $_SESSION['id'];

//編集対象のIDをGET通信で取得
// $id = $_GET['id'];
// echo $id;

//同じIDのデータをSQL文でDBから取得
$stmt = $pdo->prepare("SELECT * FROM user_table WHERE id=:id ");
$stmt->bindValue(':id',$id,PDO::PARAM_INT);
$status = $stmt->execute();

//入力済みのデータを表示
$view ="";
if ($status == false) {
    sql_error($status);
} else {
    $result = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/user_style.css">
    <title>登録内容の修正</title>
</head>
<body>

<h1>登録内容の修正</h1>
<main>
<div class="login-outer">
    <div class ="tree-index">
        <img src="img/big_tree_r.png" alt="tree" width="500px">
    </div>
    <div class="login-wrapper">
        <h2>以下フォームに入力してください</h2>
        <form method="POST" action="user_update.php">
            <p class="righting">名前：<input type="text" name ="name" value = "<?=$result['name']?>" id="name"></p>
            <p class="righting">ID：<input type="text" name="lid" value = "<?=$result['lid']?>" id="lid"></p>
            <p class="righting">パスワード：<input type="text" name="lpw"></p>
            
            <!-- <p class="kanri">
                <input type="hidden" name="kanri_flg" value = "0" <?php if($result['kanri_flg'] == '0') echo 'checked="checked"'?> id="kanri_flg">
                <input type="hidden" name="kanri_flg" value="1" <?php if($result['kanri_flg'] == '1') echo 'checked="checked"'?> >
            </p>
            <p class="life">
                <input type="hidden" name="life_flg" value="0" <?php if($result['life_flg'] == '0') echo 'checked="checked"'?> id="life_flg">
                <input type="hidden" name="life_flg" value="1" <?php if($result['life_flg'] == '1') echo 'checked="checked"'?> >
            </p> -->
            <p class="righting">電力メニュー：
                <select name ="plan" id="plan" value = "<?=$result['plan']?>">
                    <option value="tepco_standard">東京電力標準</option>
                    <option value="tepco_night8">東京電力夜トク</option>
                    <option value="kddi">auでんき</option>
                    <option value="softbank">ソフトバンクでんき</option>
                    <option value="tokyogas">東京ガス</option>
                    <option value="looop">looopでんき</option>
                    <option value="eneos">ENEOSでんき</option>
                    <option value="mcre">まちエネ</option>
                </select>
            <p class="righting">契約アンペア：<input type="text" name="ampere" value = "<?=$result['ampere']?>" id="ampere"></p>
            <p class="righting">データ粒度：
                <select name ="polling" id="polling" value = "<?=$result['polling']?>">
                    <option value="30min">30分値</option>
                    <option value="1min">1分値</option>
                </select>
            </p>
            <p><input type="hidden" name ="id" value= "<?=$result['id']?>"></p>
            <p class="righting"><input type="submit" id="submit" value="登録"></p>
        </form>
        <p class="all">
            <!-- <a href="user_select.php">登録済みユーザーを表示（編集・削除もこちら）</a>
            <br>
            <a href="user_index.php">新規登録画面</a> -->
            <a href="index.php">目次に戻る</a>
        </p>
    </div>
</div>
    <!-- <div class ="tree">
            <img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree">
    </div> -->
</main>
</body>
</html>

<?php

//外部ファイルから関数を読み込みDB接続（funcs.php）
require_once('funcs.php');
$pdo = db_conn();

//編集対象のIDをGET通信で取得
$id = $_GET['id'];
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
    <title>ユーザー管理</title>
</head>
<body>

<h1>ユーザー管理画面</h1>
<main>
    <h2>既存ユーザー編集</h2>
    <form method="POST" action="user_update.php">
        <p class="centering">名前：<input type="text" name ="name" value = "<?=$result['name']?>" id="name"></p>
        <p class="centering">ログインID：<input type="text" name="lid" value = "<?=$result['lid']?>" id="lid"></p>
        <p class="centering">ログインPW：<input type="text" name="lpw" value = "<?=$result['lpw']?>" id="lpw"></p>
        
        <p class="kanri">
            一般<input type="radio" name="kanri_flg" value = "0" <?php if($result['kanri_flg'] == '0') echo 'checked="checked"'?> id="kanri_flg">
            管理者<input type="radio" name="kanri_flg" value="1" <?php if($result['kanri_flg'] == '1') echo 'checked="checked"'?> >
        </p>
        <p class="life">
            退会<input type="radio" name="life_flg" value="0" <?php if($result['life_flg'] == '0') echo 'checked="checked"'?> id="life_flg">
            入会<input type="radio" name="life_flg" value="1" <?php if($result['life_flg'] == '1') echo 'checked="checked"'?> >
        </p>
        <p><input type="hidden" name ="id" value= "<?=$result['id']?>"></p>
        <p class="centering"><input type="submit" id="submit" value="登録"></p>
    </form>
<p class="all">
    <a href="user_select.php">登録済みユーザーを表示（編集・削除もこちら）</a>
    <br>
    <a href="user_index.php">新規登録画面</a>
    <a href="index.php">目次に戻る</a>
</p>

</main>
</body>
</html>

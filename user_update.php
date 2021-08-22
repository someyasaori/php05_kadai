<?php

//user_detail.phpで入力したPOSTデータを取得
$name = $_POST["name"];
$lid = $_POST["lid"];
$lpw = $_POST["lpw"];
$hlpw = password_hash($lpw, PASSWORD_DEFAULT);//ハッシュ化
$kanri_flg = $_POST["kanri_flg"];
$life_flg = $_POST["life_flg"];
$id = $_POST["id"];

//DB接続（mysql）
require_once('funcs.php');
$pdo = db_conn();

//データ更新SQL作成
$stmt = $pdo->prepare("UPDATE user_table SET name = :name, lid = :lid, lpw = :lpw, kanri_flg= :kanri_flg, life_flg = :life_flg WHERE id = :id;" );

//バインド変数
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$stmt->bindValue(':lpw', $hlpw, PDO::PARAM_STR);
$stmt->bindValue(':kanri_flg', $kanri_flg, PDO::PARAM_INT);
$stmt->bindValue(':life_flg', $life_flg, PDO::PARAM_INT);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
// 文字の場合 PDO::PARAM_STR、数値の場合 PDO::PARAM_INT

// 実行
$status = $stmt->execute();

//データ編集処理後
if ($status == false) {
    sql_error($stmt);
} else {
    redirect('user_select.php');
}


?>
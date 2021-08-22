<?php
//まずセッションを開始
session_start();

//ログイン用入力情報取得
$lid = $_POST['lid'];
$lpw = $_POST['lpw'];

//DB接続
require_once('funcs.php');
$pdo = db_conn();

//SQLで登録済みデータのうちIDが一致するものを探す
$stmt = $pdo->prepare("SELECT * FROM user_table WHERE lid = :lid");
$stmt->bindValue(':lid',$lid, PDO::PARAM_STR);
$status = $stmt->execute();

//SQL実行時エラーがある場合（Funcs.phpの関数を利用）
if($status==false){
    sql_error($stmt);
}

//IDが一致したデータを取得（IDが一致したもののみなのでWhileは不要）
$val = $stmt->fetch();

//該当するデータを取得出来たらセッションに値を代入
if( password_verify($lpw, $val['lpw'])){
    //Login成功時
    $_SESSION['chk_ssid']  = session_id();//SESSION変数にidを保存
    $_SESSION['kanri_flg'] = $val['kanri_flg'];//SESSION変数に管理者権限のflagを保存
    $_SESSION['name']      = $val['name'];//SESSION変数にnameを保存
    $_SESSION['id']        = $val['id'];//SESSION変数に自動付与のidを保存（電気データ呼び出し用）
    redirect('index.php');
  }else{
    //Login失敗時（失敗時はselect.phpの資料へのリンクがないバージョンを別途用意）
    redirect('login.php');
}

exit();

?>
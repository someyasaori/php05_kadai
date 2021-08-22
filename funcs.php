<?php

//XSS対応（Echoする場所で使用）
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

//DB接続関数。関数を作成し内容をReturnする。DB Nameを適宜修正。
function db_conn(){
    try {
        $db_name = "power_db";    //データベース名
        $db_id   = "root";      //アカウント名
        $db_pw   = "root";      //パスワード：XAMPPはパスワード無しに修正してください。
        $db_host = "localhost"; //DBホスト
        $pdo = new PDO('mysql:dbname=' . $db_name . ';charset=utf8;host=' . $db_host, $db_id, $db_pw);
    return $pdo; //一行追記 
    } catch (PDOException $e) {
      exit('DBConnectError:'.$e->getMessage());
      }

}

//電気料金メニューDBへ接続する関数
function db_conn2(){
    try {
        $db_name = "tepco";    //データベース名
        $db_id   = "root";      //アカウント名
        $db_pw   = "root";      //パスワード：XAMPPはパスワード無しに修正してください。
        $db_host = "localhost"; //DBホスト
        $pdo = new PDO('mysql:dbname=' . $db_name . ';charset=utf8;host=' . $db_host, $db_id, $db_pw);
    return $pdo; //一行追記 
    } catch (PDOException $e) {
      exit('DBConnectError:'.$e->getMessage());
      }

}

//SQLエラー関数：sql_error($stmt)
function sql_error($stmt){
    $error = $stmt->errorInfo();
    exit("SQLError:" . print_r($error, true));
}

//リダイレクト関数: redirect($file_name)
function redirect($file_name){
    header("Location: ".$file_name);//「 .」は次に処理が続くの意味 
    exit();
}

//ログインチェック(サーバ側のIDとSessionでブラウザ上に保存しているIDが一致しているか。一致したユーザーには新しいIDを発行し、サーバ側のセッション変数にも保存。)
function loginCheck(){
    if( $_SESSION["chk_ssid"] != session_id() ){
      exit('ログインしてください');
    }else{
      session_regenerate_id(true);
      $_SESSION['chk_ssid'] = session_id();
    }
  }
  
?>
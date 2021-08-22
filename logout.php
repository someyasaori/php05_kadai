<?php
//まずセッション開始
session_start();

//セッションを空っぽにする（初期化）
$_SESSION = array();

//Cookieに保存してあるSession IDの保存期間を過去にする
if(isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

//サーバ側でのセッションIDの破棄
session_destroy();

//上記処理後index.phpへリダイレクト
header("Location: index.php");
exit();


?>
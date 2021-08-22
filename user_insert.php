<?php
//Sessionスタート
session_start();

//関数を呼び出す
require_once('funcs.php');

//ログインチェック
// loginCheck();
$user_name = $_SESSION['name'];
$kanri = $_SESSION['kanri_flg'];

//POSTデータを取得
$name = $_POST["name"];
$lid = $_POST["lid"];
$lpw = $_POST["lpw"];
$hlpw = password_hash($lpw, PASSWORD_DEFAULT);//ハッシュ化
$kanri_flg = $_POST["kanri_flg"];
$life_flg = $_POST["life_flg"];

//DB接続
// try {
//     $pdo = new PDO('mysql:dbname=gs_db;charset=utf8;host=localhost','root','root');
//   } catch (PDOException $e) {
//     exit('DBConnectError:'.$e->getMessage());
//   }
//管理者のみが新規ユーザーを登録できる
if ($kanri == "1") {

    require_once('funcs.php');
    $pdo = db_conn();

//データ登録（SQL文）
$stmt = $pdo->prepare(
    "INSERT INTO user_table(id,name,lid,lpw,kanri_flg,life_flg)
    VALUES(NULL, :name, :lid, :lpw, :kanri_flg, :life_flg)"
);

//バインド変数
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$stmt->bindValue(':lpw', $hlpw, PDO::PARAM_STR);
$stmt->bindValue(':kanri_flg', $kanri_flg, PDO::PARAM_INT);
$stmt->bindValue(':life_flg', $life_flg, PDO::PARAM_INT);

//登録実行
$status = $stmt->execute();

//登録後エラー有無チェック、なければindex.phpへリダイレクト
if($status==false){
    $error = $stmt->errorInfo();
}else{header('Location: user_index.php');

}

} else {
    exit ("管理者としてログインしてください");
}

?>
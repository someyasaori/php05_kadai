<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/user_style.css">
    <title>ログイン</title>
</head>
<body>

<h1>でんきの使い方診断アプリ
<br>    
～あなたのでんきの使い方は？～</h1>
<div class="login-outer">
    <div class ="tree-index">
            <img src="img/big_tree_r.png" alt="tree" width="500px">
    </div>
    <div class="login-wrapper">
    <h2>ログインしてください</h2>
        <form method="POST" action="login_act.php">
            <p class="righting">ID：<input type="text" name="lid" id="lid"></p>
            <p class="righting">パスワード：<input type="text" name="lpw" id="lpw"></p>
            <p class="righting"><input type="submit" id="submit" value="ログイン"></p>
        </form>
    </div>
</div>   

</body>
</html>
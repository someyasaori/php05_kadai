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

<h1>ログイン画面</h1>

    <form method="POST" action="login_act.php">
        <p class="centering">ID：<input type="text" name="lid" id="lid"></p>
        <p class="centering">パスワード：<input type="text" name="lpw" id="lpw"></p>
        <p class="centering"><input type="submit" id="submit" value="ログイン"></p>
    </form>
    <div class ="tree">
        <img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree">
    </div>

</body>
</html>
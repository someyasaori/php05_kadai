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

<header>
    <nav class="header-wrapper">
        <ul class="inner">
            <li><a href="user_index.php"><a href="index.php">トップページに戻る</a></a></li>
            <li><a href="select_limited.php"><a href="user_select.php">登録済みユーザーを表示</a></a></li>
            <li><a href="logout.php">ログアウト</a></li>
            <li><a href="login.php">ログイン</a></li>
        </ul>
    </nav>
</header>

<h1>新規ユーザー登録</h1>
<main>
<div class="login-outer">
    <div class ="tree-index">
        <img src="img/planet.png" alt="tree" width="500px">
    </div>
    <div class="login-wrapper">
        <h2>以下フォームに入力してください</h2>
            <form method="POST" action="user_insert.php">
                <p class="righting">名前：<input type="text" name ="name" id="name"></p>
                <p class="righting">ID：<input type="text" name="lid" id="lid"></p>
                <p class="righting">パスワード：<input type="text" name="lpw" id="lpw"></p>
                <!-- <p class="kanri righting">
                    一般：<input type="radio" name="kanri_flg" value="0" id="kanri_flg">
                    管理者：<input type="radio" name="kanri_flg" value="1" >
                </p> -->
                <!-- <p class="life righting">
                    退会：<input type="radio" name="life_flg" value="0" id="life_flg">
                    入会：<input type="radio" name="life_flg" value="1" >
                </p> -->
                <p class="righting">電力メニュー：
                    <select name ="plan" id="plan" >
                        <option value="tepco_standard">東京電力標準</option>
                        <option value="tepco_night8">東京電力夜トク</option>
                        <option value="kddi">auでんき</option>
                        <option value="softbank">ソフトバンクでんき</option>
                        <option value="tokyogas">東京ガス</option>
                        <option value="looop">looopでんき</option>
                        <option value="eneos">ENEOSでんき</option>
                        <option value="mcre">まちエネ</option>
                    </select>
                </p>
                <p class="righting">契約アンペア：<input type="text" name="ampere" id="ampere"></p>
                <p class="righting mesh">データ粒度：
                    <select name ="polling" id="polling">
                        <option value="30min">30分値</option>
                        <option value="1min">1分値</option>
                    </select>
                </p>
                <p class="righting"><input type="submit" id="submit" value="登録"></p>
            </form>
    </div>
</div>
    <!-- <div class ="tree">
        <img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree">
    </div> -->

</main>
</body>
</html>
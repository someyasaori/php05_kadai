<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>お役立ちリンク集</title>
</head>
<body>

<header>
    <nav class="header-wrapper">
        <ul class="inner">
            <li><a href="user_index.php">ユーザー管理画面（管理者のみ）</a></li>
            <li><a href="select_limited.php">ログインせずにリスト概要を見る</a></li>
            <li><a href="logout.php">ログアウト</a></li>
            <li><a href="login.php">ログイン</a></li>
        </ul>
    </nav>
</header>

<h1>かしこく電気を使おう 目次</h1>
<!-- <main> -->
<!-- <div class="sub">
    <h2>新しい資料を登録</h2>
    <form method="POST" action="insert.php">
        <p>タイトル<input type="text" name="title" id="title" size ="15"></p>
        <p>URL<input type="text" name="url" id="url" size ="30"></p>
        <p>詳細<input type="text" name="details" id="details" size ="30"></p>
        <p>タグ
        <select name="tag" id="tag">
            <option value="VPP">VPP</option>
            <option value="再エネ">再エネ</option>
            <option value="リソース">リソース</option>
        </select></p>
        <p><input type="submit" id="submit" value ="登録"></p>
    </form>
    
</div> -->

<!-- <div class="sub"> 以下AJAX無しVer -->
<h2>月別データを検索</h2>
<form method ="POST" action="select_limited.php">
    <p class="date-wrapper">確認したい月を選ぶ（工事中）
        <select id="year" name="year"></select>
        <select id="month" name="month"></select>
    </p>
    <p id="submit-btn"><input type="submit" name="submit" id="submit" value="表示"></p>
</form>

<!-- <div class="sub"> 以下AJAXありVer -->
<!-- <form>
    <p class="date-wrapper">確認したい月を選ぶ 
        <select id="year" name="year"></select>
        <select id="month" name="month"></select>
    </p>
    <button id="btn">表示</button>
</form> -->


<h2>直近3か月の電気使用料を比較</h2>
<p id="recent"><a href="select.php">表示</a></p>
<!-- <div id="view"></div> -->

<!-- </div> -->
<!-- </main> -->

<!-- JQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<!-- JQuery -->

<script>
// 日付入力
function ymd(start,end,id){//まずパッケージにする、引数とする要素を探す
let y = ""; //例) y += '<option value="'+i+'">'+i+'</option>';
    for(let i=start; i<=end; i++){
        y += '<option value="'+i+'">'+i+'</option>';
    }
    $(id).html(y);
}
ymd(2020, 2030, "#year");
ymd(1, 12, "#month");
ymd(1, 31, "#date");


//（未実装/AJAXが使いこなせず…）登録ボタンをクリック。Formの提出ではなくクリックイベントでAjax処理が行われる。
$("#btn").on("click",function() {
            //Ajax送信開始
            $.ajax({
                type: "POST",
                url: "select.php",
                dataType: "html",
                data: {
                    year: $("#year").val(),
                    month: $("#month").val(),
                },
                
                //通信成功時にsuccess内が実行される！Timeoutなし。遷移せずに登録→”登録成功しました”と出る。Google検索窓の提案は「フォームに何かしら入力されたら検索結果を返す」というAjaxが入っている。他にHoverしたら、など。
                success: function(data) {
                  if(data=="false"){
                    alert("エラー");
                  }else{
                    $("#year").val("");
                    $("#month").val("");
                    $("#view").html(data);
                  }
                }
            });

        });
        
        //      }).done(function(data){
        //         var json = JSON.parse( data );
        //         console.log(json);
        //      }).fail(function(XMLHttpRequest, status, e){
        //      alert(e);
        //      });
        // });
                
                
        //         success: function(data) {
        //           if(data=="false"){
        //             alert("エラー");
        //           }else{
        //             $("#name").val("");
        //             $("#email").val("");
        //           }
        //         }
        //     });

        // });


</script>

</body>
</html>
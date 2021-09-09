<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>でんきアプリ</title>
</head>
<body>

<header>
    <nav class="header-wrapper">
        <ul class="inner">
            <li><a href="user_detail_byuser.php">アカウント登録内容を修正</a></li>
            <li><a href="user_index.php">新規アカウント登録</a></li>
            <li><a href="logout.php">ログアウト</a></li>
            <li><a href="login.php">ログイン</a></li>
        </ul>
    </nav>
</header>

<h1>あなたのでんきの使い方は？</h1>

<!-- <div class="sub"> 以下AJAX無しVer -->
<h2>今月のでんきの使い方を見る</h2>
<!-- 今月の累積使用量、今月の累積電気料金を表示するページに飛ぶ -->
<p id="recent"><a href="1month_summary.php">表示</a></p>

<h2>月別データを検索</h2>
<form method ="POST" action="select_month.php">
    <p class="date-wrapper">確認したい月を選ぶ
        <select id="year" name="year"></select>
        <select id="month" name="month"></select>
    </p>
    <p id="submit-btn"><input type="submit" name="submit" id="submit" value="表示"></p>
</form>


<h2>直近3か月のでんきの使い方を見る</h2>
<p id="recent"><a href="3months_summary.php">表示</a></p>

<p class ="admin"><a href="user_index.php">ユーザー管理画面（管理者のみ）</a></p>
<div class ="tree">
<img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree"><img src="img/tree.png" alt="tree">
</div>

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
<?php
session_start();

$sessionName = 'survey';

$DB_PATH = './db/database.json';                                        // データベースの代わりとなるjsonのパス
$database = json_decode(file_get_contents($DB_PATH), true);    // データメース読み込み

$user_id = "";
$user_data = "";
$remainder = 0;

// Sessionセットアップ
if (!isset($_SESSION[$sessionName])) {
    $_SESSION[$sessionName] = array();
}

// 関数の巣窟

function alert($text)
{
    echo "<script type='text/javascript'>alert('$text');</script>";
}

function delete($target, $key)
{
    $array = $target;
    $i = 0;
    foreach ($array as $item) {
        if ($item == $key) {
            array_splice($array, $i, 1);
            return $array;
        }
        $i ++;
    }

    return null;
}

function reload()
{
    header('Location: ./');
}

function error($code)
{
    header('Location: /?error=' . $code);
}

// メイン

$login = false;

if (isset($_SESSION[$sessionName]['user'])) {
    $user = $_SESSION[$sessionName]['user'];
    $user_id = $user['id'];
    $user_data = $database['user'][$user_id];

    if (isset($database['user'][$user_id])) {
        $login = true;
        $remainder = 50 - count($user_data['surveys']);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];

    if ($type == "login") {                    // ログイン処理

        $isset = (                             // 変数に条件式を格納
            isset($_POST['id']) &&
            isset($_POST['password'])
        );

        if ($isset) {
            $id = $_POST['id'];
            $password = $_POST['password'];

            if (isset($database['user'][$id])) {                              // ユーザーが存在するなら
                if ($database['user'][$id]['password'] == $password) {        // パスワードがあっているなら
                    $_SESSION[$sessionName]['user'] = $database['user'][$id]; // sessionにユーザー情報を保存
                    reload();
                } else {
                    error('Password');
                }
            } else {
                error('UserNotFound');
            }
        } else {
            header('HTTP/1.1 402 Unauthorized');
        }



    } elseif ($type == "register") {           // 登録処理
        $isset = (                             // 条件式が長いので変数に格納
            isset($_POST['name']) &&
            isset($_POST['id']) &&
            isset($_POST['mail']) &&
            isset($_POST['password']) &&
            isset($_POST['token'])
        );

        if ($isset) {                          // すべてのパラメーターがそろっているか確認

            $name = $_POST['name'];
            $id = $_POST['id'];
            $mail = $_POST['mail'];
            $password = $_POST['password'];
            $token = $_POST['token'];

            if (!isset($database['user'][$id]) && in_array($token, $database['token'])) { // tokenが有効か確認
                // Tokenを削除
                $database['token'] = delete($database['token'], $token);

                $user_data = array(  // ユーザーオブジェクト作成
                    'name' => $name,
                    'id' => $id,
                    'mail' => $mail,
                    'password' => $password,
                    'surveys' => array()
                );

                $database['user'][$id] = $user_data;        // データベースに追加
                $_SESSION[$sessionName]['user'] = $user_data; // セッションに追加

                file_put_contents($DB_PATH, json_encode($database, JSON_UNESCAPED_UNICODE)); // データベース保存

                reload();
            } else {
                header('Location: ./?error=Unauthorized');
            }

        } else {                               // そろっていなかったら
            header('HTTP/1.1 402 Unauthorized');
        }


    } else {
        header('HTTP/1.0 402 Unauthorized');
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['error'])) {                    // エラーが出たとき
        switch ($_GET['error']) {
            case 'Unauthorized':
                alert('Tokenが有効ではありません');
                break;
            case 'Password':
                alert('パスワードがあっていません');
                break;
            case 'UserNotFound':
                alert('そのようなユーザーは存在しません');
                break;
            case 'Login':
                alert('ログインしてください');
                break;
            case 'Question-notFound':
                alert('選択肢を記入してください');
                break;
            case 'SurveyNotFound':
                alert('そのようなアンケートは存在しません');
                break;
            case 'PleaseDeleteSurvey':
                alert('作成しているアンケートが多すぎます. どれかアンケートを削除してください');
                break;
        }
    }

    if (isset($_GET['logout'])) {                  // ログアウト
        if ($_GET['logout'] == 'true') {
            unset($_SESSION[$sessionName]);
            reload();
        }
    }

} else {
    header('HTTP/1.1 405 Method Not Allowed');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tanahiro2010</title>
    <style>
        * {
            margin-top: 1%;
        }
        .title {
            text-align: center;
        }

        body {
            background-color: antiquewhite;
        }
        main {
            text-align: center;
            font-size: large;
        }
        a {
            color: black;
            text-decoration: dashed;
        }
        h2 + p {
            margin-top: 0px;
        }

        * {
            scroll-behavior: smooth;
            box-sizing: border-box;
        }

        body {
            scrollbar-width: 0px;
        }

        body::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body>
<h1 class="title">アンケート作成ツール</h1>
<main>
    <!-- 概要 -->
    <div class="info">
        <h2>概要</h2>
        <p>
            サービス名: アンケート作成サービス<br>
            概要: 登録時にお金を払うだけ最大50個アンケートを作成、公開することができます<br>
            登録料: 1500円<br>
        </p>
        <h3>お支払方法</h3>
        <p>
            まず、対応しているお支払方法はPayPal、Amazonギフト券、楽天ギフト券です<br><br>

            TwitterのDM、またはメールでtanahiro2010にこのアンケート作成ツールを使用したい旨を伝えてください<br>
            しばらくするとお支払方法を尋ねられると思うので上記3つのうちからどれかを選んでください<br><br>

            PayPalを選んだ場合は送金用のURLが送信されます<br>
            他の2つを選んだ場合は、ギフトコードを送信してください<br>
            これによりtokenが発行されます
        </p>
    </div>

    <?php if ($login) : ?>
        <!-- ダッシュボード -->
        <div class="menu">
            <h2>
                <a href="./create">
                    アンケート作成
                    <?php
                    echo "(残り$remainder 個作成可能)"
                    ?>
                </a><br>
                <a href="./show_answer">アンケート結果表示</a><br>
                <a href="./delete">アンケート削除</a>
            </h2>
        </div>
    <?php else: ?>
        <!-- ログインか登録-->
        <div class="signup">
            <h2>登録</h2>
            <form action="./" method="post">
                <label for="name">Name : </label>
                <input type="text" name="name" placeholder="Name" required><br>

                <label for="id">Id : </label>
                <input type="text" name="id" placeholder="ID" required><br>

                <label for="mail">Mail : </label>
                <input type="email" name="mail" placeholder="Mail address" required><br>

                <label for="password">Password : </label>
                <input type="password" name="password" placeholder="Password" required><br>

                <label for="token">Token : </label>
                <input type="text" name="token" placeholder="Token" required><br>

                <!-- Form setting -->
                <input type="hidden" name="type" value="register">

                <button type="submit">登録</button>
            </form>

            <form action="./" method="post">
                <h2>ログイン</h2>

                <label for="id">Id : </label>
                <input type="text" name="id" placeholder="Id" required><br>

                <label for="password">Password : </label>
                <input type="text" name="password" placeholder="Password" required><br>

                <!-- Form setting -->
                <input type="hidden" name="type" value="login">

                <button type="submit">ログイン</button>
            </form>
        </div>
    <?php endif; ?>
</main>
</body>
</html>

<?php
session_start();

$sessionName = 'survey';

$DB_PATH = '../db/database.json';                                        // データベースの代わりとなるjsonのパス
$database = json_decode(file_get_contents($DB_PATH), true);    // データメース読み込み

$user_id = "";

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

    if (isset($database['user'][$user_id])) {
        $login = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($login) {
        if (isset($_GET['survey_id'])) {
            $survey_id = $_GET['survey_id'];
            if (isset($database['survey'][$survey_id])) {
                unset($database['survey'][$survey_id]);
                $database['user'][$user_id]['surveys'] = delete($database['user'][$user_id]['surveys'], $survey_id);

                file_put_contents($DB_PATH, json_encode($database, JSON_PRETTY_PRINT));
                reload();
                alert('削除が完了しました');
            } else {
                alert('そのようなアンケートは存在しません');
            }
        }
    } else {
        header('HTTP/1.1 401 Unauthorized');
        error('Login');
    }
} else {
    header('HTTP/1.0 405 Method Not Allowed');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tanahiroSurvey</title>
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
<h1 class="title">
    <a href="/">tanahiroSurvey</a>
</h1>
<main>
    <div class="links">
        <h2>作成したアンケート</h2>
        <?php
        $user_data = $database['user'][$user_id];
        $user_surveys = $user_data['surveys'];
        echo '<h3>';
        foreach ($user_surveys as $survey_id) {
            $survey_data = $database['survey'][$survey_id];
            $title = $survey_data['title'];

            $answer_length = count($survey_data['answers']);

            echo "<a href='?survey_id=$survey_id'>$title (ID: $survey_id 回答件数: $answer_length)</a><br>";
        }
        echo '</h3>';
        ?>
    </div>
</main>
</body>
</html>

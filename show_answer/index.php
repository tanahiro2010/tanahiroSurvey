<?php
session_start();

$sessionName = 'survey';

$DB_PATH = "../db/database.json";
$database = json_decode(file_get_contents($DB_PATH), true);

// 変数初期化
$user_id = "";
$title = "";
$description = "";
$answers_div = "";
$survey_id = "";
$isSurvey = true;

// 関数の巣窟
function alert($text)
{
    echo "<script type='text/javascript'>alert('$text');</script>";
    return true;
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

function getLastKey($array)
{
    $length = count($array) - 1;
    $j = 0;
    foreach ($array as $key => $value) {
        if ($length == $j) {
            return $key;
        }
        $j ++;
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

function redirect($url)
{
    header('Location: ' . $url);
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
        if (isset($_GET['survey_id'])) { // idはアンケートのID
            $survey_id = $_GET['survey_id'];
            if (isset($database['survey'][$survey_id])) { // IDと合致するアンケートが存在する確認
                $survey_data = $database['survey'][$survey_id]; // 変数にアンケートを代入

                // 必要事項取得
                $title = $survey_data['title'];
                $description = $survey_data['description'];
                $answers_data = $survey_data['answers'];

                if (count($answers_data) > 0) { // アンケートに回答が来てるか確認
                    foreach ($answers_data as $answer) {
                        $answers_div .= '<fieldset><h3>';
                        foreach ($answer as $key => $value) {
                            $question_title = $value['title'];
                            $answer_text = $value['answer'];

                            $answers_div .= "質問 : $question_title<br>";
                            $answers_div .= "答え : $answer_text<br>";

                        }
                        $answers_div .= "</h3></fieldset>";
                    }
                } else {
                    $answers_div = '
                    <h1>回答が来ていません！！！</h1>
                    ';
                }
            } else {
                error('SurveyNotFount');
            }
        } else {
            $isSurvey = false;
        }
    } else {
        header('HTTP/1.1 401 Unauthorized');
        error('Login');
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
    <?php if ($isSurvey): ?>
        <div class="survey_info">
            <h2>
                <?php
                echo "<a href='../show?survey_id=$survey_id'>$title</a>"; // アンケートタイトル
                ?>
            </h2>
            <p>
                <?php
                echo $description; // アンケート概要
                ?>
            </p>
        </div>


        <div>
            <h2>回答</h2>
            <?php
            echo $answers_div; // 回答一覧表示
            ?>
        </div>
    <?php else: ?>
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
    <?php endif; ?>
</main>
</body>
</html>

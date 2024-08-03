<?php
session_start();

$DB_PATH = "../db/database.json";
$database = json_decode(file_get_contents($DB_PATH), true);

// 変数初期化
$title = "";
$description = "";
$survey_id = "";
$survey_div = "";
$isSurvey = true;

// 必要な関数たち
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
    exit;
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // アンケートの答え送信
    if (isset($_POST['survey_id'])) { // POSTデータの最重要事項確認
        $survey_id = $_POST['survey_id'];
        if (isset($database['survey'][$survey_id])) {
            $survey = $database['survey'][$survey_id];
            $survey_title = $survey['title'];
            $survey_questions = $survey['questions'];

            $questions_data = array();
            $answers_data = array();

            $mail_title = "$survey_title に回答されました.";
            $mail_content = "";

            foreach ($survey_questions as $question) {
                $question_id = $question['id'];

                $question_title = $question['title'];
                $question_answer = $_POST[$question_id];

                $data = array(
                    'title' => $question_title,
                    'type' => $question['type'],
                    'answer' => $question_answer,
                );

                $mail_content .= "質問: $question_title\n答え: $question_answer\n\n";

                $answers_data[$question_id] = $data;
            }
            $database['survey'][$survey_id]['answers'][] = $answers_data;

            file_put_contents($DB_PATH, json_encode($database, JSON_PRETTY_PRINT));

            // 作者のユーザーID取得
            $survey_data = $database['survey'][$survey_id];
            $user_id = $survey_data['creator'];

            // 作者の情報取得
            $user_data = $database['user'][$user_id];
            // メールアドレス取得
            $mail_address = $user_data['mail'];

            // メール送信
            mb_language('ja');
            mb_internal_encoding('utf-8');

            mb_send_mail($mail_address, $mail_title, $mail_content);

            redirect('./?answer=true');
        } else {
            // here
            header('HTTP/1.1 404 Unauthorized');
            error('SurveyNotFound');
        }
    } else {
        header('HTTP/1.1 401 Unauthorized');
        error('SurveyNotFound');
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    // アンケート表示

    if (isset($_GET['survey_id'])) {
        $survey_id = $_GET['survey_id'];
        if (isset($database['survey'][$survey_id])) {
            $survey = $database['survey'][$survey_id];
            $title = $survey['title'];
            $description = $survey['description'];

            foreach ($survey['questions'] as $question) {
                $survey_title = isset($question['title']) ? $question['title'] : "Untitled Question";
                $survey_type = isset($question['type']) ? $question['type'] : "text";
                $id = isset($question['id']) ? $question['id'] : "";
                $required = isset($question['required']) && $question['required'] == "true" ? "required" : "";

                $type_html = "";

                if ($survey_type == "text") {
                    $type_html = "
                    <input type='text' name='$id' $required";
                    if ($required == "true") {
                        $type_html .= " placeholder='必須'";
                    }
                    $type_html .= ">";

                } elseif ($survey_type == "select" && isset($question['options']) && is_array($question['options'])) {
                    if ($required == "true") {
                        $type_html .= "<label for='$id'>必須 : </label>";
                    }
                    $type_html .= "<select name='$id' $required>";
                    $type_html .= "<option value=''>選択してください</option>";
                    foreach ($question['options'] as $option) {
                        $type_html .= "<option value='$option'>$option</option>";
                    }
                    $type_html .= "</select>";

                } else {
                    $type_html = "<input type='text' name='$id' $required"; // デフォルトはテキスト入力
                    if ($required == "true") {
                        $type_html .= " placeholder='必須'";
                    }
                    $type_html .= ">";

                }

                $survey_div .= "
                <div class='question'>
                    <h3>$survey_title</h3>
                    $type_html
                </div>
                ";
            }
        } else {
            header('HTTP/1.0 404 Not Found');
            error('SurveyNotFound');
        }

    } elseif (isset($_GET['answer'])) {
        $isSurvey = false;
    } else {
        header('HTTP/1.0 404 Not Found');
        error('SurveyNotFound');
    }

} else {
    header("HTTP/1.1 405 Method Not Allowed");
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
        <div class="survey-info">
            <h2>
                <?php
                echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); // アンケートタイトル
                ?>
            </h2>
            <p>
                <?php
                echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); // アンケート概要
                ?>
            </p>
        </div>

        <form action="./" method="post">
            <?php
            echo "<input type='hidden' name='survey_id' value='" . htmlspecialchars($survey_id, ENT_QUOTES, 'UTF-8') . "'>"; // アンケートID
            echo $survey_div; // アンケート本文
            ?>
            <button type="submit">送信</button>
        </form>
    <?php else: ?>
        <h2>回答ありがとうございました</h2>
        <p>
            アンケートへのご回答ありがとうございました<br>
            記入された内容は、アンケート作成者に送信されました<br><br>

            ぜひ貴方もtanahiroSurveyをご使用ください！！<br>
            登録料はかかりますが、その後の使用は無料です。<br>
            ぜひ下のリンクからご登録ください！！<br>
        </p>
        <h3>
            <a href="/">登録</a>
        </h3>
    <?php endif; ?>

</main>
</body>
</html>

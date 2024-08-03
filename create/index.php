<?php
session_start();

$sessionName = 'survey';

$DB_PATH = "../db/database.json";
$database = json_decode(file_get_contents($DB_PATH), true);

$user_id = "";

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($login) { // メイン

        $length = count($_POST); // POSTのBodyのlength取得

        $isset = (
            isset($_POST['title']) &&
            isset($_POST['description']) &&
            isset($_POST['title-0']) &&
            isset($_POST['type-0'])
        );

        if ($isset) {
            $formData = $_POST;
            $delete_keys = array(
                "title",
                "description"
            );

            foreach ($delete_keys as $key) {
                unset($formData[$key]);
            }

            $survey_keys = array();
            $survey_value = array();

            foreach ($formData as $key => $value) { // 解析
                $survey_keys[] = array(explode("-", $key), $value);
                $survey_value[] = $value;
            }

            $length = count($survey_keys);
            $last_id = intval($survey_keys[$length - 1][0][1]); // 識別IDの最後

            $now_id = 0;

            /*
             * 質問タイトル : title-id
             * 質問タイプ : type-id
             * 質問タイプが選択肢の時のリスト : option-id
             */

            $survey_data = array();

            $no = 0;
            while ($now_id <= $last_id && $no < $length) {
                $survey_data[$now_id] = array();
                while ($no < $length && intval($survey_keys[$no][0][1]) == $now_id) {
                    $survey_data[$now_id]['id'] = bin2hex(random_bytes(16));
                    switch ($survey_keys[$no][0][0]) {
                        case "title":
                            $survey_data[$now_id]['title'] = $survey_value[$no];
                            break;
                        case 'type':
                            $survey_data[$now_id]['type'] = $survey_value[$no];

                            if ($survey_value[$no] == "select") {
                                $option_id = $now_id + 1;
                                if (isset($_POST["option-$option_id"])) {
                                    $options = explode("\n", $_POST["option-$option_id"]);
                                    $survey_data[$now_id]['options'] = $options;
                                } else {
                                    alert($_POST["option-$option_id"]);
                                    error('Question-notFound');
                                    exit(1);
                                }
                            }
                            break;
                        case 'required':
                            $survey_data[$now_id]['required'] = $survey_value[$no];
                            break;
                    }
                    $no += 1;
                }
                $now_id += 1;
            }

            // 解析結果をもとにアンケートを構築
            $survey = array(
                "title" => $_POST['title'],
                "description" => $_POST['description'],
                "creator" => $user_id,
                "questions" => $survey_data,
                "answers" => array()
            );
            // 質問の最後にidだけのオブジェクトが追加されるので削除
            unset($survey['questions'][count($survey['questions']) - 1]);
            $survey_id = bin2hex(random_bytes(16));

            $surveys_no = count($database['user'][$_SESSION[$sessionName]['user']['id']]['surveys']);
            if ($surveys_no != 50) {
                $database['user'][$_SESSION[$sessionName]['user']['id']]['surveys'][] = $survey_id;
                $database['survey'][$survey_id] = $survey;

                file_put_contents($DB_PATH, json_encode($database, JSON_PRETTY_PRINT));

                redirect('../show?survey_id=' . $survey_id);
            } else {
                error('PleaseDeleteSurvey');
            }


        } else {
            header('HTTP/1.1 401 Unauthorized');
        }
    } else {
        header('HTTP/1.1 401 Unauthorized');
        error('Login');
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!$login) { // ゲット要求なおかつログインしていなかった場合
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
    <a href="/">tanahiroSurvey</a> -
    <?php
    echo $_SESSION[$sessionName]['user']['name'];
    ?>
</h1>
<main>
    <form action="./" method="post">
        <div class="survey-info">
            <h3>アンケートタイトル (最大20文字)</h3>
            <input type="text" name="title" placeholder="アンケートタイトル" maxlength="20">

            <h3>アンケートの概要</h3>
            <textarea name="description" maxlength="200" placeholder="アンケート概要" cols='60' rows='10'></textarea>
        </div>

        <button type="button" id="add">質問を追加</button>

        <div class="question">

        </div>

        <button type="submit">送信</button>
    </form>

</main>

<script src="./js/app.js"></script>
</body>
</html>

<?php
session_start();

$sessionName = 'survey';

$DB_PATH = '../db/database.json';
$database = json_decode(file_get_contents($DB_PATH), true);

function create($DB_PATH, $database)
{
    $token = bin2hex(random_bytes(16));
    $database['token'][] = $token;
    echo $token;

    // セーブ
    file_put_contents($DB_PATH, json_encode($database, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pass'])) {
        if ($_POST['pass'] == '9081') {
            create($DB_PATH, $database);
            $_SESSION[$sessionName]['admin'] = true;
        }
    }
} else {
    if (isset($_SESSION[$sessionName]['admin'])) {
        if ($_SESSION[$sessionName]['admin']) {
            create($DB_PATH, $database);
        }
    }
}
?>

<html>
<head>
    <title>Token</title>
</head>
<body>
<form action="./" method="post">
    <input type="password" name="pass" placeholder="Car">
    <button type="submit">Create</button>
</form>
</body>
</html>

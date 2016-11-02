<?php
require_once('initdata.php');
require_once(IPS_ROOT_PATH . 'sources/base/ipsRegistry.php');
//Config
//Timezone
date_default_timezone_set('Europe/Moscow');
//База данных, подключение
$user = "";
$password = "";
$host = "";
$DataBase = ""; //БД форума
$BansBase = "";//БД банов
//Данные для форума
$forumID = 6; //Куда постим, ID раздела
$memberID = 1496; //Пользователь, под которым постим. TODO: Запилить на проверку сессии, если будет, то под самим юзером постить
$DemoUrl = 'http://cs16-18496a.demki.com/index.json';

try {
    $dbh = new PDO('mysql:host=' . $host . ';dbname=' . $DataBase, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->query("SET NAMES utf8");
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

if (isset($_POST['bid']) && !isset($_POST['source'])) {
    $Bid = (int)$_POST['bid'];
    //Запрос к БД
    $Query = $dbh->prepare("
				SELECT player_id,
						 player_nick,
						 admin_nick,
						 ban_reason,
						 ban_created,
						 server_ip,
						 server_name,
						 ban_created,
						 ban_length
						  FROM " . $BansBase . ".amx_bans
						 WHERE bid = :bid");
    $Query->bindParam(':bid', $Bid, PDO::PARAM_STR);
    try {
        $Query->execute();
        $Result = $Query->fetch(PDO::FETCH_ASSOC);
        //Собираем время, когда бан истекает
        $expires = $Result['ban_created'] + $Result['ban_length'] * 60;
        //Генерирование ссылки на демо
        //Делаем массив из JSON
        $demolist = json_decode(file_get_contents($DemoUrl), true);
        foreach ($demolist['files'] as $demo) {
            //Берем ссылку, которая идет сразу после бана по в
            if ($Result['ban_created'] < substr($demo['completedAt'], 0, -3)) {
                $DemoLink = 'http://cstrikedemo.g-nation.ru/' . $demo['name'];
                $DemoTime = substr($demo['completedAt'], 0, -3);
                continue;
            }
        }

    } catch (PDOException $e) {
        $Result['error'] = true;
        $Result['error_message'] = $e->getMessage();
    }
    //Если еще нет демо вываливаем ошибку
    if ($Result['ban_created'] > $DemoTime) {
        $Result['error'] = true;
        $Result['error_message'] = 'Демо еще не записано. Дождитесь окончания карты!';
    }

    if (!$Result['error']) {
        $TopicPost['header'] = "Забанен: " . $Result['player_nick'];
        $TopicPost['player_nick'] = "<strong>Ник</strong>: " . $Result['player_nick'] . "<br>";
        $TopicPost['admin_nick'] = "<strong>Забанен админом</strong>: " . $Result['admin_nick'] . "<br>";
        $TopicPost['ban_reason'] = "<strong>Причина</strong>: " . $Result['ban_reason'] . "<br>";
        $TopicPost['ban_created'] = "<strong>Бан выдан</strong>: " . date('Y.m.d H:i', $Result['ban_created']) . "<br>";
        $TopicPost['player_id'] = "<strong>Стим</strong>: " . $Result['player_id'] . "<br>";
        $TopicPost['server_name'] = "<strong>На сервере</strong>: " . $Result['server_name'] . "<br>";
        $TopicPost['expired'] = "<strong>Бан истекает</strong>: " . date('Y.m.d H:i', $expires) . "<br>";
        $TopicPost['link'] = "<strong>Ссылка на бан</strong>: <a href=" . $_POST['link'] . ">" . $_POST['link'] . "</a><br>";
        $TopicPost['demo'] = "<strong>Ссылка на демо</strong>: <a href=" . $DemoLink . ">" . $DemoLink . "</a>";

        //Инициируем общение с IPS
        $ipsInit = ipsRegistry::instance();
        $ipsInit->init();
        require_once(IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php');
        //Создаем экземпляр класса, который добавит тему на форум
        $postClass = new classPost($ipsInit);
        $postClass->setForumID($forumID);
        $postClass->setTopicTitle($TopicPost['header']);
        $postClass->setPostContent(
            $TopicPost['player_nick'] .
            $TopicPost['admin_nick'] .
            $TopicPost['ban_reason'] .
            $TopicPost['ban_created'] .
            $TopicPost['player_id'] .
            $TopicPost['server_name'] .
            $TopicPost['expired'] .
            $TopicPost['link'] .
            $TopicPost['demo']

        );
        $postClass->setAuthor($memberID);

        try {
            $postClass->addTopic();
            $Query = $dbh->prepare("SELECT MAX(tid) FROM " . $DataBase . ".ipb_topics");
            $Query->bindParam(':bid', $Bid, PDO::PARAM_STR);
            $Query->execute();
            $Result = $Query->fetch(PDO::FETCH_ASSOC);
            $Result['topic_id'] = $Result["MAX(tid)"];
        } catch (Exception $error) {
            $Result['error'] = true;
            $Result['error_message'] = $e->getMessage();
        }
        echo json_encode($Result);
    } else {
        echo json_encode($Result);
    }
} else {
    $Result['error'] = true;
    $Result['error_message'] = 'Не пришли данные из $_POST';
}

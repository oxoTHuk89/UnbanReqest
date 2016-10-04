<?php
require_once('initdata.php');
require_once(IPS_ROOT_PATH . 'sources/base/ipsRegistry.php');
//Config
//База данных, подключение
$user = "";
$password = "";
$host = "";
$DataBase = ""; //БД форума
$BansBase = "";//БД банов
//Данные для форума
$forumID = 6; //Куда постим, ID раздела
$memberID = 1496; //Пользователь, под которым постим. TODO: Запилить на проверку сессии, если будет, то под самим юзером постить
$url = 'http://g-nation.ru/index.php?/forum/6-apelliatcii/'; //RedirectUrl  TODO: Запилить редирект прям в тему. Нужно тестить LastInsertID. Еще один запрос не хочу

try {
    $dbh = new PDO('mysql:host=' . $host . ';dbname=' . $DataBase, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->query("SET NAMES utf8");
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

if (isset($_POST['bid'])) {
    $Bid = (int)$_POST['bid'];
    //Запрос к БД
    $Query = $dbh->prepare("SELECT player_id, player_nick, admin_nick, ban_reason, ban_created, server_ip, server_name, expired FROM " . $BansBase . ".amx_bans WHERE bid = :bid");
    $Query->bindParam(':bid', $Bid, PDO::PARAM_STR);
    $Query->execute();
    $Result = $Query->fetch(PDO::FETCH_ASSOC);

    //TODO: Запилить автоматическую ссылку на демо или аплоад
    /*
    $demolist = file_get_contents('http://cs16-18496a.demki.com/index.json');
    $demolist = json_decode($demolist, true);

    foreach($demolist['files'] as $demo){
        $timestamp = substr($demo['completedAt'], 0, -3);
        if($GamesList['ban_created'] < $timestamp){

            break;
        }
    }
    */
    $TopicPost['header'] = "Забанен: " . $Result['player_nick'];
    $TopicPost['player_nick'] = "<strong>Ник</strong>: " . $Result['player_nick'] . "<br>";
    $TopicPost['admin_nick'] = "<strong>Забанен админом</strong>: " . $Result['admin_nick'] . "<br>";
    $TopicPost['ban_reason'] = "<strong>Причина</strong>: " . $Result['ban_reason'] . "<br>";
    $TopicPost['ban_created'] = "<strong>Бан выдан</strong>: " . date('Y.m.d h:i', $Result['ban_created']) . "<br>";
    $TopicPost['player_id'] = "<strong>Стим</strong>: " . $Result['player_id'] . "<br>";
    $TopicPost['server_name'] = "<strong>На сервере</strong>: " . $Result['server_name'] . "<br>";
    $TopicPost['expired'] = "<strong>Бан истекает</strong>: " . date('Y.m.d h:i', $Result['expired']) . "<br>";
    $TopicPost['link'] = "<strong>Ссылка</strong>: <a href=" . $_POST['link'] . ">" . $_POST['link'] . "</a>";

    $registry = ipsRegistry::instance();
    $registry->init();

    require_once(IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php');

    $postClass = new classPost($registry);
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
        $TopicPost['link']

    );
    $postClass->setAuthor($memberID);

    try {
        $postClass->addTopic();
    } catch (Exception $error) {
        print $error->getMessage();
    }

    header('Location:' . $url);
}

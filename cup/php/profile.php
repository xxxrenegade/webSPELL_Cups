<?php

try {

    $_language->readModule('profile');

    if (isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
    } else {
        $user_id = $userID;
    }

    if (!checkIfContentExists($user_id, 'userID', 'user')) {
        throw new \Exception($_language->module['user_doesnt_exist']);
    }

    if (isbanned($user_id)) {
        echo showError($_language->module['is_banned'], true);
    }

    $date = time();

    $ergebnis = mysqli_query(
        $_database,
        "SELECT * FROM `" . PREFIX . "user`
            WHERE `userID` = " .$user_id
    );

    if (!$ergebnis) {
        throw new \Exception($_language->module['query_select_failed']);
    }

    $ds = mysqli_fetch_array($ergebnis);

    updateUserVisitorStatistic($user_id);

    $anzvisits = $ds['visits'];
    $nickname = $ds['nickname'];

    $time_now = time();

    $get_pp = mysqli_query(
        $_database,
        "SELECT
                `ppID`,
                `date`,
                `duration_time`,
                `reasonID`
            FROM `" . PREFIX . "cups_penalty`
            WHERE `duration_time` > " . $time_now . " AND `userID` = " . $user_id . " AND `deleted` = 0"
    );

    if (mysqli_num_rows($get_pp) > 0) {

        $penaltyContentArray = array();

        while ($get = mysqli_fetch_array($get_pp)) {

            $penaltyArray = getpenalty($get['ppID']);

            $points = $penaltyArray['points'];
            if ($points == 1) {
                $pen = $points . ' ' . $_language->module['penalty'];
            } else {
                $pen = $points . ' ' . $_language->module['penalties'];
            }

            $ppInfoText = $penaltyArray['category_name']['de'];
            $ppInfoText .= ' (' . $_language->module['penalty_until'] . ' ' . getformatdatetime($get['duration_time']) . ')';

            $penaltyContentArray[] = '<span class="bold">' . $pen . ':</span> ' . $ppInfoText;

        }

        $penalty = showError(implode('<br />', $penaltyContentArray));

    } else {
        $penalty = '';
    }

    $registered = getformatdatetime($ds['registerdate']);
    $lastlogin = getformatdatetime($ds['lastlogin']);
    $status = isonline($ds['userID']);

    if ( $ds['email_hide'] ) {
        $email = $_language->module['n_a'];
    } else {
        $email = '<a href="mailto:'.mail_protect(cleartext($ds['email'])).'">'.cleartext($ds['email']).'</a>';
    }

    if ($loggedin && $ds['userID'] != $userID) {

        $pm = '<a href="index.php?site=messenger&amp;action=touser&amp;touser='.$ds['userID'].'">&raquo; '.$_language->module['write'].'</a>';

        $icon_url = $image_url . 'icons/';

        if (isignored($userID, $ds['userID'])) {
            $buddy = '<a href="buddys.php?action=readd&amp;id='.$ds['userID'].'&amp;userID='.$userID.'"><img src="' . $icon_url . 'buddy_readd.gif" border="0" alt="'.$_language->module['back_buddylist'].'" /></a>';
        } else if (isbuddy($userID, $ds['userID'])) {
            $buddy = '<a href="buddys.php?action=ignore&amp;id='.$ds['userID'].'&amp;userID='.$userID.'"><img src="' . $icon_url . 'buddy_ignore.gif" border="0" alt="'.$_language->module['ignore_user'].'" /></a>';
        } else if ($userID == $ds['userID']) {
            $buddy = '';
        } else {
            $buddy = '<a href="buddys.php?action=add&amp;id='.$ds['userID'].'&amp;userID='.$userID.'"><img src="' . $icon_url . 'buddy_add.gif" border="0" alt="'.$_language->module['add_buddylist'].'" /></a>';
        }

    } else {

        $pm = $_language->module['no_write'];
        $buddy = '';

    }

    $firstname = clearfromtags($ds['firstname']);
    $lastname = clearfromtags($ds['lastname']);

    $birthday = mb_substr($ds['birthday'], 0, 10);
    $birthday = date("d.m.Y",strtotime($birthday));

    $birth = getAge($user_id) . ' ' . $_language->module['years'];

    if($ds['sex'] == "f") $sex = $_language->module['female'];
    elseif($ds['sex'] == "m") $sex = $_language->module['male'];
    else $sex = $_language->module['unknown'];
    $flag = '[flag]'.$ds['country'].'[/flag]';
    $profilecountry = flags($flag);
    $town = clearfromtags($ds['town']);
    if($town == '') $town = $_language->module['n_a'];

    $equipmentArray = array();

    $cpu = clearfromtags($ds['cpu']);
    if (!empty($cpu)) {
        $equipmentArray['cpu'] = $cpu;
    }

    $mainboard = clearfromtags($ds['mainboard']);
    if (!empty($mainboard)) {
        $equipmentArray['mainboard'] = $mainboard;
    }

    $ram = clearfromtags($ds['ram']);
    if (!empty($ram)) {
        $equipmentArray['ram'] = $ram;
    }

    $monitor = clearfromtags($ds['monitor']);
    if (!empty($monitor)) {
        $equipmentArray['monitor'] = $monitor;
    }

    $graphiccard = clearfromtags($ds['graphiccard']);
    if (!empty($graphiccard)) {
        $equipmentArray['graphiccard'] = $graphiccard;
    }

    $soundcard = clearfromtags($ds['soundcard']);
    if (!empty($soundcard)) {
        $equipmentArray['soundcard'] = $soundcard;
    }

    $connection = clearfromtags($ds['verbindung']);
    if (!empty($connection)) {
        $equipmentArray['connection'] = $connection;
    }

    $keyboard = clearfromtags($ds['keyboard']);
    if (!empty($keyboard)) {
        $equipmentArray['keyboard'] = $keyboard;
    }

    $mouse = clearfromtags($ds['mouse']);
    if (!empty($mouse)) {
        $equipmentArray['mouse'] = $mouse;
    }

    $mousepad = clearfromtags($ds['mousepad']);
    if (!empty($mousepad)) {
        $equipmentArray['mousepad'] = $mousepad;
    }

    if (isset($ds['microphone'])) {

        $microphone = clearfromtags($ds['microphone']);
        if (!empty($microphone)) {
            $equipmentArray['microphone'] = $microphone;
        }

    }

    if (validate_array($equipmentArray, true)) {

        $equipmentKeyArray = array_keys($equipmentArray);

        $equipment = '';
        foreach ($equipmentKeyArray as $equipmentKey) {

            $key = $equipmentKey;
            $equipment .= '<div class="list-group-item">' . $_language->module[$key] . ': ' . $equipmentArray[$key] . '</div>';

        }

    } else {
        $equipment = '<div class="list-group-item grey italic">' . $_language->module['no_equipment'] . '</div>';
    }

    $anznewsposts = getusernewsposts($ds['userID']);
    $anzforumtopics = getuserforumtopics($ds['userID']);
    $anzforumposts = getuserforumposts($ds['userID']);

    $comments = array();
    $comments[] = getusercomments($ds['userID'], 'ne');
    $comments[] = getusercomments($ds['userID'], 'cw');
    $comments[] = getusercomments($ds['userID'], 'ar');
    $comments[] = getusercomments($ds['userID'], 'de');

    $pmgot = 0;
    $pmgot = $ds['pmgot'];

    $pmsent = 0;
    $pmsent = $ds['pmsent'];

    if ($ds['about']) {
        $about = cleartext($ds['about']);
    } else {
        $about = $_language->module['n_a'];
    }

    $teams = '';

    $whereClauseArray = array();
    $whereClauseArray[] = '`userID` = ' . $user_id;
    $whereClauseArray[] = '`active` = 1';

    $whereClause = implode(' AND ', $whereClauseArray);

    $teamSelectQuery = mysqli_query(
        $_database,
        "SELECT
                `teamID`,
                `join_date`
            FROM `" . PREFIX . "cups_teams_member`
            WHERE " . $whereClause . "
            ORDER BY teamID ASC"
    );

    if ($teamSelectQuery) {

        $anz = mysqli_num_rows($teamSelectQuery);
        if ($anz) {

            while ( $db = mysqli_fetch_array($teamSelectQuery) ) {

                $team_id = $db['teamID'];

                $teamInfo = getteam($team_id, 'name');
                $teamInfo .= '<span class="pull-right">' . getformatdatetime($db['join_date']) . '</span>';

                $teams .= '<a href="index.php?site=teams&amp;action=details&amp;id=' . $team_id . '" class="list-group-item">' . $teamInfo . '</a>';

            }

        } else {
            $teams = '<div class="list-group-item grey italic">'.$_language->module['no_team'].'</div>';
        }

    }

    $gameaccounts = '';

    $whereClauseArray = array();
    $whereClauseArray[] = '`userID` = ' . $user_id;
    $whereClauseArray[] = '`active` = 1';
    $whereClauseArray[] = '`deleted` = 0';

    $whereClause = implode(' AND ', $whereClauseArray);

    $gameaccountSelectQuery = mysqli_query(
        $_database,
        "SELECT * FROM `" . PREFIX . "cups_gameaccounts`
            WHERE " . $whereClause
    );
    if ($gameaccountSelectQuery) {

        if (mysqli_num_rows($gameaccountSelectQuery) > 0) {

            $steamGameaccountArray = array(
                'csg'
            );

            $gameaccounts = '';
            while ($db = mysqli_fetch_array($gameaccountSelectQuery)) {

                $game_tag = $db['category'];
                $gameaccountInfo = getgamename($game_tag);

                if (in_array($game_tag, $steamGameaccountArray)) {

                    $linkAttributeArray = array();
                    $linkAttributeArray[] = 'href="https://steamcommunity.com/profiles/' . $db['value'] . '"';
                    $linkAttributeArray[] = 'class="pull-right blue"';
                    $linkAttributeArray[] = 'target="_blank"';

                    $gameaccountInfo .= '<a ' . implode(' ', $linkAttributeArray) . '>' . $db['value'] . '</a>';
                } else {
                    $gameaccountInfo .= '<span class="pull-right">' . $db['value'] . '</span>';
                }


                $gameaccounts .= '<div class="list-group-item">' . $gameaccountInfo . '</div>';

            }

        } else {
            $gameaccounts = '<div class="list-group-item grey italic">' . $_language->module['no_gameacc'] . '</div>';
        }

    }

    $data_array = array();
    $data_array['$firstname'] = $firstname;
    $data_array['$nickname'] = $nickname;
    $data_array['$lastname'] = $lastname;
    $data_array['$penalty'] = $penalty;
    $data_array['$birth'] = $birth;
    $data_array['$town'] = $town;
    $data_array['$lastlogin'] = $lastlogin;
    $data_array['$registered'] = $registered;
    $data_array['$pm'] = $pm;
    $data_array['$equipment'] = $equipment;
    $data_array['$connection'] = $connection;
    $data_array['$teams'] = $teams;
    $data_array['$gameaccounts'] = $gameaccounts;
    $profile = $GLOBALS["_template_cup"]->replaceTemplate("profile", $data_array);
    echo $profile;

} catch (Exception $e) {
    echo showError($e->getMessage());
}

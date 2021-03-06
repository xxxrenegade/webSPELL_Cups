<?php

$returnArray = array(
    'status' => FALSE,
    'message' => array(),
    'results' => 0,
    'htmlData' => ''
);

try {

    $_language->readModule('gameaccounts', false, true);

    $varPage = '';

    $value = (isset($_GET['value'])) ?
        getinput($_GET['value']) : '';

    if (empty($value)) {
        throw new \Exception('no_value');
    }

    $state = (isset($_GET['state'])) ?
        getinput($_GET['state']) : '';

    if (empty($state)) {
        throw new \Exception('no_state');
    }

    $stateArray = array(
        'intern',
        'extern'
    );

    if (!in_array($state, $stateArray)) {
        throw new \Exception('wrong_state');
    }

    if ($state == 'intern') {

        $checkSteam64ID = FALSE;
        if(strlen($value) == 17) {
            $checkSteam64ID = TRUE;
        }

        $whereClauseArray = array();

        if ($checkSteam64ID) {
            $whereClauseArray[] = 'cg.`value` = \'' . $value . '\'';
        }

        $n = 1;

        if (validate_array($whereClauseArray, true)) {

            $whereClause = implode(' OR ', $whereClauseArray);

            $checkIf = mysqli_fetch_array(
                mysqli_query(
                    $_database,
                    "SELECT
                            COUNT(userID) AS `exists`
                        FROM `" . PREFIX . "cups_gameaccounts` cg
                        WHERE " . $whereClause
                )
            );

            if($checkIf['exists'] > 0) {

                $query = mysqli_query(
                    $_database, 
                    "SELECT 
                            cg.*, 
                            b.nickname AS `user_nick`,
                            c.name AS `game_name`
                        FROM `".PREFIX."cups_gameaccounts` cg
                        LEFT JOIN `". PREFIX . "user` b ON cg.userID = b.userID
                        LEFT JOIN `" . PREFIX . "games` c ON a.category = c.tag
                        WHERE " . $whereClause . "
                        ORDER BY cg.`active` DESC"
                );

                if (!$query) {
                    throw new \Exception('cups_gameaccounts_query_select_failed (' . $whereClause . ')');
                }

                while($get = mysqli_fetch_array($query)) {

                    $active = ($get['active']) ?
                        '<span class="btn btn-success btn-xs">' . $_language->module['yes'] . '</span>' : 
                        '<span class="btn btn-danger btn-xs">' . $_language->module['no'] . '</span>';

                    $data_array = array();
                    $data_array['$n'] = $n++;
                    $data_array['$gameaccount_id'] = $get['gameaccID'];
                    $data_array['$user_id'] = $get['userID'];
                    $data_array['$nickname'] = getoutput($get['user_nick']);
                    $data_array['$game'] = $get['game_name'];
                    $data_array['$value'] = $get['value'];
                    $data_array['$active'] = $active;
                    $returnArray['htmlData'] .= $GLOBALS["_template_cup"]->replaceTemplate("gameaccount_find_list", $data_array);

                    $returnArray['results']++;

                }

            }

        }


        $whereClauseArray = array();

        $whereClauseArray[] = 'a.`nickname` LIKE \'%' . $value . '%\'';

        $whereClause = implode(' OR ', $whereClauseArray);

        $checkIf = mysqli_fetch_array(
            mysqli_query(
                $_database,
                "SELECT
                        COUNT(userID) AS `exists`
                    FROM `" . PREFIX . "user` a
                    WHERE " . $whereClause
            )
        );

        if ($checkIf['exists']) {

            $returnArray['results']++;

            $query = mysqli_query(
                $_database,
                "SELECT
                        a.userID AS userID,
                        a.nickname AS nickname,
                        b.gameaccID AS gameaccID,
                        b.category AS category,
                        b.value AS value,
                        b.active AS active
                    FROM `" . PREFIX . "user` a
                    LEFT JOIN `" . PREFIX . "cups_gameaccounts` b ON a.userID = b.userID
                    WHERE " . $whereClause
            );

            if (!$query) {
                throw new \Exception('cups_gameaccounts_query_select_failed (' . $whereClause . ')');
            }

            while ($get = mysqli_fetch_array($query)) {

                if(!is_null($get['value'])) {
                    $active = ($get['active']) ?
                        '<span class="btn btn-success btn-xs white darkshadow">' . $_language->module['yes'] . '</span>' : 
                        '<span class="btn btn-danger btn-xs white darkshadow">' . $_language->module['no'] . '</span>';
                } else {
                    $active = '<span class="btn btn-info btn-xs white darkshadow">' . $_language->module['no_gameaccount'] . '</span>';
                }

                $actions = '';
                $actions .= '';

                $data_array = array();
                $data_array['$n'] = $n++;
                $data_array['$gameaccount_id'] = (is_null($get['gameaccID'])) ?
                    '' : $get['gameaccID'];
                $data_array['$user_id'] = $get['userID'];
                $data_array['$nickname'] = $get['nickname'];
                $data_array['$game'] = (is_null($get['category'])) ?
                    '' : getGame($get['category'], 'name');
                $data_array['$value'] = (is_null($get['value'])) ?
                    '' : $get['value'];
                $data_array['$active'] = $active;
                $returnArray['htmlData'] .= $GLOBALS["_template_cup"]->replaceTemplate("gameaccount_find_list", $data_array);

                $returnArray['results']++;

            }

        }

    } else if ($state == 'extern') {

        $valueArray = explode(':', $value);
        $anzValueArray = count($valueArray);

        $steam64_id = 0;
        if ((strlen($value) == 17) && ($anzValueArray == 1)) {
            $steam64_id = $value;
        } elseif($anzValueArray == 3) {
            $steam64_id = SteamID2CommunityID($value);
        } else if (validate_url($value)) {

            if (preg_match('/\/profiles\//i', $value)) {
                $getTypeOfURL = 'profiles';
            } else if (preg_match('/\/id\//i', $value)) {
                $getTypeOfURL = 'id';
            }

            $valueArray = explode('/', $value);
            $getIndex = count($valueArray) - 1;

            if(!empty($valueArray[$getIndex])) {
                $value = $valueArray[$getIndex];
            } else {
                $value = $valueArray[$getIndex - 1];
            }

            if ($getTypeOfURL == 'profiles' && (strlen($value) == 17)) {

                $steam64_id = $value;

            } else if ($getTypeOfURL == 'id') {

                $final_community_url = 'https://steamcommunity.com/id/' . $value . '/?xml=1';
                if($result = @file_get_contents($final_community_url)) {
                    $begin = strpos($result, '7656');
                    $steam64_id = substr($result, $begin, 17);
                }

            }

        }

        if (strlen($steam64_id) != 17) {
            throw new \Exception('wrong_steam64_id');
        }

        $accountDetails = getCSGOAccountInfo($steam64_id);

        $steamProfileData = $accountDetails['steam_profile'];
        $vacStatusData = $accountDetails['vac_status'];
        $csgoStatsData = $accountDetails['csgo_stats'];

        if (isset($steamProfileData['personaname'])) {
            $returnArray['htmlData'] .= '<div class="list-group-item">Steam Name:';
            $returnArray['htmlData'] .= '<span class="pull-right">'.$steamProfileData['personaname'].'</span></div>';
        }

        if ($varPage == 'admin') {

            if(isset($steamProfileData['realname'])) {
                $returnArray['htmlData'] .= '<div class="list-group-item">Name:';
                $returnArray['htmlData'] .= '<span class="pull-right">'.$steamProfileData['realname'].'</span></div>';
            }

            if(isset($steamProfileData['timecreated'])) {
                $returnArray['htmlData'] .= '<div class="list-group-item">Erstellt am:';
                $returnArray['htmlData'] .= '<span class="pull-right">'.getformatdatetime($steamProfileData['timecreated']).'</span></div>';
            }

            $returnArray['htmlData'] .= '<div class="list-group-item">Steam64 ID:';
            $returnArray['htmlData'] .= '<span class="pull-right">'.$steam64_id.'</span></div>';

        }

        $steam_id = CommunityID2SteamID($steam64_id);

        $returnArray['htmlData'] .= '<div class="list-group-item">CS:GO ID:';
        $returnArray['htmlData'] .= '<span class="pull-right">' . $steam_id . '</span></div>';

        if ($varPage == 'admin') {

            $returnArray['htmlData'] .= '<div class="list-group-item">Community Banned?';
            if(empty($vacStatusData['CommunityBanned'])) {
                $returnArray['htmlData'] .= '<span class="pull-right">nein</span></div>';
            } else {
                $returnArray['htmlData'] .= '<span class="pull-right alert-danger">ja</span></div>';
            }

            $returnArray['htmlData'] .= '<div class="list-group-item">VAC Banned?';
            if(empty($vacStatusData['VACBanned'])) {
                $returnArray['htmlData'] .= '<span class="pull-right">nein</span></div>';
            } else {
                $returnArray['htmlData'] .= '<span class="pull-right alert-danger">ja</span></div>';
                $returnArray['htmlData'] .= '<div class="list-group-item">Letzter Bann vor';
                $returnArray['htmlData'] .= '<span class="pull-right">' . $vacStatusData['DaysSinceLastBan'] . ' Tagen</span></div>';
            }

            $returnArray['htmlData'] .= '<div class="list-group-item">Echte Spielzeit:';
            $returnArray['htmlData'] .= '<span class="pull-right">' . $csgoStatsData['time_played']['hours'] . ' Stunden</span></div>';

        }

        $extern_url = 'https://steamcommunity.com/profiles/' . $steam64_id;
        $returnArray['htmlData'] .= '<a href="' . $extern_url . '" target="_blank" class="list-group-item">&raquo; Steam Community Profile</a>';

        $check = mysqli_fetch_array(
            mysqli_query(
                $_database,
                "SELECT 
                        COUNT(*) AS `if_exists` 
                    FROM `" . PREFIX . "cups_gameaccounts`
                    WHERE `value` = '" . $steam64_id . "'"
            )
        );

        if ($varPage == 'admin') {

            if($check['if_exists']) {

                $get = mysqli_fetch_array(
                    mysqli_query(
                        $_database,
                        "SELECT `userID` FROM `" . PREFIX . "cups_gameaccounts`
                            WHERE `value` = '" . $steam64_id. "'"
                    )
                );

                $intern_url = 'admincenter.php?site=cup&amp;mod=gameaccounts&amp;action=log&amp;user_id=' . $get['userID'];
                $returnArray['htmlData'] .= '<a href="' . $intern_url . '" class="list-group-item">&raquo; '.getnickname($get['userID']).'</a>';

            }

        }

        $returnArray['htmlData'] .= '<input type="hidden" name="steam64_id" id="hidden_Steam64_ID" value="' . $steam64_id . '" />';

        $returnArray['results']++;

    }

    $returnArray['status'] = TRUE;

} catch (Exception $e) {
    $returnArray['message'][] = $e->getMessage();
}

echo json_encode($returnArray);

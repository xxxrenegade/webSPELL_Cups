<?php

/**
 * Cup System written by SlicewOw - myRisk
 * Copyright (c) by SlicewOw
 **/

try {

    $_language->readModule('cups', false, true);

    if (!$loggedin || !iscupadmin($userID)) {
        throw new \Exception($_language->module['login']);
    }

    $mod = (isset($_GET['mod'])) ?
        getinput($_GET['mod']) : 'cup';

    $cup_base_path = __DIR__ . '/../cup/php/';

    if ($mod == 'cup') {

        $getStatus = (isset($_GET['status'])) ?
            getinput($_GET['status']) : '';

        if ( $getAction == "add" ) {

            // Cup hinzufuegen
            if (file_exists(__DIR__ . '/cup_admin/cup_add.php')) {
                include(__DIR__ . '/cup_admin/cup_add.php');
            } else {
                throw new \Exception($_language->module['access_denied']);
            }

        } else if ( $getAction == "edit" && isset($_GET['id']) ) {
            // Cup bearbeiten
            include(__DIR__ . '/cup_admin/cup_edit.php');
        } else if ( $getAction == "delete" && isset($_GET['id'])) {
            // Cup loeschen
            include(__DIR__ . '/cup_admin/cup_delete.php');
        } else if (($getAction == "cup") || ($getAction == "status")) {
            // Detail Ansicht des Cups
            include(__DIR__ . '/cup_admin/cup_admin_details.php');
        } else if ($getAction == "teamadd" || $getAction == "playeradd") {
            // Team zu einem Cup hinzufuegen (durch Admin)
            include(__DIR__ . '/cup_admin/cup_admin_joincup.php');
        } else if ( $getAction == "start" && !empty($getStatus) && isset($_GET['id']) ) {

            if ($getStatus == "playoffs") {
                // Cup starten und Playoffs erstellen
                include(__DIR__ . '/cup_admin/includes/cup_start_playoffs.php');
            } else if ($getStatus == "groupstage") {
                // Cup starten und Gruppen erstellen
                include(__DIR__ . '/cup_admin/includes/cup_start_groupstage.php');
            } else {
                throw new \Exception($_language->module['access_denied']);
            }

        } else if ( $getAction == "finish" && !empty($getStatus) && isset($_GET['id']) ) {

            if($getStatus == "playoffs") {
                // Cup beenden
                include(__DIR__ . '/cup_admin/cup_finish_playoffs.php');
            } else {
                // Fehler
                include(__DIR__ . '/../error.php');
            }

        } else {
            // Cup Admin Details
            include(__DIR__ . '/cup_admin/cup_details.php');
        }

    } else if ($mod == 'support') {
        include(__DIR__ . '/cup_admin/ticket_admin.php');
    } else if (file_exists(__DIR__ . '/cup_admin/cup_' . $mod . '.php')) {
        include (__DIR__ . '/cup_admin/cup_' . $mod . '.php');
    } else if (file_exists(__DIR__ . '/cup_admin/' . $mod . '.php')) {
        include(__DIR__ . '/cup_admin/' . $mod . '.php');
    } else {
        throw new \Exception($_language->module['access_denied']);
    }

} catch (Exception $e) {
    echo showError($e->getMessage());
}

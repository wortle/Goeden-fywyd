<?php

/***************************************************************************
 *   user_settings.php                                                     *
 *   Yggdrasil: User settings Form                                         *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "../settings/settings.php";
require_once "../langs/$language.php";
require "../functions.php";
require "./forms.php";

if (!isset($_POST['posted'])) {
    $settings = fetch_row_assoc("
        SELECT
            username,
            user_full_name,
            user_email,
            place_filter_level,
            place_filter_content,
            show_delete,
            initials,
            user_lang
        FROM
            user_settings
        WHERE
            username = current_user
    ");
    $title = "$_User_settings for " . $settings['username'];
    require "./form_header.php";
    echo "<h2>$title</h2>\n";
    $form = 'user_settings';
    form_begin($form, $_SERVER['PHP_SELF']);
    hidden_input('posted', 1);

    //section: user language
    echo "<tr><td colspan=\"2\"><b>$_Language</b></td></tr>\n";
    echo "<tr><td>$_Language:  </td><td>\n<select name=\"user_lang\">\n";
    echo "<option";
    if ($settings['user_lang'] == 'en')
            echo " selected=\"selected\"";
    echo " value=\"en\">English</option>\n";
    echo "<option";
    if ($settings['user_lang'] == 'nb')
            echo " selected=\"selected\"";
    echo " value=\"nb\">Norsk (bokmål)</option>\n";
    echo "</select></td></tr><tr><td colspan=\"2\">&nbsp;</td></tr>\n";

    // section: User details
    echo "<tr><td colspan=\"2\"><b>$_User_details</b></td></tr>\n";
    text_input("$_Full_name:", 40, 'user_full_name', $settings['user_full_name']);
    text_input("$_Email_addr:", 40, 'user_email', $settings['user_email']);
    text_input("$_Initials:", 10, 'initials', $settings['initials']);
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";

    // Section: place filter settings
    echo "<tr><td colspan=\"2\"><b>$_Place_filter</b></td></tr>\n";
    echo "<tr><td>$_Level:  </td><td>\n<select name=\"place_filter_level\">\n";
    $place_desc = 'desc_' . $language; // desc_en or desc_nb
    $handle = pg_query("
        SELECT
            place_level_name,
            $place_desc
        FROM
            place_level_desc
        ORDER BY place_level_id ASC
    ");
    while ($rec = pg_fetch_assoc($handle)) {
        $option = "<option ";
        if ($rec['place_level_name'] == $settings['place_filter_level'])
            $option .= "selected=\"selected\" ";
        $option .= "value=\"" . $rec['place_level_name'] . "\">" . $rec[$place_desc] . "</option>\n";
        echo $option;
    }
    echo "</select></td></tr>\n";
    text_input("$_Contents:", 10, 'place_filter_content', $settings['place_filter_content']);
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";

    form_submit();
    form_end();
    echo "</body>\n</html>\n";
}
else {
    // do update
    pg_query("
        UPDATE
            user_settings
        SET
            user_full_name = '" . $_POST['user_full_name'] . "',
            user_email = '" . $_POST['user_email'] . "',
            place_filter_level = '" . $_POST['place_filter_level'] . "',
            place_filter_content = '" . $_POST['place_filter_content'] . "',
            initials = '" . $_POST['initials'] . "',
            user_lang = '" . $_POST['user_lang'] . "'
        WHERE
            username = current_user
    ");
    header("Location: $app_root/index.php");
}

?>

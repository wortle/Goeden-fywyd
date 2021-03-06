<?php

/***************************************************************************
 *   couples.php                                                           *
 *   Yggdrasil: Search for couples                                         *
 *                                                                         *
 *   Copyright (C) 2009-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "./settings/settings.php";
require_once "./langs/$language.php";

$title = gettext(Search for couples);
$form = 'couple';
$focus= 'husb';
require "./header.php";
require "./functions.php";

echo "<div class=\"normal\">";
echo "<h2>$title</h2>\n";

echo "<form id=\"couple\" action=\"" . $_SERVER['PHP_SELF'] . "\">\n<div>\n";
echo "gettext(Husband): <input type=\"text\" size=\"12\" name=\"husb\" />\n";
echo "gettext(Wife): <input type=\"text\" size=\"12\" name=\"wife\" />\n";
echo "<input type=\"submit\" value=\"gettext(Search)\" />\n";
echo "</div>\n</form>\n\n";

$husb = isset($_GET['husb']) ? $_GET['husb'] : '';
$wife = isset($_GET['wife']) ? $_GET['wife'] : '';

if ($husb && $wife) {
    $handle = pg_query("select * from couples where p1n ilike '$husb%' and p2n ilike '$wife%'");
    echo "<p>";
    while ($row = pg_fetch_assoc($handle)) {
        echo $row['sort_date']
            . ' ' . get_name_and_dates("./family.php", $row['p1'])
            . ' ' . get_name_and_dates("./family.php", $row['p2'])
            . "<br />\n";
    }
    echo "</p>\n";
}
echo "</div>\n";
include "./footer.php";
?>

<?php

/***************************************************************************
 *   place_manager.php                                                     *
 *   Yggdrasil: Place Manager                                              *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "./settings/settings.php";
require "./functions.php";
require_once "./langs/$language.php";

$title = "gettext(Place names)";
require "./header.php";

$count = fetch_val("SELECT COUNT(*) FROM places");

echo "<div class=\"normal\">\n";
echo "<h2>gettext(Place names) ($count)</h2>\n";
echo "<p>( <a href=\"./forms/place_edit.php?place_id=0\">gettext(insert)</a> )</p>\n";
echo "<table>\n";
$handle = pg_query("SELECT place_id, place_name, place_count FROM pm_view");
while ($row = pg_fetch_assoc($handle)) {
    echo "<tr>";
    if ($row['place_count'] == 0)
        echo "<td><strong><a href=\"./forms/place_delete.php?place_id=".$row['place_id']."\">gettext(delete)</a></strong></td>";
    else
        echo "<td><a href=\"./place_view.php?place_id=".$row['place_id']."\">gettext(report)</a></td>";
    echo "<td align=\"right\">".$row['place_count']."</td>";
    echo "<td><a href=\"./forms/place_edit.php?place_id=".$row['place_id']."\">".$row['place_name']."</a></td>";
    echo "</tr>\n";
}
echo "</table>\n";
echo "</div>\n";
include "./footer.php";
?>

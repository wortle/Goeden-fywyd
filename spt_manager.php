<?php

/***************************************************************************
 *   source_type_manager.php                                               *
 *   Yggdrasil: Source Type Manager                                        *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "./settings/settings.php";
require "./functions.php";
require_once "./langs/$language.php";

$title = "gettext(Source types)";
require "./header.php";

echo "<div class=\"normal\">\n";
echo "<h2>$title</h2>\n";
echo "<p>( <a href=\"./forms/spt_edit.php?spt=0\">gettext(insert)</a> )</p>\n";
echo "<table>\n";
$label = 'label_' . $language;
$handle = pg_query("
    SELECT
        part_type_id,
        $label,
        description,
        is_leaf,
        part_type_count(part_type_id) AS tc
    FROM
        source_part_types
    ORDER BY
        tc DESC
    ");
while ($row = pg_fetch_assoc($handle)) {
    echo "<tr>";
    // if part type is unused, display link for deletion
    if ($row['tc'] == 0 && $row['part_type_id'] != 0)
        echo "<td><strong><a href=\"./forms/spt_delete.php?spt="
            . $row['part_type_id'] . "\">gettext(delete)</a></strong></td>";
    else
        echo "<td><a href=\"./spt_view.php?spt="
            . $row['part_type_id']."\">gettext(report)</a></td>";
    echo "<td align=\"right\">".$row['tc']."</td>";
    echo "<td><a href=\"./forms/spt_edit.php?spt=" . $row['part_type_id']
        . "\" title=\"gettext(edit)\">" . $row[$label] . "</a></td>";
    echo "<td>";
    echo ($row['is_leaf'] == 't') ? gettext(Leaf) : gettext(Branch);
    echo "</td>";
    echo "<td>".$row['description']."</td>";
    echo "</tr>\n";
}
echo "</table>\n";
echo "</div>\n";
include "./footer.php";
?>

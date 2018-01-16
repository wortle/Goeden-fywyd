<?php

/***************************************************************************
 *   tag_view.php                                                          *
 *   Yggdrasil: Tag View                                                   *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

// This script is basically a report listing events and persons associated
// with a tag. It is accessed from the Tag Manager via the 'report' link.

require "./settings/settings.php";
require "./functions.php";
require_once "./langs/$language.php";

$tag = $_GET['tag'];
$tag_name = fetch_val("SELECT get_tag_name($tag)");
$title = "$_All $_events $_of type $tag_name";
require "./header.php";

echo "<div class=\"normal\">\n";
echo "<h2>$title</h2>\n";
$handle = pg_query("
    SELECT
        event_id,
        event_name,
        event_date,
        place_name,
        p1,
        p2
    FROM
        tag_events
    WHERE
        tag_fk = $tag
    ORDER BY
        event_date,
        event_id
");
while ($row = pg_fetch_assoc($handle)) {
    echo '<p>[' . $row['event_id'] . '] ';
    echo $row['event_name'];
    echo ' ' . fuzzydate($row['event_date']);
    echo ' ' . $row['place_name'] . ': ';
    echo list_participants($row['event_id']);
    // print source(s)
    $innerhandle = pg_query("
    SELECT
        source_text
    FROM
        event_notes
    WHERE
        note_id = " . $row['event_id']
    );
    while ($row = pg_fetch_assoc($innerhandle)) {
            echo conc(paren($_Source . ':'
            . conc(ltrim($row['source_text']))));
    }
    echo "</p>\n";
}
echo "</div>\n";
include "./footer.php";
?>

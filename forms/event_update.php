<?php

/***************************************************************************
 *   event_update.php                                                      *
 *   Yggdrasil: Event Update Form and Action                               *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "../settings/settings.php";
require_once "../langs/$language.php";
require "../functions.php";
require "./forms.php";

if (!isset($_POST['posted'])) {
    // do form
    $event = $_GET['event'];
    $person = $_GET['person'];
    $name = get_name($person);
    $title = "$_Edit_event #$event ($_person #$person $name)";
    require "./form_header.php";
    echo "<h2>$title</h2>\n";
    $rec = fetch_row_assoc("SELECT tag_fk, place_fk, event_date, date2text(sort_date) AS sd, event_note
                            FROM events WHERE event_id = $event");
    $note = note_from_db($rec['event_note']);
    $notelen = strlen($note);
    $notelen < 1000 ? $note_height = 5 : $note_height = 20;
    $note_width = 100;
    form_begin('edit_event', $_SERVER['PHP_SELF']);
        hidden_input('person', $person);
        hidden_input('event', $event);
        hidden_input('posted', 1);
        select_tag($rec['tag_fk'], $person, $event);
        select_place($rec['place_fk']);
        date_input($rec['event_date'], $rec['sd']);
        textarea_input("$_Text:<br />$notelen", $note_height, $note_width, 'event_note', $note);
        source_input();
        text_input("$_Age:", 10, 'age', '', "($_Adds_birth_event)");
        form_submit();
    form_end();
    echo "<h3>$_Citations</h3>\n";
    $handle = pg_query("SELECT source_fk FROM event_citations WHERE event_fk = $event");
    while ($row = pg_fetch_row($handle)) {
        echo '<p>'.$row[0].' ';
        echo fetch_val("SELECT get_source_text($row[0])");
        echo "</p>\n";
    }
    echo "</body>\n</html>\n";
}
else {
    // do action
    $src = $_POST['source_id'];
    $txt = $_POST['source_text'];
    if ($txt && fetch_val("SELECT is_leaf($src)") == 't') {
        echo "Cannot create subsource under source #$src. ";
        echo "Please go back and check your source reference.";
        die;
    }
    $event = $_POST['event'];
    $person = $_POST['person'];
    $tag = $_POST['tag_fk'];
    $place = $_POST['place_fk'];
    if ($place == 0) $place = 1;
    $note = note_to_db($_POST['event_note']);
    $event_date = pad_date($_POST['date_1']) . $_POST['date_type']
                    . pad_date($_POST['date_2']) . '1';
    $sort_date = parse_sort_date($_POST['sort_date'],$event_date);
    pg_query("BEGIN");
    pg_query("UPDATE EVENTS SET tag_fk=$tag, place_fk=$place, event_date='$event_date',
                sort_date='$sort_date', event_note='$note' WHERE event_id = $event");
    set_last_selected_place($place);
    set_last_edit($person);
    $source_id = add_source($person, $tag, $event, $src, $txt);
    if ($tag == 31) // hard-coded reference to probate
        pg_query("SELECT generate_probate_witnesses($event)");
    if ($_POST['age']) // generate birth event
        add_birth($person, $event_date, $_POST['age'], $source_id);
    pg_query("COMMIT");
    header("Location: $app_root/family.php?person=$person");
}

?>

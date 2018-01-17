<?php

/***************************************************************************
 *   event_insert.php                                                      *
 *   Yggdrasil: Event Insert Form and Action                               *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "../settings/settings.php";
require_once "../langs/$language.php";
require "../functions.php";
require "./forms.php";

if (!isset($_POST['posted'])) {
    // display form
    $note_height = 5;
    $note_width = 100;
    $person = $_GET['person'];
    $name = get_name($person);
    $title = "gettext(Add event for) ($person) $name";
    require "./form_header.php";
    echo "<h2>$title</h2>\n";
    form_begin('insert_event', $_SERVER['PHP_SELF']);
    hidden_input('posted', 1);
    hidden_input('person', $person);
    select_tag(0, 0, 0);
    participant_input(0);
    select_place(0);
    date_input();
    textarea_input("gettext(Text:)", $note_height, $note_width, 'event_note', '', 4);
    source_input();
    text_input("gettext(Age:)", 10, 'age', '', "(gettext(Adds birth event))", 7);
    form_submit();
    form_end();
    echo "</body>\n</html>\n";
}
else {
    $src = $_POST['source_id'];
    $txt = $_POST['source_text'];
    if ($txt && fetch_val("SELECT is_leaf($src)") == 't') {
        echo "gettext(Cannot create subsource under source) #$src. ";
        echo "gettext(Please go back and check your source reference.)";
        die;
    }
    // process form
    $person = $_POST['person'];
    $event_note = note_to_db($_POST['event_note']);
    $event_date = pad_date($_POST['date_1']) . $_POST['date_type'] . pad_date($_POST['date_2']) . '1';
    $sort_date = parse_sort_date($_POST['sort_date'],$event_date);
    $place = $_POST['place_fk'];
    if ($place == 0) $place = 1;
    $tag = $_POST['tag_fk'];
    pg_query("BEGIN");
    $event = fetch_val("
        INSERT INTO events (
            tag_fk,
            place_fk,
            event_date,
            sort_date,
            event_note
        )
        VALUES (
            $tag,
            $place,
            '$event_date',
            '$sort_date',
            '$event_note'
        )
        RETURNING event_id
    ");
    set_last_selected_place($place);
    add_participant($person, $event);
    if ($_POST['coprincipal'] && has_coprincipal($tag)) {
        // constrain to events which allows for coprincipal, ie tag_type = 2
        $coprincipal = $_POST['coprincipal'];
        add_participant($coprincipal, $event);
    }
    if ($tag == 31) // hard-coded reference to probate
        pg_query("SELECT generate_probate_witnesses($event)");
    $source_id = add_source($person, $tag, $event, $src, note_to_db($txt));
    $age = $_POST['age'];
    if ($age && is_numeric($age)) // generate birth event
        add_birth($person, $event_date, $age, $source_id);
    if ($tag == 3) { // hard-coded death tag, check if died young
        if ((died_young($person) || ($age && $age < 16))
            && fetch_val("SELECT dead_child($person)") == 'f') {
            pg_query("INSERT INTO dead_children (person_fk) VALUES ($person)");
            pg_query("UPDATE persons SET toponym='' WHERE person_id = $person");
        }
    }
    pg_query("COMMIT");
    header("Location: $app_root/family.php?person=$person");
}

?>

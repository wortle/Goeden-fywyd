<?php

/***************************************************************************
 *   source_edit.php                                                       *
 *   Yggdrasil: Source Update Form                                         *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "../settings/settings.php";
require_once "../langs/$language.php";
require "../functions.php";
require "./forms.php";

if (!isset($_POST['posted'])) {
    $person = $_GET['person'];
    $source = $_GET['source'];
    $self =  isset($_GET['self']) ? $_GET['self'] : 0;
    $title = "gettext(Edit source) #$source";
    $template = fetch_val("SELECT template FROM templates WHERE source_fk = $source");
    $form = 'source_edit';
    $focus = 'text';
    require "./form_header.php";
    $row = fetch_row_assoc("SELECT * FROM sources WHERE source_id = $source");
    $psource = $row['parent_id'];
    $text = $row['source_text'];
    $ret = $self ? $source : $psource;
    $sort = $row['sort_order'];
    $source_date = $row['source_date'];
    $part_type = $row['part_type'] ? $row['part_type'] : 0;
    $ch_part_type = $row['ch_part_type'] ? $row['ch_part_type'] : 0;
    $spt_label = fetch_val("SELECT get_spt_label($part_type)");
    echo "<h2>gettext(Edit source) $source ($spt_label)</h2>\n";
    echo "<p><a href=\"../source_manager.php?node=$source\">gettext(To Source Manager)</a></p>";
    echo "<p>" . str_replace('./family.php', '../family.php', get_source_text($source)) . "</p>\n";
    form_begin($form, $_SERVER['PHP_SELF']);
    hidden_input('posted', 1);
    hidden_input('person', $person);
    hidden_input('source', $source);
    hidden_input('ret', $ret);
    source_num_input("gettext(Parent node:)", 'psource', $psource);
    editarea_input("gettext(Text:)", 20, 100, 'text', $text);
    if (fetch_val("SELECT is_leaf($source)") == 'f') {
        textarea_input('Template:', 3, 100, 'template', $template);
        select_source_type("Type", 'part_type', $part_type);
        select_source_type("$_Subtype:", 'ch_part_type', $ch_part_type);
    }
    else {
        hidden_input('template', false);
        hidden_input('part_type', $part_type);
        hidden_input('ch_part_type', 0);
    }
    text_input("gettext(Sort order:)", 20, 'sort', $sort);
    text_input("gettext(Source date:)", 20, 'source_date', $source_date);
    form_submit();
    form_end();
    $row = fetch_row("SELECT ecc($source), rcc($source), ssc($source)");
    printf ("<p>gettext(There_are) %s %s, %s %s gettext(and) %s %s gettext(associated with this source.)</p>",
        $row[0], ($row[0]==1 ? $_event : $_events),
        $row[1], ($row[1]==1 ? $_relation : $_relations),
        $row[2], ($row[2]==1 ? $_subsource : $_subsources));
    echo "</body>\n</html>\n";
}
else {
    $person = $_POST['person'];
    $source = $_POST['source'];
    $text = $_POST['text'];
    $source_date = $_POST['source_date']
        ? $_POST['source_date']
        : fetch_val("SELECT true_date_extract('$text')");
    $psource = $_POST['psource'] ? $_POST['psource'] : 0;
    $sort = $_POST['sort'] ? $_POST['sort'] : 1;
    $part_type = $_POST['part_type'];
    $ch_part_type = isset($_POST['ch_part_type']) ? $_POST['ch_part_type'] : 0;
    $sort = get_sort($psource, $text, $sort);
    $text = note_to_db($text);
    $ret = $_POST['ret'];
    $template = isset($_POST['template']) ? $_POST['template'] : false;
    if ($template) {
        if (fetch_val("
                SELECT count(*)
                FROM templates
                WHERE source_fk = $source
            "))
            pg_query("
                UPDATE templates
                SET template = '$template'
                WHERE source_fk = $source
            ");
        else
            pg_query("
                INSERT INTO templates (source_fk, template)
                VALUES ($source, '$template')
            ");
    }
    // this is a freak situation that arises when $psource = 0
    // and a rather brute hack to remedy it.
    if ($source == $psource)
        $psource = 0;
    pg_prepare("query",
        "UPDATE sources SET
            parent_id = $1,
            sort_order = $2,
            source_text = $3,
            source_date = $4,
            part_type = $5,
            ch_part_type = $6
        WHERE source_id = $7"
    );
    pg_execute("query",
        array(
            $psource,
            $sort,
            $text,
            $source_date,
            $part_type,
            $ch_part_type,
            $source
        )
    );
   // this script is called from two different locations.
   // One sets $person, the other doesn't.
    if ($person) {
        header("Location: $app_root/family.php?person=$person");
    }
    else {
        header("Location: $app_root/source_manager.php?node=$ret");
    }
}

?>

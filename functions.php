<?php

/***************************************************************************
 *   functions.php                                                         *
 *   Yggdrasil: Common Functions                                           *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

//#########################################################################
// basic db retrieval wrapper functions
//#########################################################################

function fetch_val($query) {
    // wrapper func, gets single value from db
    $result = pg_query($query);
    $row = pg_fetch_row($result);
    return $row[0];
}

function fetch_row($query) {
    // wrapper func, gets single row from db
    $result = pg_query($query);
    $row = pg_fetch_row($result);
    return $row;
}

function fetch_row_assoc($query) {
    // wrapper func, gets single row from db
    $result = pg_query($query);
    $row = pg_fetch_assoc($result);
    return $row;
}

function fetch_num_rows($query) {
    // wrapper func, gets number of rows from db
    $result = pg_query($query);
    return pg_num_rows($result);
}

function get_next($entity) {
    // takes entity name, returns new id number
    // works because of consistent naming scheme
    return fetch_val("
        SELECT COALESCE(MAX(" . $entity . "_id), 0) + 1
        FROM " . $entity . "s");
}

//#########################################################################
// date functions
//#########################################################################

function mydate($datestring) {
    // takes iso-8601 date of format 'yyyy-mm-dd'
    // returns localised date string
    global $language;
    return fetch_val("SELECT mydate('$datestring', '$language')");
}
function fuzzydate($datestring) {
    // takes internal "fuzzy date" char(18) string
    // returns localised date string
    global $language;
    return fetch_val("SELECT fuzzydate('$datestring', '$language')");
}

function parse_sort_date($sdate, $edate) {
    // takes input from sort date and event date, returns valid date string
    if ($sdate && $sdate[0] == '!') // override if sort date preceded by exclamation mark
        return substr($sdate, 1, 8);
    // if a sort date is entered, and event date is blank or contains only year
    if ($sdate && (!$edate || substr($edate, 4, 4) == '0000')) {
        $sort_date = $sdate;
        if (strlen($sort_date) == 4)
            $sort_date .= '0101'; // append month and day to bare year
    }
    else { // build a valid sort date from the main date
        if ($edate) { // if the main date exists
            $sort_date = substr($edate, 0, 8);
            if (substr($sort_date, 0, 4) == '0000')
                $sort_date[3] = '1';
            if (substr($sort_date, 4, 2) == '00')
                $sort_date[5] = '1';
            if (substr($sort_date, 6, 2) == '00')
                $sort_date[7] = '1';
        }
        else {
            $sort_date = '00010101';
        }
    }
    return $sort_date;
}

function trim_date($fdate) {
    // trims empty days, months and years from YYYYMMDD string
    if (substr($fdate, 6, 2) == '00') {
        $fdate = substr($fdate, 0, 6);
        if (substr($fdate, 4, 2) == '00') {
            $fdate = substr($fdate, 0, 4);
            if (substr($fdate, 0, 4) == '0000')
                $fdate = '';
        }
    }
    return $fdate;
}

function pad_date($fdate) {
    // the reverse operation of previous func
    while (strlen($fdate) < 8)
        $fdate .= '0';
    return $fdate;
}

function year_comp($s1, $s2, $limit=10) {
    // compare two dates and return true if the difference is within limit.
    if (!$s1 || !$s2) // if one birth date is missing
        return true;
    if (abs($s1 - $s2) <= $limit)
        return true;
    else
        return false;
}

function died_young($p) {
    if (fetch_val("SELECT age_at_death($p)") < 15)
        return TRUE;
    else
        return FALSE;
}

//#########################################################################
// misc simple data retrieval functions
//#########################################################################

function get_name($p) {
    return fetch_val("SELECT get_person_name($p)");
}

function get_place($pl) {
    return fetch_val("SELECT get_place_name($pl)");
}

function get_tag_name($n) {
    global $language;
    if ($language == 'nb')
        return fetch_val("SELECT tag_label FROM tags WHERE tag_id = $n");
    elseif ($language == 'en')
        return fetch_val("SELECT tag_name FROM tags WHERE tag_id = $n");
}

function lifespan($born, $died) {
return paren(fuzzydate($born) . '&nbsp;-&nbsp;' . fuzzydate($died));
}

function get_name_and_dates($url, $p) {
    global $_unregistered;
    if ($url == '')
        $url = $_SERVER['PHP_SELF'];
    if (!$p) // $p == 0
        return "[$_unregistered]";
    $row = fetch_row_assoc("
        SELECT
            name,
            born,
            died
        FROM
            name_and_dates
        WHERE
            person = $p
    ");
    return to_url($url, array('person' => $p), $row['name'], get_name_and_lifespan($p))
        . conc(lifespan($row['born'], $row['died']));
}

function get_name_and_lifespan($p) {
    global $_unregistered;
    if (!$p) // $p == 0
        return "[$_unregistered]";
    $row = fetch_row_assoc("
        SELECT
            name,
            born,
            died
        FROM
            name_and_dates
        WHERE person = $p
    ");
    return $row['name'] . conc(lifespan($row['born'], $row['died']));
}

function get_parents($p) {
    $row = fetch_row_assoc("
        SELECT
            get_parent($p,1) AS father,
            get_parent($p,2) AS mother
    ");
    $parents[0] = get_name_and_dates('', $row['father']);
    $parents[1] = get_name_and_dates('', $row['mother']);
    return $parents;
}

function has_parents($p) {
    if ($p)
        return fetch_val("
            SELECT COUNT(*) FROM relations WHERE child_fk = $p
        ");
    else
        return 0;
}

function has_descendants($p) {
    if ($p)
        return fetch_val("
            SELECT COUNT(*) FROM relations WHERE parent_fk = $p
        ");
    else
        return 0;
}

function has_spouses($p) {
    if ($p)
        return fetch_val("
            SELECT COUNT(*) FROM marriages WHERE person = $p
        ");
    else
        return 0;
}

function get_second_principal($event, $person) {
    return fetch_val("SELECT get_principal($event,$person)");
}

function get_connection_count($person) {
    return fetch_val("SELECT conn_count($person)");
}

function get_coparent($p, $q) {
    return fetch_val("SELECT get_coparent($p, $q)");
}

function get_relation_id($p, $g) {
    return fetch_val("
        SELECT
            relation_id
        FROM
            relations
        WHERE
            child_fk = $p
        AND
            get_gender(parent_fk) = $g
    ");
}

function find_father($p) {
    return fetch_val("SELECT get_parent($p,1)");
}

function find_mother($p) {
    return fetch_val("SELECT get_parent($p,2)");
}

function get_gender($p) {
    return fetch_val("SELECT get_gender($p)");
}

function get_source_text($source_id) {
    return fetch_val("SELECT get_source_text($source_id)");
}

function has_coprincipal($tag) {
    return fetch_val("SELECT has_coprincipal($tag)");
}

function get_source_principal($node) {
    return fetch_val("
        SELECT person_fk
        FROM source_linkage
        WHERE source_fk = $node AND role_fk = 1
    ");
}

//#########################################################################
// string functions
//#########################################################################

function to_url($base_url, $params, $txt, $title='') {
    $str = '<a href="' . $base_url;
    if ($params) {
        foreach ($params as $key => $value)
            $pairs[] = $key . '=' . $value;
        $str .= '?' . join($pairs, '&amp;');
    }
    $str .= '"';
    if ($title)
        $str .= ' title="' . $title . '"';
    $str .= '>' . $txt . '</a>';
    return $str;
}

function fonetik($s) {
    // a minimal phonetic comparison routine
    $s = strtoupper($s); // convert to upper case
    $s = str_replace('Å','AA', $s);
    $s = str_replace('CH','K', $s);
    $s = str_replace('CA','KA', $s);
    $s = str_replace('CE','SE', $s);
    $s = str_replace('CI','SI', $s);
    $s = str_replace('CO','KO', $s);
    $s = str_replace('CU','KU', $s);
    $s = str_replace('TH','T', $s);
    $s = str_replace('ELISA', 'LIS', $s);
    $s = str_replace('ENGE', 'INGE', $s);
    $s = str_replace('KAREN', 'KARI', $s);
    $s = str_replace('MAREN', 'MARI', $s);
    return soundex($s);
}

function soundex_comp($s1, $s2, $limit=20) {
    // separate first character and numeric part of the two soundexes and compare them.
    // if difference is within acceptable bounds, return true, else return false.
    $first_char_1 = $s1{0};
    $first_char_2 = $s2{0};
    $number_1 = substr($s1, 1);
    $number_2 = substr($s2, 1);
    if ($first_char_1 == $first_char_2 && abs($number_1 - $number_2) <= $limit)
        return true;
    else
        return false;
}

function fixamp($str) {
    // nifty expression to replace naked &s with &amp;s. keeps W3C validator happy.
    // included here for compatibility with Slekta(SQL).
    $str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&amp;", $str);
    return $str;
}

function linked_name($p, $url='./family.php') {
    global $_Link_to_person;
    return square_brace($p)
        . conc(to_url($url, array('person' => $p),
                    get_name($p),
                    get_name_and_lifespan($p))
        );
}

function child_of($p, $url='./family.php') {
    global $_son, $_daughter, $_child, $_of, $_and;
    $str = '';
    if (has_parents($p)) {
        $gender = get_gender($p);
        if ($gender == 0) $ch = $_child;
        elseif ($gender == 1) $ch = $_son;
        elseif ($gender == 2) $ch = $_daughter;
        $father = find_father($p);
        $mother = find_mother($p);
        if ($father && $mother)
            $str = paren("$ch $_of " .
                linked_name($father, $url) . " $_and " .
                linked_name($mother, $url)
            );
        elseif ($father)
            $str = paren("$ch $_of " . linked_name($father, $url));
        elseif ($mother)
            $str = paren("$ch $_of " . linked_name($mother, $url));
    }
    return $str;
}


function gname($gnum) {
    // takes gender number, returns localized gender name
    global $_Unknown, $_Male, $_Female, $_NslashA;
    if ($gnum==0) return span_type($_Unknown, "alert");
    if ($gnum==1) return $_Male;
    if ($gnum==2) return $_Female;
    if ($gnum==9) return $_NslashA;
}

function yesno($yn) {
    // takes untyped value, returns localized yes or no
    global $_Yes, $_No;
    if ($yn == 't' || $yn == strtoupper('Y'))
        return $_Yes;
    else
        return $_No;
}

function char_conv($str) {
    // converts some hard-to type html entities from text input

/*
I tried:
    $str = str_replace('--', '&#8212;', $str); // '--' -> mdash

but mdash is a very problematic character. It doesn't exist in iso 8859-1.
In UNICODE it's #8212, but in several Windows charsets it's placed at #151.
Cutting & pasting, even between random GNU/Linux applications, may put your
encoding in an illegal state. Don't use it.
*/

    $str = str_replace('<<<', '&laquo;<', $str);
    $str = str_replace('>>>', '>&raquo;', $str);
    $str = str_replace('<<', '&laquo;', $str);
    $str = str_replace('>>', '&raquo;', $str);
    $str = str_replace('1//2', '&frac12;', $str);
    $str = str_replace('1//4', '&frac14;', $str);
    $str = str_replace('3//4', '&frac34;', $str);
    // strip superfluous http marker from urls
    $str = str_replace('http:', '', $str);
    return $str;
}

function note_to_db($str) {
    $_initials = fetch_val("
        SELECT
            initials
        FROM
            user_settings
        WHERE
            username = current_user
    ");
    $str = rtrim(char_conv($str));
    $str = str_replace("\n", '', $str); // strip newlines
    $str = str_replace("\r", '', $str); // I don't see why PHP needs this.
    // convert double linebreaks to paragraphs
    $str = str_replace('<br /><br />', '</p><p>', $str);
    // expand "pseudo-tagging"
    $str = str_replace('<note>', '<span class="note">(', $str);
    $str = str_replace('</note>', " $_initials)</span>", $str);
    return pg_escape_string($str);
    // return $str;
}

function note_from_db($str) {
    // Format text from db for editing. Insert some "air"
    // newlines should be stripped by note_to_db() above at return time
    $str = str_replace('<br />', "<br />\n", $str);
    $str = str_replace('</p><p', "</p>\n\n<p", $str);
    $str = str_replace('<ul>', "\n<ul>\n", $str);
    $str = str_replace('</p><ul', "</p>\n\n<ul", $str);
    $str = str_replace('</p><ol', "</p>\n\n<ol", $str);
    $str = str_replace('</ol>', "</ol>\n", $str);
    $str = str_replace('</ul>', "</ul>\n", $str);
    $str = str_replace('</li>', "</li>\n", $str);
    return $str;
}

function researcher_info($file) {
    // print researcher info at the end of a report
    $fh = fopen($file, 'r');
    echo "<p class=\"bmd\">";
    while ($line = fgets($fh)) {
        echo "$line<br />\n";
    }
    echo "</p>\n";
    fclose($fh);
}

function get_source_plain_text($n) {
    // get source text as plain text, eg. for titles
     return strip_tags(fetch_val("
        SELECT
            link_expand(source_text)
        FROM
            sources
        WHERE
            source_id = $n"
    ));
}

function conc($str, $prefix=' ') {
    if ($str)
        return $prefix . $str;
    else
        return '';
}

function bold($str) {
    return '<b>' . $str . '</b>';
}

function italic($str) {
    return '<i>' . $str . '</i>';
}

function sup($str) {
    return '<sup>' . $str . '</sup>';
}

function square_brace($str) {
    return '[' . $str . ']';
}

function curly_brace($str) {
    return '{' . $str . '}';
}

function paren($str) {
    return '(' . $str . ')';
}

function span_type($str, $cls, $help='') {
    if ($help) {
        return "<span class=\"$cls\" title=\"$help\">" . $str . '</span>';
    }
    else {
        return "<span class=\"$cls\">" . $str . '</span>';
    }
}

function para($str, $type='') {
    $t = $type ? " class=\"$type\"" : '';
    return "<p$t>" . $str . "</p>\n";
}

function li($str, $type='') {
    $t = $type ? " class=\"$type\"" : '';
    return "<li$t>" . $str . "</li>\n";
}

function int_5($n) {
    return sprintf("%05d", $n);
}

function td($str) {
    return '<td valign="top">' . $str . '</td>';
}
function td_numeric($str) {
    return '<td class="numeric" valign="top"><code>' . $str . '</code></td>';
}

//#########################################################################
// data entry functions
//#########################################################################

function set_last_edit($person) {
    pg_query("UPDATE persons SET last_edit = NOW() WHERE person_id = $person");
}

function set_last_selected_source($source_id) {
    // in a future multi-user version, this code should be replaced
    // with a user-dependent setting, eg a cookie.
    pg_query("UPDATE user_settings SET last_selected_source = $source_id WHERE username = current_user");
}

function get_last_selected_source() {
    // in a future multi-user version, this code should be replaced
    // with a user-dependent setting, eg a cookie.
    return fetch_val("SELECT last_selected_source FROM user_settings WHERE username = current_user");
}

function set_last_selected_place($place) {
    pg_query("SELECT set_last_selected_place($place)");
}

function get_last_selected_place() {
    return fetch_val("SELECT place_fk FROM recent_places ORDER BY id DESC LIMIT 1");
}

function get_sort($id, &$text, $sort) {
    // parses sort order from text, returns sort order, modifies text
    // rewritten as wrapper for plgpgsql get_sort() function
    // note that $id is the parent id of a new source.
    $row = fetch_row_assoc("select * from get_sort($id, $sort, '$text')");
    $text = $row['string'];
    return $row['number'];
}

function add_source($person, $tag, $event, $source_id, $text, $sort=1) {
/*
Inserts sources and citations depending on input, returns current source_id
NOTE: To avoid breakage, NEVER call this routine outside of a transaction.
Update 2009-03-26: The major logic now has been moved to plpgsql, and this func
is left as a wrapper. Cf. ddl/functions.sql.
*/
    if (!$source_id && !$text) // don't bother if nothing has been entered.
        return 0;
    else {
        $text = note_to_db($text);
        return fetch_val("SELECT add_source($person, $tag, $event, $source_id, '$text', $sort)");
    }
}

function add_participant($person, $event) {
    pg_query("SELECT add_participant($person, $event)");
}

function add_birth($person, $date, $age, $source) {
    // 2009-04-03: the logic has been moved to the plpgsql function add_birth.
    return fetch_val("SELECT add_birth($person, '$date', $age, $source)");
}

function list_participants($event) {
    $handle = pg_query("SELECT person_fk, sort_order, is_principal
                            FROM participants
                            WHERE event_fk = $event
                            ORDER BY sort_order");
    while ($row = pg_fetch_row($handle)) {
        $bp = $row[2] == 'f' ? 'B' : '';
        $p_list[] = square_brace($bp . $row[1])
            . linked_name($row[0], './family.php');
    }
    return join($p_list, ', ');
}

function get_participant_note($p, $e) {
    return fetch_val("SELECT COALESCE(
                        (SELECT link_expand(part_note)
                        FROM participant_notes
                        WHERE person_fk=$p AND event_fk=$e), '')");
}

function node_details($e, $r, $s, $u) {
    // shorthand summary for number of events, relations, subnodes / unused
    // subnodes connected to this node
    $str = " ($e-$r-$s";
    if ($u)
        $str .= "/$u";
    $str .= ")";
    return $str;
}

function list_mentioned($node, $hotlink=0) {
    global $app_path, $_edit, $_delete;
    echo "<ol>\n";
    $handle = pg_query("
        SELECT
            per_id,
            get_role(role_fk) AS rolle,
            person_fk,
            get_surety(surety_fk) AS surety,
            s_name,
            link_expand(sl_note) AS note
        FROM
            source_linkage
        WHERE
            source_fk = $node
        ORDER BY
            role_fk,
            per_id
    ");
    while ($row = pg_fetch_assoc($handle)) {
        echo '<li>' . $row['rolle'] . ': ';
        echo '«' . $row['s_name'] . '»';
        if ($row['person_fk'])
            echo conc(curly_brace($row['surety']))
                . conc(linked_name($row['person_fk'], "$app_path/family.php"));
            if (has_parents($row['person_fk']))
                echo conc(child_of($row['person_fk']));
        if ($row['note'])
            echo ': ' . $row['note'];
        if ($hotlink) {
            echo conc(paren(
                to_url("$app_path/forms/linkage_edit.php",
                        array(
                            'node'      => $node,
                            'id'        => $row['per_id']
                        ), $_edit)
                . ' / '
                . to_url("$app_path/forms/linkage_delete.php",
                        array(
                            'node'      => $node,
                            'id'        => $row['per_id']
                        ), $_delete)
                ));
        }
        echo "</li>\n";
    }
    if ($hotlink)
        echo '<li>'
            . to_url("$app_path/forms/linkage_add.php",
                    array('node' => $node), "Legg til lenke")
            . "</li>\n";
    echo "</ol>\n";
}

?>

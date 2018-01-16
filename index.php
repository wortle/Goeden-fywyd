<?php

/***************************************************************************
 *   index.php                                                             *
 *   Yggdrasil: Entry Page                                                 *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

require "./settings/settings.php";
require_once "./langs/$language.php";
$title = "$_Index_and_name_search";
$form = 'search';
$focus= 'given';
require "./functions.php";
require "./header.php";

$pcount = fetch_val("SELECT COUNT(*) FROM persons")
               - fetch_val("SELECT COUNT(*) FROM merged");

echo "<div class=\"normal\">";
echo "<h2>$title ($pcount $_persons)</h2>\n";

// this is a rather special form compared to the rest of the package,
// hence it doesn't use the forms.php abstractions

echo "<form id=\"search\" action=\"" . $_SERVER['PHP_SELF'] . "\">\n<div>\n";
echo "$_Given_name: <input type=\"text\" size=\"12\" name=\"given\" />\n";
echo "\"$_Surname\": <input type=\"text\" size=\"12\" name=\"surname\" />\n";
echo "$_Birth_year: <input type=\"text\" size=\"8\" name=\"bdate\" />\n";

echo "&plusmn;<select name = \"diff\">\n";
echo "<option selected=\"selected\" value=\"0\"></option>\n";
echo "<option value=\"2\">2</option>\n";
echo "<option value=\"5\">5</option>\n";
echo "<option value=\"10\">10</option>\n";
echo "<option value=\"20\">20</option>\n";
echo "</select></td></tr>\n";

echo "<input type=\"submit\" value=\"$_Search\" />\n";
echo "</div>\n</form>\n\n";

// note that for reasons of convenience, a search for 'surname'
// will include patronym, toponym, surname, and occupation.

if (isset($_GET['given'])) $given   = $_GET['given'];
if (isset($_GET['surname'])) $surname = $_GET['surname'];
if (isset($_GET['bdate'])) $bdate   = $_GET['bdate'];
if (isset($_GET['diff'])) $diff = $_GET['diff'];

// by default, we will display the 50 most recently edited persons.
if(!isset($given)&&!isset($surname)) {
    $headline = "$_The_last_50_edited";

// This query is sluggish without the following db modification:
// create index last_edited_persons_key on persons(last_edit,person_id);
   $query = "select person_id, last_edit from persons
               where is_merged(person_id) is false
               order by last_edit desc, person_id desc limit 50";
}
else {
    if (substr($surname, 0, 1) == '!')
        $literal = ltrim($surname, '!');
    else
        $literal = "%$surname%";
    $headline = "$_Search_result";
    $query =
        "SELECT
            person_id,
            get_pbdate(person_id) as pbd
        FROM
            persons
        WHERE
            given LIKE '%$given%'
            AND (
                patronym LIKE '%$surname%'
                OR toponym LIKE '$literal'
                OR surname LIKE '%$surname%'
                OR occupation LIKE '%$surname%'
            )
            AND is_merged(person_id) IS FALSE
        ";
    if ($bdate)
        $query .= "
            AND f_year(get_pbdate(person_id))
                    BETWEEN (($bdate)::INTEGER - $diff)
                    AND (($bdate)::INTEGER + $diff)
            ";
$query .= "
    ORDER BY pbd";
}

echo "<h3>$headline:</h3>\n";
$handle = pg_query($query);
echo "<p>";
while ($row = pg_fetch_row($handle)) {
    $p = $row[0];
    echo get_name_and_dates("./family.php", $p)
        . conc(child_of($p))
        . "<br />\n";
}
echo "</p>\n";
echo para(paren(fetch_num_rows($query)
    . conc($_persons)));
echo "</div>\n";
include "./footer.php";
?>

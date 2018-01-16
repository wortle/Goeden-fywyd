<?php

/***************************************************************************
 *   settings.php                                                          *
 *   Yggdrasil: DB Connection and "global" settings                        *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

// before you start working with your database, you *must* edit this file!

//db parameters
    $host = "localhost";
    $dbname = "pgslekt";
    $username = "leif";
//    $password = "";

    $db = pg_connect("host=$host dbname=$dbname user=$username")
        or die('Could not connect: ' . pg_last_error());

// frequently used event types
    define("BIRT", 2);
    define("DEAT", 3);
    define("MARR", 4);

// application path
    $app_path = "/~leif/yggdrasil";

// application root
    $app_root = 'http://' . $_SERVER['SERVER_NAME'] . $app_path;

$handle = pg_query("
    SELECT
        initials,
        user_lang
    FROM
        user_settings
    WHERE
        username=current_user
");
$row = pg_fetch_assoc($handle);


// user initials
    $_initials = $row['initials'];

// user language
    $language = $row['user_lang'];

// set default timezone
// DEPRECATED: Set timezone globally in php.ini instead, eg.
// date.timezone = 'Europe/Oslo'

// date_default_timezone_set($row['user_tz']);

// set internal PHP encoding to UTF-8
    mb_internal_encoding("UTF-8");

// set up vars for header.php menu buttons
    $person = false;
    $family = false;
    $pedigree = false;
    $descendants = false;
    $source_manager = false;

?>

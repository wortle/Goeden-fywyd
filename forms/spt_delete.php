<?php

/***************************************************************************
 *   spt_delete.php                                                        *
 *   Yggdrasil: Delete Source Part Type                                    *
 *                                                                         *
 *   Copyright (C) 2011 by Leif B. Kristensen <leif@solumslekt.org>        *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

// This script will delete a source part type. It is callable from
// spt_manager.php iff there are no associated sources.

require "../settings/settings.php";
require "../functions.php";

$spt = $_GET['spt'];

pg_query("
    DELETE FROM source_part_types
    WHERE part_type_id = $spt
");

header("Location: $app_root/spt_manager.php");

?>

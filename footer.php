<!-- Start Footer -->
<?php

/***************************************************************************
 *   footer.php                                                            *
 *   Yggdrasil: Common Page Footer                                         *
 *                                                                         *
 *   Copyright (C) 2006-2011 by Leif B. Kristensen <leif@solumslekt.org>   *
 *   All rights reserved. For terms of use, see LICENSE.txt                *
 ***************************************************************************/

$time_end = getmicrotime();
$mtime = number_format(($time_end - $time_start),3);
print ("<p class=\"bluebox\">gettext(This page was generated in $mtime seconds).</p>\n");
echo "</body>\n</html>\n";

?>


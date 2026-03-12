<?php
/* phpMyProfiler
 * Copyright (C) 2005-2014 The phpMyProfiler project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 */

define("_PMP_REL_PATH", "..");

$pmp_module = "admin_getcover";

require_once "../config.inc.php";
require_once "../admin/include/functions.php";
require_once "../include/pmp_Smarty.class.php";

$cover_id = preg_replace(
    "/[^A-Za-z0-9]/",
    "",
    request_string($_GET, "cover", ""),
);
if ($cover_id === "") {
    exit();
}

isadmin();
admin_require_csrf();

$smarty = new pmp_Smarty();
$smarty->loadFilter("output", "trimwhitespace");
$smarty->compile_dir = "../templates_c";

if (isset($_GET["nohead"])) {
    $smarty->assign("nohead", true);
} else {
    $smarty->assign("header", t("Get Cover"));
    $smarty->assign("header_img", "getcover");
    $smarty->assign("session", "");
}

if (!is_writable("../cover/")) {
    $smarty->assign("Error", t('No write access in folder "cover".'));
} else {
    $cover = getRemoteContent(
        "http://www.invelos.com/mpimages/" .
            substr($cover_id, 0, 2) .
            "/" .
            $cover_id .
            ".jpg",
    );

    if (!empty($cover)) {
        @file_put_contents("../cover/" . $cover_id . ".jpg", $cover);
        $smarty->assign("cover", $cover_id);
    } else {
        $smarty->assign(
            "Error",
            t(
                'Can\'t download the cover. Perhaps there is no cover for this DVD or your webserver doesn\'t allow accessing remote content.',
            ),
        );
    }
}

$smarty->display("admin/getcover.tpl");
?>

<?php
/* phpMyProfiler
 * Copyright (C) 2004 by Tim Reckmann [www.reckmann.org] & Powerplant [www.powerplant.de]
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

$pmp_module = "admin_review";

require_once "../config.inc.php";
require_once "../admin/include/functions.php";
require_once "../include/pmp_Smarty.class.php";
require_once "../include/smallDVD.class.php";

isadmin();

$smarty = new pmp_Smarty();
$smarty->loadFilter("output", "trimwhitespace");
$smarty->compile_dir = "../templates_c";
admin_assign_csrf($smarty);

$smarty->assign("header", t("Reviews"));
$smarty->assign("header_img", "reviews");
$smarty->assign("session", "");

dbconnect();

if (request_int($_GET, "id", 0, 1) > 0) {
    $review_id = request_int($_GET, "id", 0, 1);
    switch ($_GET["action"]) {
        case "delete":
            admin_require_post_csrf();
            $sql = "SELECT * FROM pmp_reviews WHERE id = ?";
            $res = dbexec_prepared($sql, "i", [$review_id]);
            $row = mysql_fetch_object($res);
            $sql = "DELETE FROM pmp_reviews WHERE id = ?";
            $res = dbexec_prepared($sql, "i", [$review_id]);

            $smarty->assign("Success", t("Review deleted."));
            break;

        case "activate":
            admin_require_post_csrf();
            $sql = "UPDATE pmp_reviews SET status = 1 WHERE id = ?";
            $res = dbexec_prepared($sql, "i", [$review_id]);

            $sql = "SELECT * FROM pmp_reviews WHERE id = ?";
            $res = dbexec_prepared($sql, "i", [$review_id]);
            $row = mysql_fetch_object($res);

            $smarty->assign("Success", t("Review activated."));
            break;
    }
} elseif (isset($_GET["action"]) && $_GET["action"] == "allactivate") {
    admin_require_post_csrf();
    $sql = "UPDATE pmp_reviews SET status = 1 WHERE status = 0 ORDER BY id ASC";
    $res = dbexec($sql);

    $smarty->assign("Success", t("All Review entries activated."));
}

$sql = "SELECT * FROM pmp_reviews WHERE status = 0";
$res = dbexec($sql);

$pending = [];

if (mysql_num_rows($res) > 0) {
    while ($row = mysql_fetch_object($res)) {
        $row->date = strftime($pmp_dateformat, strtotime($row->date));
        $row->name = htmlentities($row->name);
        $row->text = htmlentities($row->text, ENT_QUOTES, "UTF-8");
        $row->dvd = new smallDVD($row->film_id);
        $pending[] = $row;
    }
}
$smarty->assign("pending", $pending);

$sql = "SELECT * FROM pmp_reviews WHERE status = 1 ORDER BY id DESC";
$res = dbexec($sql);

$active = [];

if (mysql_num_rows($res) > 0) {
    while ($row = mysql_fetch_object($res)) {
        $row->date = strftime($pmp_dateformat, strtotime($row->date));
        $row->name = htmlentities($row->name);
        $row->text = htmlentities($row->text, ENT_QUOTES, "UTF-8");
        $row->dvd = new smallDVD($row->film_id);
        $active[] = $row;
    }
}
$smarty->assign("active", $active);

$smarty->display("admin/reviews.tpl");
?>

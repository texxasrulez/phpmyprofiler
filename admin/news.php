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

$pmp_module = "admin_news";

require_once "../config.inc.php";
require_once "../admin/include/functions.php";
require_once "../include/pmp_Smarty.class.php";

isadmin();

$smarty = new pmp_Smarty();
$smarty->loadFilter("output", "trimwhitespace");
$smarty->compile_dir = "../templates_c";
admin_assign_csrf($smarty);

$smarty->assign("header", t("News"));
$smarty->assign("header_img", "news");
$smarty->assign("session", "");

dbconnect();

if (isset($_GET["action"])) {
    $news_id = request_int($_GET, "id", 0, 1);
    switch ($_GET["action"]) {
        case "delete":
            admin_require_post_csrf();
            if ($news_id > 0) {
                $sql = "DELETE FROM pmp_news WHERE id = ?";
                $res = dbexec_prepared($sql, "i", [$news_id]);
                $smarty->assign("Success", t("News deleted."));
            }
            break;

        case "add":
            $smarty->assign("editadd", "add");
            break;

        case "addsave":
            admin_require_post_csrf();
            if (empty($_POST["title"]) || empty($_POST["text"])) {
                $smarty->assign("title", $_POST["title"]);
                $smarty->assign("text", $_POST["text"]);

                if (empty($_POST["title"])) {
                    $smarty->assign("Focus", "title");
                } else {
                    $smarty->assign("Focus", "text");
                }

                $smarty->assign("Error", t("Title / Text is missing"));
                $smarty->assign("editadd", "add");
            } else {
                $sql =
                    "INSERT INTO pmp_news (date, title, text) VALUES (now(), ?, ?)";
                $res = dbexec_prepared($sql, "ss", [
                    $_POST["title"],
                    $_POST["text"],
                ]);
                $smarty->assign("Success", t("News successfully added."));
            }
            break;

        case "edit":
            if ($news_id > 0) {
                $sql = "SELECT * FROM pmp_news WHERE id = ?";
                $res = dbexec_prepared($sql, "i", [$news_id]);

                while ($row = mysql_fetch_object($res)) {
                    $edit[] = $row;
                }

                $smarty->assign("edit", $edit);
                $smarty->assign("editadd", "edit");
            }
            break;

        case "editsave":
            admin_require_post_csrf();
            if ($news_id > 0) {
                $sql =
                    "UPDATE pmp_news SET title = ?, text = ?, date = now() WHERE id = ?";
                $res = dbexec_prepared($sql, "ssi", [
                    $_POST["title"],
                    $_POST["text"],
                    $news_id,
                ]);
                $smarty->assign("Success", t("News successfully edited."));
            }
            break;
    }
}

$sql = "SELECT * FROM pmp_news ORDER BY id DESC";
$res = dbexec($sql);

$news = [];

if (mysql_num_rows($res) > 0) {
    while ($row = mysql_fetch_object($res)) {
        $row->text = nl2br($row->text);
        $row->date = strftime($pmp_dateformat, strtotime($row->date));
        $news[] = $row;
    }
} else {
    $smarty->assign("Info", t("No News available."));
}

$smarty->assign("news", $news);
$smarty->display("admin/news.tpl");
?>

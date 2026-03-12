<?php
/* phpMyProfiler
 * Copyright (C) 2008-2014 The phpMyProfiler project
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

// No direct access
defined("_PMP_REL_PATH") or
    die("Not allowed! Possible hacking attempt detected!");

// Functions only used by ACP

// Send UTF-8 header to all acp sites
header("Content-type: text/html; charset=utf-8");

require_once "../include/functions.php";

function admin_csrf_token()
{
    if (
        empty($_SESSION["admin_csrf_token"]) ||
        !is_string($_SESSION["admin_csrf_token"]) ||
        strlen($_SESSION["admin_csrf_token"]) < 32
    ) {
        if (function_exists("random_bytes")) {
            $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $_SESSION["admin_csrf_token"] = bin2hex(
                openssl_random_pseudo_bytes(32),
            );
        } else {
            $_SESSION["admin_csrf_token"] = sha1(
                uniqid((string) mt_rand(), true),
            );
        }
    }

    return $_SESSION["admin_csrf_token"];
}

function admin_csrf_validate_request()
{
    $token = "";
    if (isset($_POST["csrf_token"]) && is_scalar($_POST["csrf_token"])) {
        $token = (string) $_POST["csrf_token"];
    } elseif (isset($_GET["csrf_token"]) && is_scalar($_GET["csrf_token"])) {
        $token = (string) $_GET["csrf_token"];
    }

    if ($token === "") {
        return false;
    }

    return hash_equals(admin_csrf_token(), $token);
}

function admin_require_csrf()
{
    if (!admin_csrf_validate_request()) {
        header("HTTP/1.1 403 Forbidden");
        die("Invalid CSRF token");
    }
}

function admin_require_post_csrf()
{
    if (strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
        header("HTTP/1.1 405 Method Not Allowed");
        header("Allow: POST");
        die("Method not allowed");
    }

    admin_require_csrf();
}

function admin_assign_csrf($smarty)
{
    $token = admin_csrf_token();
    $smarty->assign("csrf_token", $token);
    $smarty->assign("csrf_token_url", "csrf_token=" . rawurlencode($token));
}

function isadmin()
{
    $_SESSION["lastside"] = basename((string) $_SERVER["PHP_SELF"]);
    if ($_SESSION["lastside"] === "") {
        $_SESSION["lastside"] = "index.php";
    }

    if (empty($_SESSION["isadmin"])) {
        header("Location:login.php");
    }
}

function checkDBState()
{
    global $pmp_dateformat;

    // Get Version of Database
    $sql =
        'SELECT id, DATE_FORMAT(date, \'' .
        $pmp_dateformat .
        ' %H:%i:%s\') as date FROM pmp_update ORDER BY id DESC LIMIT 1';
    $res = dbexec($sql, true);

    if (mysql_num_rows($res) > 0) {
        $row = mysql_fetch_object($res);
        $StateDB = $row->id;
        $StateDBTime = $row->date;
    } else {
        $StateDB = -1;
        $StateDBTime = -1;
    }

    // Look for updates
    $StateUpdate = -1;

    $handle = opendir("updates/");
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && !is_dir($file)) {
            $tmp = explode(".", $file);

            if ($tmp[count($tmp) - 1] == "upd") {
                if ($tmp[0] > $StateUpdate) {
                    $StateUpdate = $tmp[0];
                }
            }
        }
    }

    closedir($handle);

    return [
        "StateUpdate" => $StateUpdate,
        "StateDB" => $StateDB,
        "StateDBTime" => $StateDBTime,
    ];
}

function getThemes()
{
    require_once _PMP_REL_PATH . "/admin/include/theme.class.php";

    $res = [];
    $themes_dir = "../themes/";
    if (!is_dir($themes_dir)) {
        return $res;
    }

    $handle = @opendir($themes_dir);
    if ($handle) {
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != ".." && is_dir($themes_dir . $file)) {
                $tmp = new theme($file);
                if ($tmp->id !== false) {
                    $res[] = $tmp;
                }
            }
        }

        closedir($handle);
    }

    return $res;
}

// On some systems, like FreeBSD, a constant with name iconv is defined
if (!function_exists("iconv") && function_exists("libiconv")) {
    function iconv($input_encoding, $output_encoding, $string)
    {
        return libiconv($input_encoding, $output_encoding, $string);
    }
}

function getCurrencies()
{
    dbconnect(false);
    $res = @dbexec("SELECT id FROM pmp_rates ORDER BY id", true);

    // The Euro is not in the list, because it's the base currency
    $rates["EUR"] = "EUR";

    if ($res) {
        if (mysql_num_rows($res) > 0) {
            while ($row = mysql_fetch_object($res)) {
                $rates[$row->id] = $row->id;
            }
        }
    } else {
        // no access to database, use default values
        $rates["USD"] = "USD";
        $rates["JPY"] = "JPY";
        $rates["BGN"] = "BGN";
        $rates["CZK"] = "CZK";
        $rates["DKK"] = "DKK";
        $rates["EEK"] = "EEK";
        $rates["GBP"] = "GBP";
        $rates["HUF"] = "HUF";
        $rates["LTL"] = "LTL";
        $rates["LVL"] = "LVL";
        $rates["PLN"] = "PLN";
        $rates["RON"] = "RON";
        $rates["SEK"] = "SEK";
        $rates["CHF"] = "CHF";
        $rates["NOK"] = "NOK";
        $rates["HRK"] = "HRK";
        $rates["RUB"] = "RUB";
        $rates["TRY"] = "TRY";
        $rates["AUD"] = "AUD";
        $rates["BRL"] = "BRL";
        $rates["CAD"] = "CAD";
        $rates["CNY"] = "CNY";
        $rates["HKD"] = "HKD";
        $rates["IDR"] = "IDR";
        $rates["INR"] = "INR";
        $rates["KRW"] = "KRW";
        $rates["MXN"] = "MXN";
        $rates["MYR"] = "MYR";
        $rates["NZD"] = "NZD";
        $rates["PHP"] = "PHP";
        $rates["SGD"] = "SGD";
        $rates["THB"] = "THB";
        $rates["ZAR"] = "ZAR";
        $rates["ISK"] = "ISK";
    }

    return $rates;
}

function uncompress_file($infilename)
{
    $path_parts = pathinfo($infilename);
    $ext = "." . $path_parts["extension"];
    $name = $path_parts["filename"];

    if ($ext == ".zip") {
        return extractZip($infilename);
    } elseif ($ext == ".bz2") {
        return extractBzip2($infilename, $name);
    }

    return false;
}

function extractZip($infilename)
{
    if (!class_exists("ZipArchive")) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($infilename) !== true) {
        return false;
    }

    $ok = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        if ($entry === false) {
            continue;
        }

        // Block zip-slip and hidden traversal paths.
        if (
            strpos($entry, "../") !== false ||
            strpos($entry, "..\\") !== false ||
            strpos($entry, ":") !== false
        ) {
            continue;
        }

        $basename = basename($entry);
        if ($basename === "") {
            continue;
        }

        $stream = $zip->getStream($entry);
        if ($stream === false) {
            continue;
        }

        $content = stream_get_contents($stream);
        fclose($stream);
        if ($content === false) {
            continue;
        }

        $target = "./" . $basename;
        if (file_put_contents($target, $content) !== false) {
            $ok = true;
        }
    }

    $zip->close();

    return $ok;
}

function extractBzip2($infilename, $name)
{
    $in_file = bzopen($infilename, "r");

    if (is_resource($in_file)) {
        $out_file = fopen($name, "w");
        $buffer = "";

        while ($buffer = bzread($in_file, 4096)) {
            fwrite($out_file, $buffer, 4096);
        }

        bzclose($in_file);
        fclose($out_file);

        return true;
    } else {
        return false;
    }
}

function saveLastIP()
{
    dbconnect();
    // Remove old IP
    $sql = "DELETE FROM pmp_statistics WHERE type = 'last_login_old';";
    dbexec($sql);
    // Move last IP
    $sql =
        "UPDATE pmp_statistics SET type = 'last_login_old' WHERE type = 'last_login_new';";
    dbexec($sql);
    // Insert new IP
    $sql =
        "INSERT INTO pmp_statistics VALUES ('last_login_new', '" .
        mysql_real_escape_string($_SERVER["REMOTE_ADDR"]) .
        "', '" .
        date("H:i") .
        "', '" .
        date("Y-m-d") .
        "');";
    dbexec($sql);
    dbclose();
}

function getTags()
{
    $tags[""] = "empty";
    $sql =
        'SELECT DISTINCT name FROM pmp_tags WHERE name != \'\' ORDER BY name';
    $res = dbexec($sql, true);
    if ($res) {
        while ($row = mysql_fetch_object($res)) {
            $tags[$row->name] = $row->name;
        }
    }

    return $tags;
}

function getTimezones()
{
    $locations = [];
    $zones = timezone_identifiers_list();

    foreach ($zones as $zone) {
        $zone = explode("/", $zone); // 0 => Continent, 1 => City

        if (
            $zone[0] == "Africa" ||
            $zone[0] == "America" ||
            $zone[0] == "Antarctica" ||
            $zone[0] == "Arctic" ||
            $zone[0] == "Asia" ||
            $zone[0] == "Atlantic" ||
            $zone[0] == "Australia" ||
            $zone[0] == "Europe" ||
            $zone[0] == "Indian" ||
            $zone[0] == "Pacific"
        ) {
            if (isset($zone[1]) != "") {
                $locations[$zone[0] . "/" . $zone[1]] = str_replace(
                    "_",
                    " ",
                    $zone[0] . "/" . $zone[1],
                );
            }
        }
    }

    return $locations;
}

function getThemeCSS()
{
    global $pmp_theme;

    $files = [];

    $theme = (string) $pmp_theme;
    $dir = "../themes/" . $theme . "/css";

    if ($theme === "" || !is_dir($dir)) {
        $theme = "default";
        $dir = "../themes/" . $theme . "/css";
    }

    $dh = @opendir($dir);
    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if (substr($file, strlen($file) - 4) == ".css") {
                $files[$file] = $file;
            }
        }

        closedir($dh);
    }

    if (count($files) === 0) {
        $files["default.css"] = "default.css";
    }

    return $files;
}

function getIsset(&$value)
{
    if (isset($value)) {
        return $value;
    } else {
        return;
    }
}

function maskData($value)
{
    return mysql_real_escape_string((string) $value);
}

function genTopTags()
{
    dbconnect();

    dbexec("DELETE FROM pmp_tags WHERE name = 'IMDB Top 250'");
    dbexec("DELETE FROM pmp_tags WHERE name = 'IMDB Bottom 100'");
    dbexec("DELETE FROM pmp_tags WHERE name = 'OFDB Top 250'");
    dbexec("DELETE FROM pmp_tags WHERE name = 'OFDB Bottom 100'");

    $sql =
        "SELECT pmp_reviews_connect.id, pmp_reviews_external.type, top250, bottom100 FROM pmp_reviews_connect LEFT JOIN pmp_reviews_external ON review_id = pmp_reviews_external.id WHERE (top250 IS NOT NULL OR bottom100 IS NOT NULL) AND pmp_reviews_connect.id NOT IN (SELECT id FROM pmp_boxset)";

    $result = dbexec($sql);
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_object($result)) {
            if (isset($row->top250)) {
                $sql =
                    "INSERT INTO pmp_tags values ('" .
                    $row->id .
                    "', '" .
                    strtoupper($row->type) .
                    " Top 250', '" .
                    strtoupper($row->type) .
                    " Top 250')";
            } else {
                $sql =
                    "INSERT INTO pmp_tags values ('" .
                    $row->id .
                    "', '" .
                    strtoupper($row->type) .
                    " Bottom 100', '" .
                    strtoupper($row->type) .
                    " Bottom 100')";
            }
            dbexec($sql, true);
        }
    }

    dbclose();
}

function delCachedTempPHP()
{
    // Delete cached templates
    $handle = opendir("../cache/");
    while (($file = readdir($handle)) !== false) {
        if (strrchr($file, ".") == ".php") {
            @unlink("../cache/" . basename($file));
        }
    }
    closedir($handle);
}

function getMaxPacket()
{
    $res = dbexec("SHOW VARIABLES like 'max_allowed_packet'");
    $row = mysql_fetch_object($res);
    return $row->Value * 0.99;
}

function genScreenshotTag($indb)
{
    dbconnect();

    dbexec("DELETE FROM pmp_tags WHERE name = 'Screenshots'");

    foreach ($indb as $id) {
        $sql =
            "INSERT INTO pmp_tags values ('" .
            mysql_real_escape_string($id["id"]) .
            "', 'Screenshots', 'Screenshots')";
        dbexec($sql, true);
    }

    dbclose();
}

function getScreenshotsAdm($path)
{
    // Check OS
    if (php_uname("s") != "Windows NT") {
        $windows = false;
        $symlinks = 1;
    } else {
        $windows = true;
        if (substr(php_uname("v"), 6, 4) >= 6000) {
            $symlinks = 1;
        }
    }

    // Get all IDs
    $ids = [];
    $link = [];
    if (is_dir($path)) {
        $handle = opendir($path);
        if (is_resource($handle)) {
            while (false !== ($file = readdir($handle))) {
                if (
                    is_dir($path . DIRECTORY_SEPARATOR . $file) &&
                    $file != "." &&
                    $file != ".." &&
                    $file != "thumbs"
                ) {
                    if ($windows) {
                        $islink =
                            $file !==
                            pathinfo(
                                @readlink($path . DIRECTORY_SEPARATOR . $file),
                                PATHINFO_BASENAME,
                            );
                    } else {
                        $islink = is_link($path . DIRECTORY_SEPARATOR . $file);
                    }
                    if ($islink) {
                        $link[$file] = pathinfo(
                            @readlink($path . DIRECTORY_SEPARATOR . $file),
                            PATHINFO_BASENAME,
                        );
                    }
                    $ids[] = utf8_encode($file);
                }
            }
        }
        closedir($handle);
    }

    $indb = [];
    $notindb = [];

    // IDs in database?
    dbconnect();
    foreach ($ids as $id) {
        $sql =
            "SELECT id, title, sorttitle FROM pmp_film WHERE id='" .
            mysql_real_escape_string($id) .
            "'";
        $res = dbexec($sql);
        if (mysql_num_rows($res) != 0) {
            $row = mysql_fetch_object($res);
            $indb[$id] = [
                "sort" => $row->sorttitle,
                "id" => $id,
                "title" => $row->title,
            ];
        } else {
            $notindb[$id] = $id;
        }
    }
    if (!empty($indb)) {
        asort($indb);
    }

    return [$ids, $link, $indb, $notindb, $symlinks, $windows];
}

?>

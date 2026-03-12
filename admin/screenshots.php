<?php
/* phpMyProfiler
 * Copyright (C) 2012-2014 The phpMyProfiler project
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

$pmp_module = "admin_screenshots";

require_once "../config.inc.php";
require_once "../include/functions.php";
require_once "../admin/include/functions.php";
require_once "../include/pmp_Smarty.class.php";

isadmin();

function rrmdir($dir)
{
    $success = false;
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        $success = rmdir($dir);
    }
    return $success;
}

function entenc($text)
{
    // This is needed for german umlauts
    $res = "";
    for ($i = 0; $i < strlen($text); $i++) {
        $cc = ord($text[$i]);
        if ($cc >= 128 || $cc == 38) {
            $res .= "_";
        } else {
            $res .= chr($cc);
        }
    }
    return $res;
}

function uncompress_all($zip_file, $path)
{
    if (
        !class_exists("ZipArchive") ||
        !file_exists($path . DIRECTORY_SEPARATOR . $zip_file)
    ) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($path . DIRECTORY_SEPARATOR . $zip_file) !== true) {
        return false;
    }

    $i = 1;
    while (
        is_dir($path . DIRECTORY_SEPARATOR . "screens-" . sprintf('%1$04d', $i))
    ) {
        $i++;
    }
    $dest_dir =
        $path . DIRECTORY_SEPARATOR . "screens-" . sprintf('%1$04d', $i);
    if (!is_dir($dest_dir)) {
        @mkdir($dest_dir, 0777);
    }

    $allowed_ext = ["jpg", "jpeg", "png", "gif", "webp"];
    $written = 0;

    for ($idx = 0; $idx < $zip->numFiles; $idx++) {
        $entry = $zip->getNameIndex($idx);
        if ($entry === false) {
            continue;
        }

        if (
            strpos($entry, "/thumbs/") !== false ||
            strpos($entry, "../") !== false ||
            strpos($entry, "..\\") !== false
        ) {
            continue;
        }

        $filename = basename($entry);
        if ($filename === "") {
            continue;
        }

        $filename = entenc($filename);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext, true)) {
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

        $target = $dest_dir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($target, $content) !== false) {
            $written++;
        }
    }

    $zip->close();

    return $written > 0;
}

function getSafeScreenshotName($value)
{
    $name = basename((string) $value);
    if (
        $name === "" ||
        $name === "." ||
        $name === ".." ||
        strpos($name, "..") !== false
    ) {
        return false;
    }

    return $name;
}

$smarty = new pmp_Smarty();
$smarty->loadFilter("output", "trimwhitespace");
$smarty->compile_dir = "../templates_c";
admin_assign_csrf($smarty);

$smarty->assign("header", t("Screenshots"));
$smarty->assign("header_img", "screenshots");
$smarty->assign("session", "");

// We need some time to do this
@set_time_limit(0);
@ignore_user_abort(1);

$path = substr(getcwd(), 0, -5) . "screenshots";

if (isset($_GET["action"]) && $_GET["action"] == "upload") {
    admin_require_post_csrf();
    $uploadDir = ".." . DIRECTORY_SEPARATOR . "screenshots";

    if (isset($_FILES["file"]) && $_FILES["file"]["name"] != "") {
        if (!is_writeable($uploadDir)) {
            $smarty->assign(
                "Error",
                t("The directory screenshots is not writeable."),
            );
        } else {
            if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
                $infilename = basename($_FILES["file"]["name"]);
                $ext = strtolower(
                    substr($infilename, strrpos($infilename, ".") + 1),
                );
                if ($ext == "zip") {
                    if (
                        @move_uploaded_file(
                            $_FILES["file"]["tmp_name"],
                            $uploadDir . DIRECTORY_SEPARATOR . $infilename,
                        )
                    ) {
                        if (uncompress_all($infilename, $uploadDir)) {
                            if (
                                !@unlink(
                                    $uploadDir .
                                        DIRECTORY_SEPARATOR .
                                        $infilename,
                                )
                            ) {
                                $smarty->assign(
                                    "Error",
                                    t('Can\'t delete compressed file.'),
                                );
                            }
                            $smarty->assign(
                                "Success",
                                t("Added new screenshots."),
                            );
                        } else {
                            $smarty->assign(
                                "Error",
                                t("Unable to decompress file."),
                            );
                            @unlink($_FILES["file"]["tmp_name"]);
                        }
                    } else {
                        $smarty->assign("Error", t("Something went wrong."));
                        @unlink($_FILES["file"]["tmp_name"]);
                    }
                } else {
                    $smarty->assign("Error", t("Wrong file type!"));
                    @unlink($_FILES["file"]["tmp_name"]);
                }
            } else {
                $smarty->assign(
                    "Error",
                    t("Upload failed!") .
                        " " .
                        t("Error No:") .
                        " " .
                        $_FILES["file"]["error"],
                );
            }
        }
    } else {
        $smarty->assign("Error", t("You need to select a file first!"));
    }
}

if (isset($_GET["action"]) && $_GET["action"] == "show") {
    $show_id = getSafeScreenshotName(rawurldecode($_GET["id"]));
    if ($show_id === false) {
        $smarty->assign("Error", t("Invalid screenshot id."));
        $screenshots = [];
    } else {
        $screenshots = getScreenshots($show_id, "../");
    }

    $smarty->assign("id", $show_id);
    $smarty->assign("screenshots", $screenshots);

    $smarty->assign("show", 1);
}

if (isset($_GET["action"]) && $_GET["action"] == "relink") {
    admin_require_post_csrf();
    $relink_id = isset($_GET["id"])
        ? getSafeScreenshotName(rawurldecode($_GET["id"]))
        : false;
    if (
        $relink_id !== false &&
        (isset($_POST["relink"]) || isset($_POST["symlink"]))
    ) {
        if (
            substr($_POST["relink"], -1) == "." ||
            substr($_POST["symlink"], -1) == "."
        ) {
            $smarty->assign("Error", "Links must not end with a dot.");
        } else {
            if (isset($_POST["relink"]) && $_POST["relink"] != "") {
                $ren = rename(
                    $path . DIRECTORY_SEPARATOR . utf8_decode($relink_id),
                    $path . DIRECTORY_SEPARATOR . $_POST["relink"],
                );
                $ren_t = rename(
                    $path .
                        DIRECTORY_SEPARATOR .
                        "thumbs" .
                        DIRECTORY_SEPARATOR .
                        utf8_decode($relink_id),
                    $path .
                        DIRECTORY_SEPARATOR .
                        "thumbs" .
                        DIRECTORY_SEPARATOR .
                        $_POST["relink"],
                );
                if ($ren && $ren_t) {
                    $info = t("Screenshots successfully relinked.");
                } else {
                    $error = t("Screenshots could not be relinked.");
                }
                $_GET["id"] = rawurlencode($_POST["relink"]);
            }
            if (isset($_POST["symlink"]) && $_POST["symlink"] != "") {
                $sym = symlink(
                    $path . DIRECTORY_SEPARATOR . utf8_decode($relink_id),
                    $path . DIRECTORY_SEPARATOR . $_POST["symlink"],
                );
                $sym_t = symlink(
                    $path .
                        DIRECTORY_SEPARATOR .
                        "thumbs" .
                        DIRECTORY_SEPARATOR .
                        utf8_decode($relink_id),
                    $path .
                        DIRECTORY_SEPARATOR .
                        "thumbs" .
                        DIRECTORY_SEPARATOR .
                        $_POST["symlink"],
                );
                if ($sym && $sym_t) {
                    if (!empty($info)) {
                        $info .= "<br />Symlink successfully build.";
                    } else {
                        $info = "Symlink successfully build.";
                    }
                } else {
                    if (!empty($error)) {
                        $error .= "<br />Symlink could not be build.";
                    } else {
                        $error = "Symlink could not be build.";
                    }
                }
            }
            if (!empty($info)) {
                $smarty->assign("Info", $info);
            }
            if (!empty($error)) {
                $smarty->assign("Error", $error);
            }
        }
    }
}

[$ids, $link, $indb, $notindb, $symlink, $windows] = getScreenshotsAdm($path);
if ($symlink == 1) {
    $smarty->assign("symlinks", 1);
}

if (isset($_GET["action"]) && $_GET["action"] == "buildtags") {
    admin_require_post_csrf();
    genScreenshotTag($indb);

    // Delete cached templates
    delCachedTempPHP();

    $smarty->assign("Success", t("Tags table updated."));
}

if (isset($_GET["action"]) && $_GET["action"] == "delete") {
    admin_require_post_csrf();
    $delete_id = isset($_GET["id"])
        ? getSafeScreenshotName(rawurldecode($_GET["id"]))
        : false;
    if ($delete_id !== false) {
        // Symlink or directory
        if ($windows) {
            $islink =
                utf8_decode($delete_id) !==
                pathinfo(
                    @readlink(
                        $path . DIRECTORY_SEPARATOR . utf8_decode($delete_id),
                    ),
                    PATHINFO_BASENAME,
                );
        } else {
            $islink = is_link($path . DIRECTORY_SEPARATOR . $delete_id);
        }

        // Delete symlink
        if ($islink) {
            if ($windows) {
                $del = rmdir(
                    $path . DIRECTORY_SEPARATOR . utf8_decode($delete_id),
                );
                if (
                    is_dir(
                        $path .
                            DIRECTORY_SEPARATOR .
                            "thumbs" .
                            DIRECTORY_SEPARATOR .
                            utf8_decode($delete_id),
                    )
                ) {
                    $del_t = rmdir(
                        $path .
                            DIRECTORY_SEPARATOR .
                            "thumbs" .
                            DIRECTORY_SEPARATOR .
                            utf8_decode($delete_id),
                    );
                } else {
                    $del_t = true; // No thumbs, so it's OK
                }
            } else {
                $del = unlink(
                    $path . DIRECTORY_SEPARATOR . utf8_decode($delete_id),
                );
                if (
                    is_dir(
                        $path .
                            DIRECTORY_SEPARATOR .
                            "thumbs" .
                            DIRECTORY_SEPARATOR .
                            utf8_decode($delete_id),
                    )
                ) {
                    $del_t = unlink(
                        $path .
                            DIRECTORY_SEPARATOR .
                            "thumbs" .
                            DIRECTORY_SEPARATOR .
                            utf8_decode($delete_id),
                    );
                } else {
                    $del_t = true; // No thumbs, so it's OK
                }
            }
            if ($del && $del_t) {
                $smarty->assign("Info", "Symlink successfully deleted.");
                if (isset($indb[$delete_id])) {
                    unset($indb[$delete_id]);
                } else {
                    unset($notindb[$delete_id]);
                }
            } else {
                $smarty->assign("Error", "Symlink could not be deleted.");
            }
        }
        // Delete directory
        else {
            // Is there a symlink to this directory
            if (in_array($delete_id, $link)) {
                // Delete symlink
                $symlink = array_search($delete_id, $link);
                if ($windows) {
                    $del = rmdir($path . DIRECTORY_SEPARATOR . $symlink);
                    if (
                        is_dir(
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                $symlink,
                        )
                    ) {
                        $del_t = rmdir(
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                $symlink,
                        );
                    } else {
                        $del_t = true; // No thumbs, so it's OK
                    }
                } else {
                    $del = unlink($path . DIRECTORY_SEPARATOR . $symlink);
                    if (
                        is_link(
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                $symlink,
                        )
                    ) {
                        $del_t = unlink(
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                $symlink,
                        );
                    } else {
                        $del_t = true; // No thumbs, so it's OK
                    }
                }
                if ($del && $del_t) {
                    // Rename directory to prior symlink
                    $ren = rename(
                        $path . DIRECTORY_SEPARATOR . utf8_decode($delete_id),
                        $path . DIRECTORY_SEPARATOR . $symlink,
                    );
                    if (
                        is_dir(
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                utf8_decode($delete_id),
                        )
                    ) {
                        $ren_t = rename(
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                utf8_decode($delete_id),
                            $path .
                                DIRECTORY_SEPARATOR .
                                "thumbs" .
                                DIRECTORY_SEPARATOR .
                                $symlink,
                        );
                    } else {
                        $ren_t = true; // No thumbs, so it's OK
                    }
                    if ($ren && $ren_t) {
                        $smarty->assign(
                            "Info",
                            "Screenshots successfully moved to prior symlink.",
                        );
                        if (isset($indb[$delete_id])) {
                            unset($indb[$delete_id]);
                        } else {
                            unset($notindb[$delete_id]);
                        }
                        unset($link[$symlink]);
                    } else {
                        $smarty->assign(
                            "Error",
                            "Screenshots could not be moved to prior symlink.",
                        );
                    }
                } else {
                    $smarty->assign("Error", "Symlink could not be deleted.");
                }
            }
            // Delete directory
            else {
                $del = rrmdir(
                    $path . DIRECTORY_SEPARATOR . utf8_decode($delete_id),
                );
                if (
                    is_dir(
                        $path .
                            DIRECTORY_SEPARATOR .
                            "thumbs" .
                            DIRECTORY_SEPARATOR .
                            utf8_decode($delete_id),
                    )
                ) {
                    $del_t = rrmdir(
                        $path .
                            DIRECTORY_SEPARATOR .
                            "thumbs" .
                            DIRECTORY_SEPARATOR .
                            utf8_decode($delete_id),
                    );
                } else {
                    $del_t = true; // No thumbs, so it's OK
                }
                if ($del && $del_t) {
                    $smarty->assign(
                        "Info",
                        "Screenshots successfully deleted.",
                    );
                    if (isset($indb[$delete_id])) {
                        unset($indb[$delete_id]);
                    } else {
                        unset($notindb[$delete_id]);
                    }
                } else {
                    $smarty->assign(
                        "Error",
                        "Screenshots could not be deleted.",
                    );
                }
            }
        }
    }
}

$smarty->assign("indb", $indb);
$smarty->assign("notindb", $notindb);
$smarty->assign("link", $link);

$smarty->display("admin/screenshots.tpl");
?>

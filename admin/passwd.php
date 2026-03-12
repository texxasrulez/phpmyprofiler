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

$pmp_module = "admin_passwd";

require_once "../config.inc.php";
require_once "../admin/include/functions.php";
require_once "../admin/include/password.php";
require_once "../include/pmp_Smarty.class.php";

$smarty = new pmp_Smarty();
$smarty->loadFilter("output", "trimwhitespace");
$smarty->compile_dir = "../templates_c";
admin_assign_csrf($smarty);

$smarty->assign("header", t("Change the Administrator Password"));
$smarty->assign("header_img", "passwd");
$smarty->assign("session", "");

$configfile = "../passwd.inc.php";
$has_credentials = false;

if (file_exists($configfile) && is_readable($configfile)) {
    require_once $configfile;
    $has_credentials = !empty($pmp_admin) && !empty($pmp_passwd);
}

if ($has_credentials) {
    isadmin();
} else {
    $smarty->assign("nologout", true);
}

$user = false;
$nouser = false;

// Check if file exits and is writeable
if (!file_exists($configfile)) {
    $smarty->assign(
        "BigError",
        t("Configuration File") .
            ' "' .
            basename($configfile) .
            '" ' .
            t("not found."),
    );
} elseif (!is_writeable($configfile)) {
    $smarty->assign(
        "BigError",
        t("Configuration File") .
            ' "' .
            basename($configfile) .
            '" ' .
            t("is not writeable."),
    );
} else {
    // Check if admin user and pass is set
    if (empty($pmp_admin) || empty($pmp_passwd)) {
        $nouser = true;
        $smarty->assign("NoUser", true);
    }
}

if (!$nouser) {
    $smarty->assign("Focus", "cur_user");
} else {
    $smarty->assign("Focus", "new_user");
}

// Check if correct
if (isset($_POST["setpasswd"])) {
    admin_require_post_csrf();
    // Check against old user
    if (!$nouser) {
        if (empty($_POST["cur_user"]) || empty($_POST["cur_passwd"])) {
            if (empty($_POST["cur_user"]) && empty($_POST["cur_passwd"])) {
                $smarty->assign(
                    "ErrorCur",
                    t("You must enter a username and a password."),
                );
            } elseif (empty($_POST["cur_user"])) {
                $smarty->assign("ErrorCur", t("You must enter a username."));
                $smarty->assign("LastCurPasswd", $_POST["cur_passwd"]);
            } elseif (empty($_POST["cur_passwd"])) {
                $smarty->assign("ErrorCur", t("You must enter a password."));
                $smarty->assign("LastCurUser", $_POST["cur_user"]);
                $smarty->assign("Focus", "cur_passwd");
            }
        } elseif (
            $_POST["cur_user"] != $pmp_admin ||
            !password_verify($_POST["cur_passwd"], $pmp_passwd)
        ) {
            $smarty->assign(
                "ErrorCur",
                t("The username or the password is wrong."),
            );
        } else {
            $user = true;
            $smarty->assign("LastCurUser", $_POST["cur_user"]);
            $smarty->assign("LastCurPasswd", $_POST["cur_passwd"]);
        }
    }

    // Check if all needed data entered
    if ($user) {
        $smarty->assign("Focus", "new_user");
    }

    if (
        empty($_POST["new_user"]) ||
        empty($_POST["new_passwd"]) ||
        empty($_POST["new_passwd2"])
    ) {
        if (
            empty($_POST["new_user"]) &&
            (empty($_POST["new_passwd"]) || empty($_POST["new_passwd2"]))
        ) {
            $smarty->assign(
                "ErrorNew",
                t("You must enter the new username and the new password."),
            );
        } elseif (empty($_POST["new_user"])) {
            $smarty->assign("ErrorNew", t("You must enter the new username."));
            $smarty->assign("LastNewPasswd", $_POST["new_passwd"]);
            $smarty->assign("LastNewPasswd2", $_POST["new_passwd2"]);
        } elseif (empty($_POST["new_passwd"]) || empty($_POST["new_passwd2"])) {
            $smarty->assign("ErrorNew", t("You must enter the new password."));
            $smarty->assign("LastNewUser", $_POST["new_user"]);
            $smarty->assign("Focus", "new_passwd");
        }
    } elseif ($_POST["new_passwd"] != $_POST["new_passwd2"]) {
        $smarty->assign("ErrorNew", t("The new Password do not match."));
        $smarty->assign("LastNewUser", $_POST["new_user"]);
        $smarty->assign("LastNewPasswd", "");
        $smarty->assign("LastNewPasswd2", "");
        $smarty->assign("Focus", "new_passwd");
    } else {
        if ((!$nouser && $user) || $nouser) {
            $new_user = preg_replace(
                "/[^A-Za-z0-9_.@-]/",
                "",
                (string) $_POST["new_user"],
            );
            if (strlen($_POST["new_passwd"]) < 12) {
                $smarty->assign(
                    "ErrorNew",
                    t("Password must contain at least 12 characters."),
                );
                $smarty->assign("LastNewUser", $new_user);
                $smarty->assign("Focus", "new_passwd");
            } else {
                $data =
                    "<?php\n// " .
                    t("Configuration File of Administration") .
                    "\n// " .
                    t("Generated:") .
                    " " .
                    date("r") .
                    "\n\n";
                $data .= '$pmp_admin' . " = '" . $new_user . "';" . "\n";
                $data .=
                    '$pmp_passwd' .
                    " = '" .
                    password_hash($_POST["new_passwd"], PASSWORD_BCRYPT, [
                        "cost" => 12,
                    ]) .
                    "';" .
                    "\n";
                $data .= "\n?>";

                if (!file_put_contents($configfile, $data)) {
                    $smarty->assign(
                        "BigError",
                        t("An error occurs while writing to") .
                            ' "' .
                            basename($configfile) .
                            '".',
                    );
                } else {
                    $smarty->assign("Success", t("Settings saved."));
                    #$smarty->assign('NoUser', '');
                    $smarty->assign("LastNewUser", "");
                    $smarty->assign("LastCurUser", "");
                    $smarty->assign("LastCurPasswd", "");
                    $smarty->assign("Focus", "cur_user");
                }
            }
        } else {
            $smarty->assign("LastNewUser", $_POST["new_user"]);
            $smarty->assign("LastNewPasswd", $_POST["new_passwd"]);
            $smarty->assign("LastNewPasswd2", $_POST["new_passwd2"]);
        }
    }
}

$smarty->display("admin/passwd.tpl");
?>

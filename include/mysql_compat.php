<?php
/**
 * Compatibility layer for legacy mysql_* functions removed in PHP 7+.
 * Uses mysqli internally to keep the existing codebase operational.
 */

if (!function_exists("each")) {
    function each(&$array)
    {
        $key = key($array);
        if ($key === null) {
            return false;
        }

        $value = current($array);
        next($array);

        return [
            1 => $value,
            "value" => $value,
            0 => $key,
            "key" => $key,
        ];
    }
}

if (!defined("MYSQL_ASSOC")) {
    define("MYSQL_ASSOC", defined("MYSQLI_ASSOC") ? MYSQLI_ASSOC : 1);
}
if (!defined("MYSQL_NUM")) {
    define("MYSQL_NUM", defined("MYSQLI_NUM") ? MYSQLI_NUM : 2);
}
if (!defined("MYSQL_BOTH")) {
    define("MYSQL_BOTH", defined("MYSQLI_BOTH") ? MYSQLI_BOTH : 3);
}

if (!function_exists("mysql_connect") && function_exists("mysqli_connect")) {
    if (function_exists("mysqli_report")) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    $GLOBALS["__pmp_mysql_link"] = null;
    $GLOBALS["__pmp_mysql_last_result"] = null;
    $GLOBALS["__pmp_mysql_errno"] = 0;
    $GLOBALS["__pmp_mysql_error"] = "";

    function __pmp_mysql_set_error($link = null)
    {
        if ($link instanceof mysqli) {
            $GLOBALS["__pmp_mysql_errno"] =
                mysqli_connect_errno() ?: mysqli_errno($link);
            $GLOBALS["__pmp_mysql_error"] =
                mysqli_connect_error() ?: mysqli_error($link);
        } else {
            $GLOBALS["__pmp_mysql_errno"] = mysqli_connect_errno();
            $GLOBALS["__pmp_mysql_error"] = mysqli_connect_error();
        }
    }

    function __pmp_mysql_link($link_identifier = null)
    {
        if ($link_identifier instanceof mysqli) {
            return $link_identifier;
        }
        if (
            isset($GLOBALS["__pmp_mysql_link"]) &&
            $GLOBALS["__pmp_mysql_link"] instanceof mysqli
        ) {
            return $GLOBALS["__pmp_mysql_link"];
        }
        return null;
    }

    function mysql_connect(
        $server = null,
        $username = null,
        $password = null,
        $new_link = false,
        $client_flags = 0,
    ) {
        $server = $server ?: ini_get("mysqli.default_host");
        $username = $username ?: ini_get("mysqli.default_user");
        $password = $password ?: ini_get("mysqli.default_pw");

        if (!empty($client_flags)) {
            $link = mysqli_init();
            if (!$link) {
                __pmp_mysql_set_error();
                return false;
            }

            $connected = @mysqli_real_connect(
                $link,
                $server,
                $username,
                $password,
                "",
                0,
                "",
                (int) $client_flags,
            );
            if (!$connected) {
                __pmp_mysql_set_error($link);
                return false;
            }
        } else {
            $link = @mysqli_connect($server, $username, $password, "", 0, "");
        }

        if (!$link) {
            __pmp_mysql_set_error();
            return false;
        }

        $GLOBALS["__pmp_mysql_link"] = $link;
        $GLOBALS["__pmp_mysql_errno"] = 0;
        $GLOBALS["__pmp_mysql_error"] = "";

        return $link;
    }

    function mysql_select_db($database_name, $link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if (!$link) {
            __pmp_mysql_set_error();
            return false;
        }

        $res = @mysqli_select_db($link, $database_name);
        if (!$res) {
            __pmp_mysql_set_error($link);
        }
        return $res;
    }

    function mysql_query($query, $link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if (!$link) {
            __pmp_mysql_set_error();
            return false;
        }

        $result = @mysqli_query($link, $query);
        $GLOBALS["__pmp_mysql_last_result"] = $result;
        if ($result === false) {
            __pmp_mysql_set_error($link);
        } else {
            $GLOBALS["__pmp_mysql_errno"] = 0;
            $GLOBALS["__pmp_mysql_error"] = "";
        }

        return $result;
    }

    function mysql_set_charset($charset, $link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if (!$link) {
            __pmp_mysql_set_error();
            return false;
        }
        return @mysqli_set_charset($link, $charset);
    }

    function mysql_close($link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if (!$link) {
            return false;
        }
        $res = @mysqli_close($link);
        if ($res) {
            $GLOBALS["__pmp_mysql_link"] = null;
        }
        return $res;
    }

    function mysql_errno($link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if ($link) {
            return mysqli_errno($link);
        }
        return (int) $GLOBALS["__pmp_mysql_errno"];
    }

    function mysql_error($link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if ($link) {
            return (string) mysqli_error($link);
        }
        return (string) $GLOBALS["__pmp_mysql_error"];
    }

    function mysql_real_escape_string(
        $unescaped_string,
        $link_identifier = null,
    ) {
        $link = __pmp_mysql_link($link_identifier);
        if (!$link) {
            $link = mysql_connect();
            if (!$link) {
                return addslashes((string) $unescaped_string);
            }
        }
        return mysqli_real_escape_string($link, (string) $unescaped_string);
    }

    function mysql_num_rows($result)
    {
        if ($result instanceof mysqli_result) {
            return mysqli_num_rows($result);
        }
        return 0;
    }

    function mysql_fetch_array($result, $result_type = MYSQL_BOTH)
    {
        if ($result instanceof mysqli_result) {
            return mysqli_fetch_array($result, $result_type);
        }
        return false;
    }

    function mysql_fetch_assoc($result)
    {
        if ($result instanceof mysqli_result) {
            return mysqli_fetch_assoc($result);
        }
        return false;
    }

    function mysql_fetch_row($result)
    {
        if ($result instanceof mysqli_result) {
            return mysqli_fetch_row($result);
        }
        return false;
    }

    function mysql_fetch_object(
        $result,
        $class_name = "stdClass",
        array $params = [],
    ) {
        if ($result instanceof mysqli_result) {
            return mysqli_fetch_object($result, $class_name, $params);
        }
        return false;
    }

    function mysql_result($result, $row = 0, $field = 0)
    {
        if (!($result instanceof mysqli_result)) {
            return false;
        }

        $row = (int) $row;
        if ($row < 0 || $row >= mysqli_num_rows($result)) {
            return false;
        }
        if (!mysqli_data_seek($result, $row)) {
            return false;
        }

        $data = mysqli_fetch_array($result, MYSQLI_BOTH);
        if ($data === null || $data === false) {
            return false;
        }

        if (is_int($field) || ctype_digit((string) $field)) {
            $field = (int) $field;
        }

        return isset($data[$field]) ? $data[$field] : false;
    }

    function mysql_insert_id($link_identifier = null)
    {
        $link = __pmp_mysql_link($link_identifier);
        if (!$link) {
            return 0;
        }
        return mysqli_insert_id($link);
    }

    function mysql_data_seek($result, $row_number)
    {
        if ($result instanceof mysqli_result) {
            return mysqli_data_seek($result, $row_number);
        }
        return false;
    }
}

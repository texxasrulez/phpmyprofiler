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

class update
{
    public $id;
    public $ExecSQL = [];
    public $Author;
    public $Date;
    public $Description;
    public $lasterror = false;

    public function __construct($file = null)
    {
        if ($file !== null) {
            $this->update($file);
        }
    }

    public function update($file)
    {
        $this->ExecSQL = [];

        $tmpcontent = file($file);
        if ($tmpcontent === false) {
            return;
        }

        $content = implode(" ", array_map("trim", $tmpcontent));

        preg_match("/<id>(.*?)<\/id>/i", $content, $tmp);
        $this->id = isset($tmp[1]) ? $tmp[1] : null;

        preg_match_all("/<ExecSQL>(.*?)<\/ExecSQL>/i", $content, $tmp);
        $this->ExecSQL = isset($tmp[1]) ? $tmp[1] : [];

        preg_match("/<Author>(.*?)<\/Author>/i", $content, $tmp);
        $this->Author = isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/<Date>(.*?)<\/Date>/i", $content, $tmp);
        $this->Date = isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/<Description>(.*?)<\/Description>/i", $content, $tmp);
        $this->Description = isset($tmp[1]) ? $tmp[1] : null;
    }

    public function doit()
    {
        if (!is_array($this->ExecSQL) || count($this->ExecSQL) === 0) {
            $this->lasterror =
                "No executable SQL statements found in update file for update id " .
                $this->id .
                ".";
            return false;
        }

        $res = true;
        $sql = "";
        foreach ($this->ExecSQL as $sql) {
            $res = dbexec(trim($sql), true);

            if (!$res) {
                break;
            }
        }

        if ($res) {
            $sql =
                "INSERT INTO pmp_update (id, date) VALUES (" .
                mysql_real_escape_string($this->id) .
                ", now())";
            $res = dbexec($sql, true);

            if ($res) {
                $this->lasterror = true;
                return $this->Description;
            } else {
                $this->lasterror =
                    mysql_error() .
                    "<br /><br />Query:<br />" .
                    replace_table_prefix($sql);
                return false;
            }
        } else {
            $this->lasterror =
                mysql_error() . "<br /><br />Query:<br />" . $sql;
            return false;
        }
    }
}
?>

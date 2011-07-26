<?php
// Hoplite
// Copyright (c) 2011 Blue Static
// 
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General License as published by the Free
// Software Foundation, either version 3 of the License, or any later version.
// 
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General License for
// more details.
//
// You should have received a copy of the GNU General License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.

namespace hoplite\base\filter;

function TrimmedString($str)
{
    return trim($str);
}

function String($str)
{
    $find = array(
        '<',
        '>',
        '"'
    );
    $replace = array(
        '&lt;',
        '&gt;',
        '&quot;'
    );
    return str_replace($find, $replace, $str);
}

function Int($int)
{
    return intval($int);
}

function Float($float)
{
    return floatval($float);
}

function Bool($bool)
{
    $str = strtolower(TrimmedString($bool));
    if ($str == 'yes' || $str == 'true')
        return TRUE;
    else if ($str == 'no' || $str == 'false')
        return FALSE;
    return (bool)$bool;
}

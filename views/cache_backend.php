<?php
// Hoplite
// Copyright (c) 2013 Blue Static
//
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or any later version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.

namespace hoplite\views;

/*!
  CacheBackend is used by the TemplateLoader to store and retrieve compiled
  templates.
*/
interface CacheBackend
{
  /*!
    Gets the compiled template data for the template of the given name.

    If the template modification time is newer than what is stored in the cache,
    this function should discard the item and return NULL.

    @param string The name of the template.
    @param int The UNIX timestamp the uncompiled template was last modified.

    @return string|NULL The cached template data, or NULL if it is not cached.
  */
  public function GetTemplateDataForName($name, $modification_time);

  /*!
    Stores the compiled template data into the cache with the given name and
    template modification time.

    @param string The name of the template.
    @param string The compiled template data.
    @param int The UNIX timestamp the uncompiled template was last modified.
  */
  public function StoreCompiledTemplate($name, $modification_time, $data);
}

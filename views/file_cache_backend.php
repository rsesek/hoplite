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

require_once HOPLITE_ROOT . '/views/cache_backend.php';

/*!
  An instance of a CacheBackend that writes cached representations to the file
  system in temporary files.
*/
class FileCacheBackend implements CacheBackend
{
  /*! @var string The cache path for templates. This should be a path, to which
                  the cached template name will be appended. This should not
                  end with a trailing slash.
  */
  protected $cache_path = '/tmp/phalanx_views';

  public function cache_path() { return $this->cache_path; }

  public function __construct($cache_path)
  {
    $this->cache_path = $cache_path;
  }

  public function GetTemplateDataForName($name, $tpl_mtime)
  {
    $cache_path = $this->_CachePath($name);

    // Make sure the cached file exists and hasn't gotten out-of-date.
    if (!file_exists($cache_path) || filemtime($cache_path) < $tpl_mtime)
      return NULL;

    // Load the contents of the cache.
    $data = @file_get_contents($cache_path);
    if ($data === FALSE)
      return NULL;

    return $data;
  }

  public function StoreCompiledTemplate($name, $mtime, $data)
  {
    $cache_path = $this->_CachePath($name);

    // Cache the file.
    if (file_put_contents($cache_path, $data) === FALSE)
      throw new TemplateLoaderException('Could not cache ' . $name . ' to ' . $cache_path);
  }

  /*! Returns the cache path for a given template name. */
  protected function _CachePath($name)
  {
    return $this->cache_path . $name . '.phpi';
  }
}

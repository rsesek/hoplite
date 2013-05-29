<?php
// Hoplite
// Copyright (c) 2011 Blue Static
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

use \hoplite\base\Profiling;

require_once HOPLITE_ROOT . '/base/profiling.php';
require_once HOPLITE_ROOT . '/views/template.php';

/*!
  This class knows how to load and cache templates to the file system.
*/
class TemplateLoader
{
  /*! @var TemplateLoader Singleton instance */
  static private $instance = NULL;

  /*! @var string Base path for loading the template file. Use %s to indicate
                  where the name (passed to the constructor) should be
                  substituted.
  */
  protected $template_path = '%s.tpl';

  /*! @var string The cache path for templates. Unlike |$template_path|, this
                  should only be a path, to which the cached template name will
                  be appended. This should not end with a trailing slash.
  */
  protected $cache_path = '/tmp/phalanx_views';

  /*! @var string A header to put at the beginning of each cached template file,
                  common for setting include paths or cache debug information.
  */
  protected $cache_prefix = '';

  /*! @var array An array of Template objects, keyed by the template name. */
  protected $cache = array();

  /*! @var array Array of template usage counts, keyed by name. Only used
                 when profiling.
  */
  protected $usage = array();

  /*! Gets the singleton instance. */
  static public function GetInstance()
  {
    if (!self::$instance) {
      $class = get_called_class();
      self::$instance = new $class();
    }
    return self::$instance;
  }
  /*! Sets the singleton instance. */
  static public function SetInstance($instance) { self::$instance = $instance; }

  /*! Accessors */
  public function set_template_path($path) { $this->template_path = $path; }
  function template_path() { return $this->template_path; }
  
  public function set_cache_path($path) { $this->cache_path = $path; }
  public function cache_path() { return $this->cache_path; }

  /*!
    Loads a template from a file, creates a Template object, and returns a copy
    of that object.

    @param string Template name, with wich the template plath is formatted.

    @return Template Clone of the cached template.
  */
  public function Load($name)
  {
    if (Profiling::IsProfilingEnabled() && !isset($this->usage[$name]))
      $this->usage[$name] = 0;

    // First check the memory cache.
    if (isset($this->cache[$name]))
      return clone $this->cache[$name];

    // Then check the filesystem cache.
    $template = $this->_LoadIfCached($name);
    if ($template) {
      $this->cache[$name] = $template;
      return clone $template;
    }

    // Finally, parse and cache the template.
    $template = $this->_Cache($name);
    $this->cache[$name] = $template;
    return clone $template;
  }

  /*! Convenience function for loading templates. */
  static public function Fetch($name)
  {
    return self::GetInstance()->Load($name);
  }

  /*! Marks a template as having been used. */
  public function MarkTemplateRendered($name)
  {
    if (!isset($this->usage[$name]))
      throw new \InvalidArgumentException("Template $name has not been loaded through this instance");

    $this->usage[$name]++;
  }

  /*!
    Loads a cached filesystem template if it is up-to-date.

    @param string Template name

    @return Template|NULL
  */
  protected function _LoadIfCached($name)
  {
    $cache_path = $this->_CachePath($name);
    $tpl_path   = $this->_TemplatePath($name);

    // Make sure the cached file exists and hasn't gotten out-of-date.
    if (!file_exists($cache_path) || filemtime($cache_path) < filemtime($tpl_path))
      return NULL;

    // Load the contents of the cache.
    $data = @file_get_contents($cache_path);
    if ($data === FALSE)
      return NULL;

    return Template::NewWithCompiledData($name, $data);
  }

  /*!
    Loads a raw template from the file system, stores the compiled template in
    the file system, and returns a new template object with that data.

    @param string Template name.

    @return Template
  */
  protected function _Cache($name)
  {
    $cache_path = $this->_CachePath($name);
    $tpl_path   = $this->_TemplatePath($name);

    $data = @file_get_contents($tpl_path);
    if ($data === FALSE)
      throw new TemplateLoaderException('Could not load template ' . $name);

    $template = Template::NewWithData($name, $data);

    // Cache the file.
    if (file_put_contents($cache_path, $this->cache_prefix . $template->template()) === FALSE)
      throw new TemplateLoaderException('Could not cache ' . $name . ' to ' . $cache_path);

    return $template;
  }

  /*! Returns the template path for a given template name. */
  protected function _TemplatePath($name)
  {
    return sprintf($this->template_path, $name);
  }

  /*! Returns the cache path for a given template name. */
  protected function _CachePath($name)
  {
    return $this->cache_path . $name . '.phpi';
  }
}

class TemplateLoaderException extends \Exception {}

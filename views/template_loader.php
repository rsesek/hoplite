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
  The TemplateLoader manages the reading of templates from the file system.

  It can also work with a CacheBackend to store the compiled template data to
  increase performance.

  Internally it also maintains a cache that it will consult after a template is
  loaded for the first time.
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

  /*! @var CacheBackend A cache for compiled template data. */
  protected $cache_backend = NULL;

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
  public function template_path() { return $this->template_path; }

  public function set_cache_backend(CacheBackend $backend) { $this->cache_backend = $backend; }
  public function cache_backend() { return $this->cache_backend; }

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

    // Then check if the cache backend has it.
    $template = $this->_QueryCache($name);
    if ($template) {
      $this->cache[$name] = $template;
      return clone $template;
    }

    // Finally, parse and cache the template.
    $template = $this->_Load($name);
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
    Queries the optional CacheBackend for a template.

    @param string Template name

    @return Template|NULL
  */
  protected function _QueryCache($name)
  {
    if (!$this->cache_backend)
      return NULL;

    $tpl_path = $this->_TemplatePath($name);
    $data = $this->cache_backend->GetTemplateDataForName($name, filemtime($tpl_path));
    if (!$data)
      return NULL;

    return Template::NewWithCompiledData($name, $data);
  }

  /*!
    Loads a raw template from the file system and compiles it. If the optional
    CacheBackend is present, it will cache the compiled data.

    @param string Template name.

    @return Template
  */
  protected function _Load($name)
  {
    $tpl_path = $this->_TemplatePath($name);

    $data = @file_get_contents($tpl_path);
    if ($data === FALSE)
      throw new TemplateLoaderException('Could not load template ' . $name);

    $template = Template::NewWithData($name, $data);

    if ($this->cache_backend) {
      $this->cache_backend->StoreCompiledTemplate(
          $name, filemtime($tpl_path), $template->template());
    }

    return $template;
  }

  /*! Returns the template path for a given template name. */
  protected function _TemplatePath($name)
  {
    return sprintf($this->template_path, $name);
  }
}

class TemplateLoaderException extends \Exception {}

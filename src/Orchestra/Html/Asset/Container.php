<?php namespace Orchestra\Html\Asset;

use RuntimeException;

class Container {

	/**
	 * Application instance.
	 *
	 * @var Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * The asset container name.
	 *
	 * @var string
	 */
	protected $name = null;

	/**
	 * All of the registered assets.
	 *
	 * @var array
	 */
	protected $assets = array();

	/**
	 * Create a new asset container instance.
	 *
	 * @access public
	 * @param  Illuminate\Foundation\Application    $app
	 * @param  string                               $name
	 * @return void
	 */
	public function __construct($app, $name)
	{
		$this->app  = $app;
		$this->name = $name;
	}

	/**
	 * Add an asset to the container.
	 *
	 * The extension of the asset source will be used to determine the type of
	 * asset being registered (CSS or JavaScript). When using a non-standard
	 * extension, the style/script methods may be used to register assets.
	 *
	 * <code>
	 *		// Add an asset to the container
	 *		Asset::container()->add('jquery', 'js/jquery.js');
	 *
	 *		// Add an asset that has dependencies on other assets
	 *		Asset::add('jquery', 'js/jquery.js', 'jquery-ui');
	 *
	 *		// Add an asset that should have attributes applied to its tags
	 *		Asset::add('jquery', 'js/jquery.js', null, array('defer'));
	 * </code>
	 *
	 * @access public
	 * @param  string  $name
	 * @param  string  $source
	 * @param  array   $dependencies
	 * @param  array   $attributes
	 * @return void
	 */
	public function add($name, $source, $dependencies = array(), $attributes = array())
	{
		$type = (pathinfo($source, PATHINFO_EXTENSION) == 'css') ? 'style' : 'script';

		return $this->$type($name, $source, $dependencies, $attributes);
	}

	/**
	 * Add a CSS file to the registered assets.
	 *
	 * @access public
	 * @param  string   $name
	 * @param  string   $source
	 * @param  array    $dependencies
	 * @param  array    $attributes
	 * @return self
	 */
	public function style($name, $source, $dependencies = array(), $attributes = array())
	{
		if ( ! array_key_exists('media', $attributes))
		{
			$attributes['media'] = 'all';
		}

		$this->register('style', $name, $source, $dependencies, $attributes);

		return $this;
	}

	/**
	 * Add a JavaScript file to the registered assets.
	 *
	 * @access public
	 * @param  string   $name
	 * @param  string   $source
	 * @param  array    $dependencies
	 * @param  array    $attributes
	 * @return self
	 */
	public function script($name, $source, $dependencies = array(), $attributes = array())
	{
		$this->register('script', $name, $source, $dependencies, $attributes);

		return $this;
	}

	/**
	 * Add an asset to the array of registered assets.
	 *
	 * @access protected
	 * @param  string   $type
	 * @param  string   $name
	 * @param  string   $source
	 * @param  array    $dependencies
	 * @param  array    $attributes
	 * @return void
	 */
	protected function register($type, $name, $source, $dependencies, $attributes)
	{
		$dependencies = (array) $dependencies;

		$attributes = (array) $attributes;

		$this->assets[$type][$name] = compact('source', 'dependencies', 'attributes');
	}

	/**
	 * Get the links to all of the registered CSS assets.
	 *
	 * @access public
	 * @return string
	 */
	public function styles()
	{
		return $this->group('style');
	}

	/**
	 * Get the links to all of the registered JavaScript assets.
	 *
	 * @access public
	 * @return string
	 */
	public function scripts()
	{
		return $this->group('script');
	}

	/**
	 * Get all of the registered assets for a given type / group.
	 *
	 * @param  string  $group
	 * @return string
	 */
	protected function group($group)
	{
		if ( ! isset($this->assets[$group]) or count($this->assets[$group]) == 0) return '';

		$assets = '';

		foreach ($this->arrange($this->assets[$group]) as $name => $data)
		{
			$assets .= $this->asset($group, $name);
		}
		
		return $assets;
	}

	/**
	 * Get the HTML link to a registered asset.
	 *
	 * @param  string  $group
	 * @param  string  $name
	 * @return string
	 */
	protected function asset($group, $name)
	{
		if ( ! isset($this->assets[$group][$name])) return '';

		$asset = $this->assets[$group][$name];

		return call_user_func_array(array($this->app['html'], $group), array(
			$asset['source'], 
			$asset['attributes'],
		));
	}

	/**
	 * Sort and retrieve assets based on their dependencies
	 *
	 * @access protected
	 * @param  array   $assets
	 * @return array
	 */
	protected function arrange($assets)
	{
		list($original, $sorted) = array($assets, array());

		while (count($assets) > 0)
		{
			foreach ($assets as $asset => $value)
			{
				$this->evaluateAsset($asset, $value, $original, $sorted, $assets);
			}
		}
		
		return $sorted;
	}

	/**
	 * Evaluate an asset and its dependencies.
	 *
	 * @access protected
	 * @param  string  $asset
	 * @param  string  $value
	 * @param  array   $original
	 * @param  array   $sorted
	 * @param  array   $assets
	 * @return void
	 */
	protected function evaluateAsset($asset, $value, $original, &$sorted, &$assets)
	{
		// If the asset has no more dependencies, we can add it to the sorted list
		// and remove it from the array of assets. Otherwise, we will not verify
		// the asset's dependencies and determine if they've been sorted.
		if (count($assets[$asset]['dependencies']) == 0)
		{
			$sorted[$asset] = $value;

			unset($assets[$asset]);
		}
		else
		{
			foreach ($assets[$asset]['dependencies'] as $key => $dependency)
			{
				if ( ! $this->dependencyIsValid($asset, $dependency, $original, $assets))
				{
					unset($assets[$asset]['dependencies'][$key]);

					continue;
				}

				// If the dependency has not yet been added to the sorted list, we can not
				// remove it from this asset's array of dependencies. We'll try again on
				// the next trip through the loop.
				if ( ! isset($sorted[$dependency])) continue;

				unset($assets[$asset]['dependencies'][$key]);
			}
		}		
	}

	/**
	 * Verify that an asset's dependency is valid.
	 *
	 * A dependency is considered valid if it exists, is not a circular reference, and is
	 * not a reference to the owning asset itself. If the dependency doesn't exist, no
	 * error or warning will be given. For the other cases, an exception is thrown.
	 *
	 * @access protected
	 * @param  string  $asset
	 * @param  string  $dependency
	 * @param  array   $original
	 * @param  array   $assets
	 * @return boolean
	 */
	protected function dependencyIsValid($asset, $dependency, $original, $assets)
	{
		if ( ! isset($original[$dependency]))
		{
			return false;
		}
		elseif ($dependency === $asset)
		{
			throw new RuntimeException("Asset [$asset] is dependent on itself.");
		}
		elseif (isset($assets[$dependency]) and in_array($asset, $assets[$dependency]['dependencies']))
		{
			throw new RuntimeException("Assets [$asset] and [$dependency] have a circular dependency.");
		}

		return true;
	}
}

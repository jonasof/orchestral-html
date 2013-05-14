<?php namespace Orchestra\Html;

use \Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider {
	
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['orchestra.asset'] = $this->app->share(function($app)
		{
			return new Asset\Environment($app);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('orchestra.asset');
	}
}

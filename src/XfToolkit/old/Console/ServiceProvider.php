<?php namespace XfToolkit\Console;

// When a service provider standard is finally created we will swap this out

interface ServiceProvider {

	/**
	 * Setup the service.
	 */
	public function register(Application $application);
}
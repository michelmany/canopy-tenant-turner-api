<?php

namespace Inc;

final class Init {
	/**
	 * Store all the classes  inside an array
	 * @return string[] Full list of classes
	 */
	public static function getServices(): array {
		return [
			Base\BaseController::class,
			Controllers\SettingsController::class,
			API\ApiController::class,
			Controllers\CronController::class,
		];
	}

	public static function registerServices(): void {
		foreach ( self::getServices() as $class ) {
			$service = self::instantiate( $class );
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	private static function instantiate( $class ) {
		return new $class();
	}
}
<?php
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

class i8 {
	/** @var \Slim\App $app */
	public $app;

	/** @var Dotenv $dotenv */
	protected $dotenv;

	/**
	 * Construct the Dotenv object and verify our configuration
	 */
	protected function constructDotenv(): void {
		$this->dotenv = Dotenv::createImmutable(dirname(__DIR__));
		$this->dotenv->load();
		$this->dotenv->required('APP_ENV')->allowedValues(['development', 'test', 'production']);
		$this->dotenv->required('DATABASE_DSN')->notEmpty();
		$this->dotenv->ifPresent('I8_MAGENTASSO_DISABLE')->isBoolean();
		if (!array_key_exists('I8_MAGENTASSO_DISABLE', $_ENV) || !boolval($_ENV['I8_MAGENTASSO_DISABLE'])) {
			$this->dotenv->required('I8_MAGENTASSO_CLIENT_ID')->notEmpty();
			$this->dotenv->required('I8_MAGENTASSO_CLIENT_SECRET')->notEmpty();
			$this->dotenv->required('I8_MAGENTASSO_SERVER_URL')->notEmpty();
		}
	}

	/**
	 * Construct an instance of the i8 application
	 */
	public function __construct() {
		$this->constructDotenv();

		// Construct application
		$this->app = AppFactory::create();
		$this->app->addRoutingMiddleware();
		$this->app->addErrorMiddleware(in_array($_ENV['APP_ENV'], ['development', 'test']), true, true);

		// Add debug routes
		if (in_array($_ENV['APP_ENV'], ['development', 'test'])) {
			$this->app->get('/debug', \i8Controllers::class . ':debug');
		}

		// Add normal app routes
		$this->app->get('/sso', \i8Controllers::class . ':sso_initiate');
		$this->app->get('/sso/callback', \i8Controllers::class . ':sso_callback');
		$this->app->post('/sso/logout', \i8Controllers::class . ':sso_logout');
		$this->app->post('/shorten', \i8Controllers::class . ':shorten');
		$this->app->get('/{slug}', \i8Controllers::class . ':slug');
		$this->app->any('/', \i8Controllers::class . ':index');
	}
}

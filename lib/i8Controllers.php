<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use HansOtt\PSR7Cookies\SetCookie;
use Psr\Container\ContainerInterface;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use MagentaSSO\MagentaRequest;
use MagentaSSO\MagentaResponse;
use MagentaSSO\MagentaSignatureException;

class i8Controllers {
	/** @var ?ContainerInterface $container */
	public $container;

	/** @var \i8Html $html */
	public $html;

	/**
	 * Construct an i8Controllers instance
	 *
	 * @param ?ContainerInterface $container
	 */
	public function __construct(?ContainerInterface $container) {
		$this->container = $container;
		$this->html = new \i8Html();
	}

	/**
	 * Do debugging things
	 *
	 * @param mixed[] $args
	 */
	public function debug(Request $request, Response $response, ?array $args = []): Response {
		$query_values = $request->getQueryParams();
		if (array_key_exists('setapikey', $query_values)) {
			$cookie = new SetCookie('i8apikey', $query_values['setapikey'], time() + (60 * 60 * 24 * 7), '/');
			$response = $cookie->addToResponse($response);
		}

		$response->getBody()->write('Debug actions OK');
		return $response;
	}

	/**
	 * If logged in, show a form to shorten a URL, as well as a log out button.
	 * If not logged in, show a log in button.
	 *
	 * @param mixed[] $args
	 */
	public function index(Request $request, Response $response, ?array $args = []): Response {
		$user_obj = null;
		$cookies = $request->getCookieParams();
		if (array_key_exists('i8apikey', $cookies)) {
			$user_obj = i8Helpers::user_get_by_apikey($cookies['i8apikey']);
		}

		$responseBody = $response->getBody();
		if ($user_obj === null) {
			$responseBody->write($this->html->renderDefault([], [
				$this->html->tagHasChildren('form', ['method' => 'GET', 'action' => '/sso', 'class' => 'form-auth'], ...[
					$this->html->tagHasChildren('button', ['type' => 'submit'], "Log in"),
				]),
			]));

			return $response;
		}
		
		$responseBody->write($this->html->renderDefault([], [
			$this->html->tagHasChildren('h1', [], "i8"),
			$this->html->tagHasChildren('span', ['class' => 'margin-y'], "Logged in as " . htmlspecialchars($user_obj['email'])),
			$this->html->tagHasChildren('form', ['method' => 'POST', 'action' => '/shorten', 'class' => 'form-shorten'], ...[
				$this->html->tag('input', ['type' => 'text', 'name' => 'url', 'placeholder' => 'URL to shorten']),
				$this->html->tagHasChildren('button', ['type' => 'submit'], "Shorten"),
			]),
			$this->html->tagHasChildren('form', ['method' => 'GET', 'action' => '/apikey'], ...[
				$this->html->tagHasChildren('button', ['type' => 'submit'], "Show API key"),
			]),
			$this->html->tagHasChildren('form', ['method' => 'POST', 'action' => '/sso/logout', 'class' => 'form-auth'], ...[
				$this->html->tagHasChildren('button', ['type' => 'submit'], "Log out"),
			]),
		]));

		return $response;
	}
	/**
	 * Display the user's API key, if they're logged in, otherwise 404.
	 *
	 * @param mixed[] $args
	 */
	public function showapikey(Request $request, Response $response, ?array $args = []): Response {
		$user_obj = null;
		$cookies = $request->getCookieParams();
		if (array_key_exists('i8apikey', $cookies)) {
			$user_obj = i8Helpers::user_get_by_apikey($cookies['i8apikey']);
		}

		if ($user_obj === null) {
			throw new HttpNotFoundException($request);
		}
		
		$response->getBody()->write($this->html->renderDefault([], [
			$this->html->tagHasChildren('h1', [], "i8"),
			$this->html->tagHasChildren('span', ['class' => 'margin-y'], ...[
				"Your API key is: ",
				$this->html->tagHasChildren('code', [], htmlspecialchars($user_obj['apikey'])),
			]),
			$this->html->tagHasChildren('form', ['method' => 'GET', 'action' => '/'], ...[
				$this->html->tagHasChildren('button', ['type' => 'submit'], "Back to home"),
			]),
		]));

		return $response;
	}

	/**
	 * Log out
	 *
	 * @param mixed[] $args
	 */
	public function sso_logout(Request $request, Response $response, ?array $args = []): Response {
		$cookie = SetCookie::thatDeletesCookie('i8apikey', '/');
		$response = $cookie->addToResponse($response);

		return $response->withHeader('Location', '/')->withStatus(302);
	}

	/**
	 * Initiate a MagentaSSO request
	 *
	 * @param mixed[] $args
	 */
	public function sso_initiate(Request $request, Response $response, ?array $args = []): Response {
		$magenta_request = new MagentaRequest(
			$_ENV['I8_MAGENTASSO_CLIENT_ID'],
			$_ENV['I8_MAGENTASSO_CLIENT_SECRET'],
			null,
			null,
			strval($request->getUri()->withPath("/sso/callback")),
		);

		return $response
			->withHeader('Location', "{$_ENV['I8_MAGENTASSO_SERVER_URL']}?{$magenta_request}")
			->withStatus(302);
	}

	/**
	 * Process an incoming MagentaSSO response
	 *
	 * @param mixed[] $args
	 */
	public function sso_callback(Request $request, Response $response, ?array $args = []): Response {
		$query_values = $request->getQueryParams();
		if (!array_key_exists('payload', $query_values) || !array_key_exists('signature', $query_values)) {
			throw new HttpBadRequestException($request);
		}

		// Decode and verify the response
		try {
			$magenta_response = new MagentaResponse(
				$_ENV['I8_MAGENTASSO_CLIENT_ID'],
				$_ENV['I8_MAGENTASSO_CLIENT_SECRET'],
			);
			$magenta_response->decode(
				$query_values['payload'],
				$query_values['signature'],
			);
		} catch (MagentaSignatureException $e) {
			throw new HttpBadRequestException($request);
		}

		// If we get here, the response was okay
		// Get the user object from the SSO external_id
		$user_obj = i8Helpers::user_get_or_create(
			$magenta_response->data['user_data']['external_id'],
			$magenta_response->data['user_data']['email'],
		);

		// Save our API key as a cookie
		$cookie = new SetCookie('i8apikey', $user_obj['apikey'], time() + (60 * 60 * 24 * 7), '/');
		$response = $cookie->addToResponse($response);

		// And redirect to the index page
		return $response->withHeader('Location', '/')->withStatus(302);
	}

	/**
	 * Shorten a URL
	 *
	 * @param mixed[] $args
	 */
	public function shorten(Request $request, Response $response, ?array $args = []): Response {
		$rBody = (array) $request->getParsedBody();
		$rCookie = (array) $request->getCookieParams();

		// Get API key - first from request body, then from cookies
		$apikey = array_key_exists('apikey', $rBody) ? $rBody['apikey'] : null;
		if ($apikey === null)
			$apikey = array_key_exists('i8apikey', $rCookie) ? $rCookie['i8apikey'] : null;

		// Abort if no API key
		if ($apikey === null)
			throw new HttpBadRequestException($request);

		// Get user object, abort if no user
		if (($user = i8Helpers::user_get_by_apikey($apikey)) === null) 
			throw new HttpBadRequestException($request);

		// Abort if no URL
		$url = array_key_exists('url', $rBody) ? trim($rBody['url']) : null;
		if (empty($url))
			throw new HttpBadRequestException($request);

		// Get or create a URL entry for this URL
		$shortened = i8Helpers::url_get_or_create($rBody['url'], intval($user['id']), true);

		// Get the encoded slug for this URL
		$slug = i8Helpers::slug_encode(intval($shortened['id']));

		// Return the full URL
		$response->getBody()->write(strval($request->getUri()->withPath("/{$slug}")));
		return $response->withHeader('Content-Type', 'text/plain');
	}

	/**
	 * Redirect to the target of the shortened URL
	 *
	 * @param mixed[] $args
	 */
	public function slug(Request $request, Response $response, ?array $args = []): Response {
		$urlID = i8Helpers::slug_decode($args['slug']);
		if (($urlEntry = i8Helpers::url_get($urlID)) !== null) {
			return $response->withHeader('Location', $urlEntry['url'])->withStatus(302);
		}

		throw new HttpNotFoundException($request);
	}
}

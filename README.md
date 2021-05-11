# i8

A tiny, but secure, URL shortener.

## Installation

Assuming you have your web roots in `/var/www`,
and you want to keep i8 in `/var/www/i8`:

```shell
% # Clone the repo and install dependencies
% git clone https://github.com/u1f408/i8.git /var/www/i8
% cd /var/www/i8
% composer install

% # Edit the configuration environment variables
% cp .env.dist .env
% $EDITOR .env
```

Point your web server's document root to
`/var/www/i8/public` and rewrite all requests
to `index.php` - this will work for nginx:

```nginx
server {
	server_name i8.test;
	root /var/www/i8/public;

	# Or, however you enable PHP
	include /etc/nginx/fastcgi_php.conf;

	location / {
		try_files $uri /index.php;
	}
}
```

## Usage

i8 is an _authenticated_ URL shortener - only users that have
been granted access are able to create shortened URLs.

i8 primarily uses [MagentaSSO][] for authenticating users -
the `I8_MAGENTASSO_*` environment variables configure this.

Visiting `https://i8.test` will redirect you to the
configured MagentaSSO provider, where you can log in.

After successfully authenticating, you will be presented with
a page containing your API key, a button to reset that API key,
and a form to shorten a URL from a web browser.

[MagentaSSO]: https://github.com/magentasso

### Using without MagentaSSO

You can turn off MagentaSSO authentication in i8 by setting
the environment variable `I8_MAGENTASSO_DISABLE=true` in your
`.env`, and in this mode you will have to manually add new
API keys into the i8 database to be able to shorten URLs.

There is (or, will be) a helper script for this: `tools/addApiKey.php`.

### API usage

```shell
% http --form https://i8.test/shorten url=https://example.com apikey=$YOUR_API_KEY
HTTP/1.1 200 OK
Content-Type: text/plain

https://i8.test/BJCV
```

## License

i8 is licensed under the terms of the
GNU Affero General Public License, version 3
(or, at your option, any later version).

See [LICENSE](./LICENSE) for details.

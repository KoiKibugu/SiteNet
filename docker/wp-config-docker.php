<?php
/**
 * WordPress configuration for the Docker deployment.
 *
 * Mounted over the repo's (gitignored) LocalWP wp-config.php by
 * docker/docker-compose.yml — the LocalWP file is never modified.
 *
 * Authentication salts are provided by the ignored Docker-only .env file.
 */

// ** Database settings — the compose "db" service (MySQL 8.4) ** //
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'wpuser' );
define( 'DB_PASSWORD', 'wppass123' );
define( 'DB_HOST', 'db:3306' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/** Authentication keys are unique per Docker checkout, not shared with LocalWP. */
$auth_keys = array(
	'AUTH_KEY' => 'WORDPRESS_AUTH_KEY',
	'SECURE_AUTH_KEY' => 'WORDPRESS_SECURE_AUTH_KEY',
	'LOGGED_IN_KEY' => 'WORDPRESS_LOGGED_IN_KEY',
	'NONCE_KEY' => 'WORDPRESS_NONCE_KEY',
	'AUTH_SALT' => 'WORDPRESS_AUTH_SALT',
	'SECURE_AUTH_SALT' => 'WORDPRESS_SECURE_AUTH_SALT',
	'LOGGED_IN_SALT' => 'WORDPRESS_LOGGED_IN_SALT',
	'NONCE_SALT' => 'WORDPRESS_NONCE_SALT',
	'WP_CACHE_KEY_SALT' => 'WORDPRESS_CACHE_KEY_SALT',
);
foreach ( $auth_keys as $constant => $environment_variable ) {
	$value = getenv( $environment_variable );
	if ( false === $value || '' === $value ) {
		throw new RuntimeException( 'Missing required environment variable: ' . $environment_variable );
	}
	define( $constant, $value );
}
unset( $auth_keys, $constant, $environment_variable, $value );

/** Database table prefix. */
$table_prefix = 'wp_';

/* Docker-specific values. */

/**
 * Normalize the request scheme behind TLS-terminating proxies (Cloudflare
 * Tunnel reaches Apache over plain HTTP but sends X-Forwarded-Proto: https).
 * Without this, is_ssl() is false at the origin while WP_HOME is https, and
 * redirect_canonical() emits a self-referential 301 loop on every page load.
 */
if ( ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )
	|| ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) ) {
	$_SERVER['HTTPS'] = 'on';
}

/**
 * Serve the site from whichever host the visitor used (localhost:2026,
 * LAN IP http://192.168.x.x:2026, or a future tunnel hostname) without
 * redirects or broken asset URLs. Must be defined BEFORE wp-settings.php.
 *
 * Proxy-aware: Cloudflare Tunnel (and most reverse proxies) terminate TLS at
 * the edge and reach Apache over plain HTTP, signalling the original scheme
 * via X-Forwarded-Proto. Without this, enqueued CSS/JS would be printed as
 * http:// URLs on an https:// page and browsers would block them as mixed
 * content ("site renders with no styling").
 */
if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
	$scheme = 'http';
	if ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] )
		|| ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )
		|| ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] ) )
		|| ( isset( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] ) ) {
		$scheme = 'https';
	}
	define( 'WP_HOME', $scheme . '://' . $_SERVER['HTTP_HOST'] );
	define( 'WP_SITEURL', WP_HOME );
}

/** Direct filesystem method (no FTP prompts) when editing via wp-admin. */
define( 'FS_METHOD', 'direct' );

/** Debugging off for the shared deployment. */
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

define( 'WP_ENVIRONMENT_TYPE', 'production' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
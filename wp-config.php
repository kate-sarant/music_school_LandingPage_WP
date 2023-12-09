<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'test' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'W+/d]}%Xx{Qm{/,idrI)uxjjz}A*|lfi-.u<{N7~?Uv(ics7rEm7e1|/H,/6qyMH' );
define( 'SECURE_AUTH_KEY',  '{wJrh9tLpO>(1Q-@H[&U1|!6F`rYqZWnk>4]QW(`W&*pArJ>2KF@=36OO)y/%JWf' );
define( 'LOGGED_IN_KEY',    'nd86HZc[[+@VW-A4@/<Xr?I5wC<xQ*wR6zoHh`tLl>l|:~6~M.csjbD$d.`}lx`^' );
define( 'NONCE_KEY',        ':QZ6;y8C.d_fEp<C}(Qb/W3 HV_SB@ vD)p6Hi rfr^p~J(h|)#_]l,9r^[@OOcs' );
define( 'AUTH_SALT',        '({7f2cdW}yO>wM0C9up9LsmF8nw+[Li=)mBc>Fg lG;]-219/Sw6z{SG|<{6%>jM' );
define( 'SECURE_AUTH_SALT', 'U9}rCX4h^_AsBz?-qj&9C?<V2y78u?CEPNoTi(8VtJv8LA~+y6MHLFqCWc3D{G0H' );
define( 'LOGGED_IN_SALT',   '7*0/AMrChEMJQVMf4St<Yef`!Z}rb}PTav/l:uuGQh`I=^9fPrfSJXy*]D|l4tSA' );
define( 'NONCE_SALT',       '$1o9&#*s)KY$U$ArnaFu iKgW(y]8$~;@d<NzYAhnpUyIKHK#..M_en^5m==B{+-' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );
define('FS_METHOD','direct');

/* Add any custom values between this line and the "stop editing" line. */


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
define('WP_ALLOW_REPAIR', true);

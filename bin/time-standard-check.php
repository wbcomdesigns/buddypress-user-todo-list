<?php
/**
 * time-standard-check.php - fail when a plugin prints a time outside its one formatter.
 *
 * Standard: ~/.claude/workflows/wbcom-time-standard.md (each plugin keeps a synced copy at
 * docs/standards/time.md). Portable version of Learnomy's bin/check-one-time-formatter.php.
 *
 *   TM1  wp_date() / date_i18n() / mysql2date() / get_date_from_gmt() called outside the
 *        time helpers. Each picks its own timezone; every shown time goes through
 *        date_label(), which uses display_timezone() (rule 4 of the standard).
 *
 * The helpers file needs no configuration: any file that DEFINES a function or method
 * named display_timezone / date_label (bare or prefixed, e.g. learnomy_date_label) is the
 * helpers file and may call the raw formatters.
 *
 * Usage:  php time-standard-check.php [plugin-dir] [more-plugin-dirs ...]
 *         (pass Free and Pro together; each dir is checked with its own allowlist)
 * Allow:  inline: a `time-ok: <reason>` comment on the line of the call or the line above it.
 *         file:   <plugin-dir>/.time-standard-allow, one "TM1 path/fragment  # reason" per line;
 *         a finding is allowed when its file path contains the fragment (legacy files only).
 * Exit:   0 clean, 1 findings, 2 usage error (no such dir, or no PHP file scanned in a dir: wrong path).
 */

$dirs = array_slice( $argv, 1 );
if ( ! $dirs ) {
	$dirs = array( '.' );
}

$raw   = array( 'wp_date', 'date_i18n', 'mysql2date', 'get_date_from_gmt' );
$skip  = '#/(vendor|node_modules|libs|lib|tests?|bin|tools|dist|build|\.git)/#';
$found = array();

foreach ( $dirs as $dir ) {
	$dir = rtrim( $dir, '/' );
	if ( ! is_dir( $dir ) ) {
		echo "time-standard: no such dir: $dir\n";
		exit( 2 );
	}

	$allow = array();
	if ( is_file( "$dir/.time-standard-allow" ) ) {
		foreach ( file( "$dir/.time-standard-allow", FILE_IGNORE_NEW_LINES ) as $line ) {
			$line = trim( preg_replace( '/#.*/', '', $line ) );
			if ( preg_match( '/^TM1\s+(\S+)/', $line, $m ) ) {
				$allow[] = $m[1];
			}
		}
	}

	$label = count( $dirs ) > 1 ? basename( realpath( $dir ) ) . '/' : '';
	$files   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	$scanned = 0;

	foreach ( $files as $file ) {
		$path = $file->getPathname();
		$rel  = substr( $path, strlen( $dir ) + 1 );
		if ( 'php' !== $file->getExtension() || preg_match( $skip, '/' . $rel ) ) {
			continue;
		}
		++$scanned;

		$tokens = token_get_all( file_get_contents( $path ) );
		$lines  = null;
		$hits   = array();
		$n      = count( $tokens );

		for ( $i = 0; $i < $n; $i++ ) {
			$t = $tokens[ $i ];
			if ( ! is_array( $t ) ) {
				continue;
			}

			// A file that defines the helpers is the helpers file: skip it whole.
			if ( T_FUNCTION === $t[0] ) {
				for ( $j = $i + 1; $j < $n && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_STRING ), true ); $j++ ) {
					if ( T_STRING === $tokens[ $j ][0] && preg_match( '/(^|_)(display_timezone|date_label)$/i', $tokens[ $j ][1] ) ) {
						continue 3;
					}
				}
				continue;
			}

			// T_NAME_FULLY_QUALIFIED covers \wp_date( ... ) on PHP 8+.
			$name = strtolower( ltrim( $t[1], '\\' ) );
			if ( ! in_array( $t[0], array( T_STRING, T_NAME_FULLY_QUALIFIED ), true ) || ! in_array( $name, $raw, true ) ) {
				continue;
			}

			// A real function call: next code token is "(", previous is not ->, ::, function or new.
			for ( $j = $i + 1; $j < $n && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0]; $j++ );
			for ( $k = $i - 1; $k >= 0 && is_array( $tokens[ $k ] ) && T_WHITESPACE === $tokens[ $k ][0]; $k-- );
			$prev = $tokens[ $k ] ?? null;
			if ( '(' !== ( $tokens[ $j ] ?? null ) || ( is_array( $prev ) && in_array( $prev[0], array( T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW ), true ) ) ) {
				continue;
			}

			// Inline escape: `time-ok: <reason>` on the call's line or the line above. The reason is required.
			$lines = $lines ?? file( $path );
			$here  = $lines[ $t[2] - 1 ] ?? '';
			$above = $lines[ $t[2] - 2 ] ?? '';
			if ( preg_match( '/time-ok:\s*\S/', $here ) || preg_match( '#^\s*(//|\#|/?\*).*time-ok:\s*\S#', $above ) ) {
				continue;
			}

			$hits[] = sprintf( 'TM1  %s%s:%d  %s', $label, $rel, $t[2], trim( $here ) );
		}

		foreach ( $allow as $fragment ) {
			if ( false !== strpos( $rel, $fragment ) ) {
				continue 2;
			}
		}
		$found = array_merge( $found, $hits );
	}

	// A gate that scanned nothing has checked nothing: a wrong path must not read as clean.
	if ( ! $scanned ) {
		echo "time-standard: no PHP files scanned under $dir - wrong path?\n";
		exit( 2 );
	}
}

if ( ! $found ) {
	echo "time-standard: clean\n";
	exit( 0 );
}
echo 'time-standard: ' . count( $found ) . " finding(s) - show times through date_label() / display_timezone(); see ~/.claude/workflows/wbcom-time-standard.md\n";
foreach ( $found as $f ) {
	echo "  $f\n";
}
exit( 1 );

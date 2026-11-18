#!/usr/bin/env bash
# Local CI: php -l on PHP 8.1 and 8.5 (Docker), WPCS security + i18n sniffs (text domain
# enforced), the time standard, grunt's text-domain check, and a POT that matches the code.
# Any failing step fails the script.
set -euo pipefail
cd "$(dirname "$0")/.."
domain=bp-user-to-do-list
for v in 8.1 8.5; do
	echo "== php -l $v"
	docker run --rm -v "$PWD":/p -w /p "php:$v-cli" sh -c \
		'! find . -name "*.php" -not -path "./node_modules/*" -not -path "./vendor/*" -not -path "./dist/*" -print0 | xargs -0 -n1 php -l 2>&1 | grep -v "^No syntax errors"'
done
echo "== phpcs (security + i18n, errors gate)"
phpcs -q -n --standard=WordPress --extensions=php --ignore=node_modules,dist,vendor,bin \
	--sniffs=WordPress.Security.EscapeOutput,WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput,WordPress.DB.PreparedSQL,WordPress.WP.I18n \
	--runtime-set text_domain "$domain" .
echo "== time standard"
php -n bin/time-standard-check.php .
echo "== grunt checktextdomain"
npx grunt checktextdomain
echo "== POT is current"
tmp=$(mktemp -d)
wp i18n make-pot . "$tmp/$domain.pot" --domain="$domain" --exclude=node_modules,dist,docs,audit,assets/js/select2.full.js,assets/js/select2.full.min.js,assets/js/bptodo-front.min.js,assets/js/bptodo-modern.min.js --skip-audit >/dev/null
diff <(grep '^msgid ' "languages/$domain.pot" | sort) <(grep '^msgid ' "$tmp/$domain.pot" | sort) \
	|| { echo "languages/$domain.pot is stale: regenerate it, then msgmerge, make-mo, make-php, make-json"; exit 1; }
rm -rf "$tmp"
echo "== .l10n.php direct-access guard"
missing=$(grep -L "defined( 'ABSPATH' ) || exit;" languages/*.l10n.php)
[ -z "$missing" ] || { echo "Add \"defined( 'ABSPATH' ) || exit;\" after <?php in: $missing (make-php drops it)"; exit 1; }
echo "local CI passed"

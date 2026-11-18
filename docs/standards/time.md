# Wbcom Time Standard

Applies to every plugin and theme in the portfolio. Each plugin keeps a synced copy at
`docs/standards/time.md`. Reference implementation: Learnomy 2.1.2
(`learnomy/includes/functions.php`, Basecamp 10397030702).

**Rule: store every time in UTC. Show it in one timezone, chosen in one place, the same way on
admin screens, member pages, emails, PDFs and edit fields. A filter lets a site show each
member their own timezone.**

Why: WordPress gives several ways to print a date and each picks its own timezone.
`wp_date()` converts to the site's timezone; `date_i18n()` and `mysql2date()` print the stored
value as it is. Learnomy had about 170 of these mixed together, so near midnight one screen said
14 October and the next said 15 October for the same moment. Some values were also stored as the
site's clock time, so lessons, cohorts and deadlines opened hours early or late on any site
outside UTC.

## The helpers (one plugin family, one set)

Each plugin family (free + pro) has exactly these, in the free plugin. Pro and add-ons call them
and never add their own.

| Helper | Job |
|---|---|
| `display_timezone( int $user_id = 0 ): DateTimeZone` | The one place that picks the timezone. Returns `wp_timezone()` through the filter `<prefix>_display_timezone( $timezone, $user_id )`. |
| `date_label( string\|int\|null $utc, string $fallback, ?string $format, int $user_id = 0 ): string` | The one formatter for every time shown. Takes a stored UTC string (a naive string is read as UTC) or a Unix timestamp; returns `wp_date()` in `display_timezone()`; the fallback for empty, zero-date or unparseable values. |
| `datetime_input_to_utc( ?string $local ): ?string` | Save: what the editor typed (in `display_timezone()`) to UTC `Y-m-d H:i:s`. Blank or unparseable gives null. |
| `datetime_local_input( ?string $utc ): string` | Edit field: stored UTC to `Y-m-d\TH:i` in `display_timezone()`. Take `substr( …, 0, 10 )` for a date input. The exact inverse of the line above. |
| `rest_datetime( string $utc ): string` | REST output: a stored UTC time as ISO 8601 with offset, applied to every response by one filter. |
| `display_time_sql( string $column ): string` | Reports: `CONVERT_TZ( col, '+00:00', <display offset> )`, to wrap in `DATE()` or `DATE_FORMAT()` for day, week and month buckets. A numeric offset needs no MySQL timezone tables. |

Names follow the plugin's own namespace or prefix. Copy the bodies from the Learnomy reference.

## Rules

1. **Store UTC.** Write with `current_time( 'mysql', true )`, `gmdate()` or the save helper above.
   Never `current_time( 'mysql' )` or `current_time( 'timestamp' )` for a stored or compared time.
2. **Convert what people type, once, on save,** with `datetime_input_to_utc()` in the one writer
   (service or REST handler) that every surface goes through: admin form, front-end builder,
   REST, CLI. A date with no time that means a whole day (an end date, a deadline) becomes
   23:59:59 in that timezone first; a start date becomes 00:00:00.
3. **Compare in UTC.** Gates, unlocks, sale windows and deadlines compare the stored UTC value
   with `current_time( 'mysql', true )` or `time()`. No "site-local frame" exceptions.
4. **Show through `date_label()` only.** No `wp_date()`, `date_i18n()`, `mysql2date()` or
   `get_date_from_gmt()` outside the helpers. A line that truly needs one carries a `time-ok:`
   comment saying why.
5. **Emails, notifications and PDFs format for the recipient:** pass their user id as
   `$user_id`, so the member filter applies to what they receive.
6. **Edit fields round trip through the pair.** Show with `datetime_local_input()`, save with
   `datetime_input_to_utc()`. Both use `display_timezone()`, so a time never moves on save.
7. **REST sends ISO 8601 with the offset** (`2026-10-20T08:30:00+00:00`) through one
   `rest_post_dispatch` filter on the plugin's own namespaces (`rest_datetime()` in Learnomy), not
   per route, so a new route gets it too. Input accepts what the editor typed or ISO; the server
   converts. A route that receives a stored time back to match a row (an announcement recall)
   normalises ISO to `Y-m-d H:i:s` first. Clients parse with one helper that accepts both forms.
8. **JavaScript never uses the browser's timezone for a stored time.** PHP passes
   `display_timezone()->getName()` as `timeZone` in the data the script already receives
   (`wp_localize_script` or the module's data). The script reads UTC with
   `new Date( v.replace( ' ', 'T' ) + 'Z' )` and formats with `toLocaleDateString( …, { timeZone } )`.
   For a `datetime-local` value use `Intl.DateTimeFormat( 'en-CA', { timeZone, …, hourCycle: 'h23' } ).formatToParts()`.
   Countdowns count to the ISO instant. A value that is already a display-timezone clock time
   (from `datetime_local_input()`) may be read and printed in the browser frame as it is.
9. **Report days are the display timezone's days:** group with `DATE( display_time_sql( col ) )`;
   build PHP day keys with `date_label( $ts, '', 'Y-m-d' )`.
10. **One-time conversions of old data use `wp_timezone()`, not `display_timezone()`:** the old
    values were typed in the site's timezone. Run once behind a version or an option flag; a
    stored time cannot tell which frame it was written in.

## Gate

The gate is one shared script, the same for every plugin:

```bash
php ~/.claude/skills/wp-plugin-development/scripts/time-standard-check.php <plugin> [<pro>]
```

Copy it to the plugin's `bin/time-standard-check.php` and run it in local CI (stage 2.5 of
`bin/local-ci.sh`) and in the CI `standards` job; `gate-check.sh` runs it at release (row TM).
It fails (exit 1, one `TM1 file:line` row per finding) on any call to `wp_date()`,
`date_i18n()`, `mysql2date()` or `get_date_from_gmt()` in any PHP file of the plugin outside
`vendor/`, `tests/`, `bin/` and build output. The helpers file needs no setting: it is the file
that defines `display_timezone()` / `date_label()`. Two escapes, both need a reason: a
`time-ok: <reason>` comment on the line (or the line above), and for a legacy file not yet
converted a `TM1 path/fragment  # reason` line in `.time-standard-allow`. Learnomy's own
`bin/check-one-time-formatter.php` is the script this one was ported from; it reads only
`includes/` and `templates/`, so it misses calls in `blocks/` and the main file. Add a guard
test that, with the site set to `Asia/Kolkata` in that process only, checks a stored UTC time
shows as the local time, the filter moves display and edit fields together, and each stored field
saves as UTC.

## QA cases (every release that touches time)

Run on `Asia/Kolkata` (UTC+5:30), `America/New_York` (daylight saving), `UTC` and a manual
offset ("UTC+5:30"), near midnight local: stored values are UTC; admin and member screens show
the same local date for the same moment; an edit field keeps its value over three saves; gates
open at the exact local minute; emails and PDFs show local dates; JS screens ignore the browser's
own timezone; with a member-timezone filter on, each member sees their own time. The full case
list is on Learnomy card 10397030702.

## Shortcuts on the record

- `display_time_sql()` uses the current offset for the whole report range, so in a week with a
  daylight-saving change rows within an hour of midnight can land in the next or previous day.
  Group in PHP with `date_label()` where a report needs exact days.
- Browsers older than 2023 may not accept a numeric offset (`+05:30`) as `timeZone`; the scripts
  fall back to the stored text when `Intl` throws.

# Block + REST + Interactivity Migration Plan

Goal: serve the two jobs — (a) a member's personal task list, (b) a group's shared
tasks — in a modern, fast, theme-uniform way. Replace shortcode/BP-tab PHP templates
and jQuery/admin-ajax with **dynamic SSR blocks backed by a `bptodo/v1` REST API and
the WordPress Interactivity API**.

Decisions locked with owner:
- **WP Interactivity API (SSR + hydrate)** for the client layer (fast first paint, tiny runtime, same output for block/shortcode/tab).
- **Full build** in this pass: REST + both blocks + rewiring, keeping shortcode + BP tabs working throughout.
- Buildless: no `@wordpress/scripts`. Frontend uses `viewScriptModule` (ES module, `@wordpress/interactivity` via import map). Editor uses `wp.*` globals + `ServerSideRender`. Keeps the existing grunt pipeline.
- **Legacy/Modern layout toggle, Modern as default.** A large install base means the old path is never deleted — it's kept behind a setting so any site can fall back instantly. Modern (block/REST/Interactivity) ships as the default for new installs and updates.

### Layout mode (safety switch)
- Setting `bptodo_layout_mode` in General settings: **`modern`** (default) | `legacy`. Filterable: `apply_filters( 'bptodo_layout_mode', $mode, $context )` for per-site/per-surface override.
- `bptodo_layout_mode()` helper returns the effective mode. Every render entry point (shortcode, member tab, group tab) branches on it:
  - `modern` → `shared/render-list.php` (SSR + Interactivity + REST).
  - `legacy` → the current templates + jQuery/admin-ajax (unchanged, fully working).
- The old templates, jQuery handlers, and AJAX endpoints are **retained indefinitely** as the legacy path — not deprecated. Assets for each mode load only in that mode.
- Admin note beside the toggle: "Modern uses blocks + REST for faster, richer lists. Switch to Legacy if a theme/plugin conflict appears."

---

## 0. Positioning: standalone-first, BuddyPress optional

The todo is already a CPT (`bp-todo`) and all ownership/permissions are **user-based**,
so the modern layer works for **any logged-in WordPress user with no BuddyPress at all**
— a personal todo app (Any.do-style). BuddyPress/BuddyBoss becomes an **optional
enhancement**, not a requirement.

| Layer | Standalone (no BP) | With BP/BuddyBoss |
|---|---|---|
| Personal todos | ✅ block/shortcode on any page or a "My Todos" page | ✅ + profile "Todos" tab |
| Group todos | — (hidden) | ✅ group tab + assignment |
| REST `bptodo/v1` | ✅ `/todos`, `/todos/stats`, `/categories` | ✅ + `/groups/{id}/todos` |
| Blocks | ✅ `bptodo/my-todos` | ✅ + `bptodo/group-todos` |

**Dependency change (make BP optional):**
- Remove the hard requirement: `bptodo_check_buddypress_subsite_activation()` currently
  deactivates the plugin without BP — change to a soft, dismissible admin notice
  ("BuddyPress/BuddyBoss not detected — group todos and profile tabs are disabled;
  personal todos work normally").
- Gate all BP-only code behind `bptodo_is_bp_active()` (`function_exists('buddypress')`):
  profile tab registration, group extension, BP notifications, `groups_is_user_*` checks.
  Plain WP fallbacks: `manage_options`/owner caps, avatar via `get_avatar`, name via
  `display_name`, member URL via author page or the "My Todos" page.
- Plugin header: `Requires Plugins:` drops BuddyPress; readme/description reframed to
  "Personal + team todo lists for WordPress — works standalone, and integrates with
  BuddyPress/BuddyBoss when present." (WP.org slug unchanged.)

**Standalone surfaces:**
- The `bptodo/my-todos` block is the primary standalone UI — a site owner drops it on any
  page (or an auto-provisioned "My Todos" page) and every logged-in user sees *their own*
  list there. This is the SaaS-app experience, delivered by the same SSR+Interactivity+REST
  stack built below.
- Optional: a `[bptodo_list]` on a page does the same for shortcode users.

This does not change the build below — it **widens** it: the same REST + blocks + Interactivity
serve standalone, and the BP tab/group pieces become conditional wrappers.

---

## 0.2 Architecture & code organization (senior-level, no patchwork)

Today all logic (AJAX + shortcode + rendering + group fan-out + caps) lives in one
~1,900-line `public/class-bptodo-public.php`, with meta reads/writes and the same
"group admin-or-mod" permission check copy-pasted across handlers. We do **not** bolt
REST onto that. We introduce a clean **layered architecture** where a **domain layer is
the single source of truth**, and every entry point (REST, legacy AJAX, blocks, CLI,
templates) delegates to it. Legacy and modern share one implementation — that is the
"no patchwork" guarantee.

```
Domain (single source of truth)          includes/domain/
  Bptodo_Todo_Repository   — all CPT/meta CRUD + query + stats + group fan-out.
                             query(), get(), create(), update(), set_status(),
                             delete(), stats(), assign_to_members(). No caller
                             touches get_post_meta/update_post_meta directly again.
  Bptodo_Permissions       — one authority: can_view/create/edit/delete/complete($todo,$user),
                             can_view_scope($scope,$user), is_group_manager(). Replaces the
                             4+ duplicated inline cap checks.
  Bptodo_Categories        — term list/create + the allow_user_add_category gate.
  Bptodo_Todo (DTO)        — prepare_for_response(): one canonical todo shape for REST + SSR.

Application (thin; delegate to domain)   includes/rest/  public/ (legacy)  admin/ (cli)
  Bptodo_REST_Controller   — routes → Repository/Permissions. No business logic.
  Legacy AJAX handlers     — REFACTORED to call Repository/Permissions (not re-implement).
                             Same endpoints/nonce; internals now one line each.
  CLI demo seeder          — uses Repository::create() instead of raw wp_insert_post.

Presentation (mode-aware)                includes/render/  blocks/
  Bptodo_Renderer          — bptodo_render_todos($args): branch on layout mode; in modern
                             mode composes template parts; in legacy mode calls old templates.
  render/parts/*.php       — stat-cards, filter-bar, card, pager, empty-state (shared by
                             modern SSR + blocks; legacy templates untouched).
  blocks/*                 — block.json + render.php (call Bptodo_Renderer) + view.js + index.js.

Integration (optional, guarded)          includes/integrations/
  Bptodo_BuddyPress        — profile tab, group extension, BP notifications. Loaded only when
                             bptodo_is_bp_active(). Standalone core never references BP.
```

Principles:
- **One data authority** (`Bptodo_Todo_Repository`) — every read/write of the todo store goes through it. Kills scattered meta access and the "dual/stale key" bug class.
- **One permission authority** (`Bptodo_Permissions`) — every cap decision in one place, unit-probeable.
- **One todo shape** (`Bptodo_Todo::prepare_for_response`) — REST JSON and SSR card read the same DTO, so they never drift.
- **Thin entry points** — REST/AJAX/blocks/CLI contain wiring + validation only; business logic is in the domain.
- **Guarded integration** — BuddyPress is a loaded-on-demand integration module, not woven through the core.
- **Autoloading + naming** — PSR-like file-per-class under `includes/domain|rest|render|integrations/`, `Bptodo_*` prefix, registered through the existing loader; no new globals.
- **The god-class shrinks, not grows** — its logic moves into the domain layer; it keeps only thin BP-screen glue (or moves under `integrations/`). Net: fewer lines, clearer ownership.

This makes the legacy AJAX path *safer* too (it now shares the tested domain layer), and every
new surface (REST, blocks, quick-add, future mobile app) is a thin adapter over the same core.

---

## 0.1 Data reuse (no duplicate logic)

| Existing primitive | Reused by |
|---|---|
| `bptodo_get_todos_page($args)` → `{todos,total,total_pages,page,per_page}` | REST `GET /todos`, block `render.php` |
| `bptodo_count_todos($args)` → `{total,completed,pending}` | REST `GET /todos/stats`, block stat cards |
| `bptodo_get_group_member_task_stats($group_id,...)` | Group block Overview |
| `bptodo_filter_group_member_ids($group_id,$ids)` | Group assignment validation |
| Group fan-out create (public.php ~750-825), assign email (`Bptodo_Emails::send_assigned_email`) | REST `POST /groups/{id}/todos` |
| Meta keys `todo_status` ('complete'|absent), `todo_priority`, `todo_due_date`, `todo_group_id`, `todo_primary_id` | everywhere |
| Caps: owner (`post_author`), `edit_others_posts`, `groups_is_user_admin/mod`, `manage_options` | REST permission callbacks |

**Helper extension (one change, shared by REST + SSR):** add `priority`, `category`
(slug or id), `search`, `orderby`, `order` to `bptodo_get_todos_page()` (currently
author/group/status/paged only). This makes filtering **server-side and paginated**
(fixes the "search only covers current page" limitation) and gives REST + block + the
retained shortcode one query path.

---

## 1. REST API — `bptodo/v1` (Task 10)

New file: `includes/class-bptodo-rest-controller.php` (`Bptodo_REST_Controller`),
registered on `rest_api_init`. Auth = cookie + `X-WP-Nonce` (`wp_rest`), sent by
`apiFetch`/Interactivity. Every route has an explicit `permission_callback`.

| Method | Route | Params | Permission | Returns |
|---|---|---|---|---|
| GET | `/todos` | status, priority, category, search, per_page, page, orderby, order, user_id, group_id | logged-in; `user_id`≠self or `all` ⇒ `manage_options`; `group_id` ⇒ group member | `{items[], total, total_pages, page, per_page}` |
| POST | `/todos` | title*, description, category, priority, due_date, group_id?, assignees[]? | logged-in; group_id ⇒ group member (assignees ⇒ group admin/mod) | created todo object |
| GET | `/todos/{id}` | — | owner / admin / group admin-mod | todo object |
| PATCH | `/todos/{id}` | title, description, category, priority, due_date | owner / `edit_others_posts` / group admin-mod | updated todo |
| DELETE | `/todos/{id}` | — | owner / `edit_others_posts` / group admin-mod | `{deleted:true, id}` |
| POST | `/todos/{id}/complete` | — | owner / admin / group admin-mod | `{id, status:'complete'}` |
| DELETE | `/todos/{id}/complete` | — | same | `{id, status:'incomplete'}` (undo) |
| GET | `/todos/stats` | user_id?, group_id? | logged-in (+ scope rules) | `{total, pending, completed, progress}` |
| GET | `/categories` | — | logged-in | `[{id, slug, name, count}]` |
| POST | `/categories` | name* | logged-in + `allow_user_add_category` setting | created term |
| GET | `/groups/{group_id}/todos` | status, priority, search, per_page, page | group member | paginated items |
| POST | `/groups/{group_id}/todos` | title*, …, assignees[] | group member (assignees ⇒ admin/mod) | created + fan-out summary |

- **Todo object shape** (one `prepare_item_for_response`): `id, title, description, status, priority, due_date, due_human, category{id,slug,name}, group_id, author{id,name,avatar}, is_owner, can_edit, permalink, edit_url`.
- Permission helpers centralize the existing checks (`bptodo_user_can_edit_todo($todo,$user)`), so REST + AJAX + templates share one authority (kills the scattered inline copies).
- `check_ajax_referer` paths stay for the legacy UI during transition; REST uses `wp_rest` nonce.

---

## 2. Blocks — `bptodo/my-todos` + `bptodo/group-todos` (Task 11)

New dir: `blocks/` with build-free metadata blocks.

```
blocks/
  my-todos/
    block.json      (apiVersion 3, render:file:./render.php, viewScriptModule:file:./view.js, editorScript:file:./index.js, attributes, supports)
    render.php      (SSR: reuse bptodo_get_todos_page + bptodo_count_todos; emit Interactivity directives + context)
    view.js         (ES module: Interactivity store — see §3)
    index.js        (editor: wp.blocks.registerBlockType + wp.serverSideRender + InspectorControls, plain JS, no JSX)
  group-todos/
    block.json, render.php, view.js(re-uses shared store), index.js
  shared/
    render-list.php (shared SSR partial: stat cards, filter bar, card grid, pager — used by both blocks + shortcode + tabs)
```

- **Attributes** (my-todos): `perPage`(10), `defaultStatus`('all'), `defaultPriority`('all'), `showAdd`(bool), `showStats`(bool), `userId`(0). (group-todos adds `groupId`, `showAssignee`.)
- **Registration:** `register_block_type_from_metadata( BPTODO_PLUGIN_PATH . 'blocks/my-todos' )` etc., on `init`. Blocks are `render`-callback dynamic (no static save).
- **Editor UX:** `ServerSideRender` preview + `InspectorControls` (per_page, status/priority defaults, show add/stats). Category enum fetched from `/categories`.
- `render.php` sets the Interactivity **context** (`data-wp-context`) with initial `{page, status, priority, category, search, perPage, scope}` and server-renders page 1 so first paint is complete and correct.

---

## 3. Interactivity store + directives (Task 12)

`blocks/my-todos/view.js` (shared store, both blocks import the same registration):

```js
import { store, getContext } from '@wordpress/interactivity';
const { state, actions } = store('bptodo', {
  state: { /* derived: isBusy, hasFilters */ },
  actions: {
    *toggleComplete(e) { /* POST|DELETE /todos/{id}/complete, optimistic class swap, refresh stats */ },
    *deleteTodo(e)     { /* confirm (a11y modal), DELETE /todos/{id}, remove card, refresh stats */ },
    setStatus(e), setPriority(e), setSearch(e), setCategory(e), clearFilters(),
    *reload()          { /* GET /todos?…context, replace list HTML fragment, update pager + counts */ },
    *goToPage(e)       { /* context.page = n; reload() */ },
  },
});
```

Directives added to `shared/render-list.php` markup:
- Root: `data-wp-interactive="bptodo"`, `data-wp-context="…"`.
- Checkbox: `data-wp-on--change="actions.toggleComplete"`, `data-wp-bind--checked`.
- Delete btn: `data-wp-on--click="actions.deleteTodo"`, `aria-label`.
- Filters: `data-wp-on--change` (status/priority/category), `data-wp-on--input` (search, debounced in action).
- Pager links: `data-wp-on--click="actions.goToPage"`.
- List region: `data-wp-bind--aria-busy="state.isBusy"` + a loading skeleton toggle.

All network calls go to `bptodo/v1` with `X-WP-Nonce`; no admin-ajax, no full reload.
Data localized once via `wp_add_inline_script`/module data: `{ root:'/wp-json/bptodo/v1', nonce, strings, i18n }`.

---

## 4. Rewire shortcode + BP tabs; deprecate old paths (Task 13)

**Mode-aware renderer, three entry points** — a single `bptodo_render_todos( $args )` branches on `bptodo_layout_mode()`:
- **modern** → `shared/render-list.php` (SSR + Interactivity directives).
- **legacy** → the existing template for that surface (unchanged).

Entry points:
- `[bptodo_list]` / `[bptodo_by_category]` → map atts → args → `bptodo_render_todos()`. Same tags/atts; existing usage keeps working in either mode.
- Member tab (`member-todo-list.php`) → in modern mode its body calls the shared renderer (scope=member, author=displayed user); in legacy mode it renders as today.
- Group tab (`group/list.php`) → same, scope=group.
- **Add**: legacy full `/add` page remains in both modes for rich description; modern mode also offers an **inline quick-add** (Interactivity → `POST /todos`).

**Legacy retained (not deprecated):**
- `initCompleteTodo`, `initRemoveTodo`, `initSearchFilter`, `initTabs`, the AJAX handlers, and `bptodo-shortcode-template.php` all stay — they power `legacy` mode. Modern mode simply doesn't enqueue/use them.
- `initEditFormSubmit` stays (the full-page edit form is shared by both modes until the modern inline editor lands).
- Update `audit/manifest.json` (+summary): REST endpoints 0→N, blocks 0→2, add `bptodo_layout_mode` setting; note legacy vs modern asset split. Nothing removed.

---

## 5. Asset + registration wiring

- `init`: register the 2 blocks (auto-enqueues view module + editor script on demand).
- `rest_api_init`: register `Bptodo_REST_Controller`.
- Conditional front enqueue (already scoped) extends to: enqueue the Interactivity runtime + module data on todo tabs, shortcode pages, **and** any post containing a bptodo block (`has_block()`).
- Editor: enqueue `index.js` via block.json `editorScript`; localize category list.

---

## 6. Backward compatibility contract

- Option keys, meta keys, CPT, taxonomy, nonce `bptodo-todo-nonce` (legacy AJAX) all unchanged.
- Shortcodes keep the same tags + attributes (mapped internally).
- Group fan-out + assignment emails unchanged (REST calls the same code path).
- No DB migration. No breaking change for existing pages/sites.

---

## 7. Functional test plan (Task 14) — the gate

**A. REST probes (WP-CLI `wp eval` / curl with nonce), per route:**
1. `GET /todos` as member → only own; as admin `user_id=all` → all; as member `user_id=all` → 403.
2. `POST /todos` creates (meta correct); `PATCH` edits; `DELETE` removes; wrong user → 403.
3. `POST/DELETE /todos/{id}/complete` flips `todo_status`; group admin can complete a member's; stranger → 403.
4. `GET /todos/stats` counts match `bptodo_count_todos`.
5. `GET/POST /categories` respects `allow_user_add_category`.
6. `GET/POST /groups/{id}/todos` — member reads; non-member 403; assignees fan out + email queued.

**B. Browser (Playwright MCP, per surface, zero console errors):**
1. **Member tab** — SSR first paint shows todos with no flash; complete/undo (checkbox) updates card + stats **without reload**; delete → a11y confirm → card removed; filters (status/priority/category/search) filter **across all pages** now; pagination via Interactivity; quick-add creates inline.
2. **Group tab** — same, plus create with member assignment (fan-out visible), Overview member stats.
3. **Block in editor** — insert `My Todos`/`Group Todos`, ServerSideRender preview renders, InspectorControls change per_page/status and preview updates; save; **front renders + is interactive**.
4. **Shortcode** — `[bptodo_list]` still renders (now interactive), params respected.
5. **Presentation** — BuddyX + Reign + dark mode ([data-bx-mode]) + 390px: theme-native, no overflow, tokens inherited.
6. **Perf** — Network panel: first paint is SSR (no XHR needed to see the list); interactions are single REST calls (no admin-ajax, no full navigations); assets load only on todo/block surfaces.
7. **Roles** — subscriber can't reach admin scopes; group non-member blocked.

**C. Packaging gate:** whole-plugin PHP lint, pristine dist-zip lint (the check that caught the `%>` fatal), WPCS on new files, min rebuild, `blocks/` included in dist copy globs.

---

## 8. Implementation order (build the core first, prove it, then layer on)

Each step is verified before the next. The domain layer is built and proven **before**
any adapter, and the legacy path is refactored onto it early — so by the time modern
code lands, the core is already validated by the existing UI.

1. **Domain layer** — `Bptodo_Todo_Repository` (absorb + extend the query: priority/category/search/orderby), `Bptodo_Permissions`, `Bptodo_Categories`, `Bptodo_Todo` DTO. Wire into the loader. Probe each method via `wp eval` (query/create/update/set_status/delete/stats/assign).
2. **Refactor legacy AJAX + CLI onto the domain** — same endpoints, nonce, behaviour; internals become thin delegations; delete the duplicated inline cap checks. **Regression-verify legacy mode in the browser** — this proves the domain layer against the real UI before anything new is built.
3. **`Bptodo_REST_Controller`** — thin over domain + `Bptodo_Todo` DTO. Register on `rest_api_init`. Probe every route (test plan 7A).
4. **BP-optional** — `bptodo_is_bp_active()`; move BP tab/group/notifications into `integrations/Bptodo_BuddyPress` (load-on-demand); replace hard deactivation with a soft notice; standalone smoke (disable BP → personal todos + REST still work).
5. **`Bptodo_Renderer` + `render/parts/*`** — modern SSR parity with the legacy list (stat cards, filter bar, card, pager, empty state).
6. **Layout-mode setting** (`bptodo_layout_mode`, filter, admin toggle) + mode-aware wiring in the renderer.
7. **`bptodo/my-todos` block** (standalone) — block.json/render.php(→Renderer)/index.js → editor + front verify.
8. **Interactivity store** (`view.js` + directives) — complete/undo/delete/filter/paginate + inline quick-add via REST → verify (7B.1), zero reloads.
9. **`bptodo/group-todos` block** (BP-guarded, reuses parts) → group verify (7B.2).
10. **Rewire entry points** — shortcode + member tab + group tab through `Bptodo_Renderer` honoring the mode → per-surface verify (7B.4), legacy fallback intact.
11. **Full gate** — 7A/7B/7C, update `audit/manifest.json`(+summary), readme reframe + changelog, docs, min rebuild, `blocks/`+`includes/` in dist globs, pristine-zip lint, version bump.

## 9. Risks & mitigations
- **Interactivity API learning curve / hydration mismatch** → server-render exactly the markup the store expects; test hydrate on every surface.
- **BP tab is a screen, not a post** → tab template calls the shared renderer directly (not `do_blocks`), same markup/directives — verified in 7B.1/7B.2.
- **REST nonce on cached pages** → localize a fresh `wp_rest` nonce per request; Interactivity reads it at call time.
- **Regression for existing shortcode users** → shortcode kept, mapped to renderer, verified in 7B.4; AJAX kept one release.
- **Theme token drift (Reign lacks `--bx-*`)** → keep the existing fallback chain; dark literals for dark scope (per standing note).

---

## 10. Storage decision (conscious, not default)
**Keep CPT `bp-todo` + postmeta** for v4.0 — zero migration, existing data intact, and the
`Bptodo_Todo_Repository` **abstracts storage** so a future custom-table swap is contained to
one class if scale ever demands it (no caller changes). Guardrails now: ensure meta keys used
in `WHERE`/`ORDER BY` (`todo_status`, `todo_priority`, `todo_due_date`, `todo_group_id`) are
queried through the repository's paginated paths only; document that a per-user 10k+ todo scale
is the trigger to revisit a custom table. **Decision: CPT now, repository makes it swappable.**

## 11. Field roadmap (design for it, don't build it all)
The DTO + repository + REST schema are **meta-driven and extensible** so new fields are additive,
never a contract break.
- **v4.0 (modern core):** title, description, status, priority, due_date, category, group, assignees — the fields we have today, now first-class in REST/DTO/blocks.
- **Later (SaaS parity, not this pass):** subtasks/checklist, time reminders (vs due date), recurring rules, tags/labels, notes/attachments, saved views/lists.
- **Rule:** every new field = one repository accessor + one DTO key + one REST arg + one render part. No touching callers. **Decision: ship existing fields modern-first; build the extension seams, defer new features.**

## 12. Versioning & rollout — LOCKED
- **Version: 4.0.0** (major; architecture + positioning change).
- **Modern is the default for EVERYONE** — new installs *and* existing installs on update.
  Legacy stays available via the `bptodo_layout_mode` toggle as an instant fallback.
- **Because Modern ships to every live site on update, compatibility rigor is mandatory (not optional):**
  - **Auto-fallback:** `bptodo_layout_mode()` returns `legacy` automatically when WP < 6.5 or the block/Interactivity runtime is unavailable — Modern never hard-breaks an older site.
  - **Markup compatibility:** modern render parts keep the existing customer-facing class names/IDs where feasible so theme/child CSS keeps working; documented delta for any that change.
  - **Hook continuity:** the domain-level filters/actions still fire in Modern mode; the legacy `wp_ajax_bptodo_*` handlers remain registered so third-party code hooking them doesn't fatal.
  - **Upgrade notice:** readme `Upgrade Notice` + a dismissible admin pointer explaining the new layout and the one-click "switch to Classic" toggle.
  - **Rollback:** one setting flip → Legacy, lossless (shared data). This is the safety valve.
- Optional `4.0.0-beta` tag for testers before stable is encouraged given the blast radius.

## 13. Testing infrastructure (well-organized = tested)
Manual browser (Playwright) verifies UX; it does not replace automated tests for a refactor
of this size.
- **PHPUnit** — the domain layer is pure and highly testable: `Bptodo_Todo_Repository` (CRUD/query/stats/fan-out) and `Bptodo_Permissions` (every cap decision) get unit tests; `Bptodo_REST_Controller` gets REST integration tests (`WP_Test_REST_Controller_Testcase`). Scaffold via the `wp-plugin-ci-setup` skill (PHP 8.1–8.4 × WP matrix, PHPStan L5 + stubs, WPCS blocking).
- **The refactor is safe because** Step 2 re-points the *existing* UI at the new domain layer — so the current browser flows are the first regression suite, then PHPUnit locks it.
- **Decision: add PHPUnit + PHPStan + CI as part of this work** (the plugin has none today) — this is the backbone that makes "no patchwork" durable.

## 14. REST as a well-behaved resource
- Per-route `args` with `validate_callback` + `sanitize_callback`; `get_item_schema()` for the todo resource (enables block bindings + future clients + self-docs).
- Pagination via `X-WP-Total` / `X-WP-TotalPages` headers (not just body).
- Errors as `WP_Error` with correct HTTP status (400/401/403/404/409).
- Custom controller (domain-backed), **not** `show_in_rest` on the CPT — keeps the shape/permissions ours.
- Namespaced `bptodo/v1`; versioned so v2 can evolve without breaking clients.

## 15. Security, i18n, a11y as first-class (not a pass at the end)
- **Security:** every REST arg sanitized/validated; every SSR echo escaped (`esc_html`/`esc_attr`/`esc_url`); nonce (`wp_rest`) + capability on every route; no trust in client-sent ownership.
- **i18n:** all PHP + JS strings translatable; module strings via script-module i18n / localized data; text domain `wb-todo` unchanged.
- **a11y (Interactivity UI):** `aria-live` region announces list changes after complete/delete/filter; focus returns sanely after a card is removed; all controls keyboard-operable; the confirm is the existing accessible modal, not `window.confirm`. Matches the ux-foundation WCAG 2.1 AA bar.

## 16. Caching & performance budget
- **Stats:** intentionally uncached (status flips via meta, not `save_post`) — documented, single aggregate query.
- **Categories:** cached term list, invalidated on term create (single write path through `Bptodo_Categories`).
- **List SSR:** cacheable per `(scope, user/group, filters, page)`; invalidation is clean because **every write goes through the repository** — one place to bust keys.
- **JS budget:** the Interactivity view module stays lean (target < ~6 KB); no jQuery in modern mode; assets load only on todo/block surfaces (already enforced).

---

## 17. Existing-customer impact (data unchanged ≠ zero risk)
Data is untouched (CPT/meta/options/nonce/shortcodes identical, no migration). The real
risks are frontend/behavioural, and all are mitigated by defaulting existing installs to Legacy:

| Risk (not data) | Who | Mitigation |
|---|---|---|
| List *looks/behaves* different (block vs old template) | any site if Modern forced on | **Existing installs stay Legacy on update** + opt-in prompt; identical to today until they choose Modern |
| Custom CSS/JS targeting old markup breaks | sites with theme/child customizations | Legacy default; Modern parts keep compatible class names where feasible; documented |
| Code hooking `wp_ajax_bptodo_*` / plugin filters/templates | sites/other plugins extending it | Legacy retains all hooks; Modern keeps the same domain-level filters firing; hook map documented |
| Interactivity API needs WP 6.5+ | sites on older WP/older block support | **Auto-fallback to Legacy** when WP < 6.5 or block/interactivity unavailable — `bptodo_layout_mode()` degrades safely; Modern never hard-requires new WP |
| New REST routes exposed | all | Additive; every route has `permission_callback`; verified in test gate 7A that no route leaks another user's data |
| BP "deactivate if missing" → soft notice | non-BP or half-configured sites | Pure improvement: plugin now works standalone instead of self-deactivating; BP sites unaffected |
| Major version 4.0.0 | all | Semver signals the change; changelog + upgrade notice; rollback = flip toggle to Legacy (instant, lossless, since data is shared) |

**Rollback story:** because both modes read the same data, a customer who dislikes Modern flips
one setting back to Legacy and is exactly where they started — no export/import, no loss. That
shared-data design is *why* this is safe.

---

## Owner decisions — LOCKED
1. **Version:** 4.0.0 major.
2. **Rollout:** Modern default for **everyone** (new + existing on update); Legacy via toggle; auto-fallback on WP < 6.5. → drives the mandatory compat rigor in §12/§17.
3. **Field scope:** existing fields modern-first + extension seams; no new SaaS field this pass.
4. **Testing:** blocks/REST + manual browser/`wp eval` gate this pass; PHPUnit/PHPStan/CI deferred to a follow-up. The domain layer is built **pure and injectable** so tests drop in later with no refactor.

**Plan is fully specified. Ready to implement Step 1 (domain layer).**

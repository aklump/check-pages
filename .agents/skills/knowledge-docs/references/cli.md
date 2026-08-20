# The `know` CLI

`know <command> <path>` forwards directly to the engine's PHP entry point
(`php ~/.knowledge/app/bin/book.php <command> <path>`). There are exactly
six live commands. No other command exists in the running system.

| Command | Purpose | Typical args | Notes |
|---|---|---|---|
| `know create <path>` | Scaffold a brand-new, empty book at `<path>` (copies `book.yml`, `bookbinder.yml`, `plugins.yml`, `variables.yml`, empty `pages/`, `images/`, `src/`). | `know create /path/to/book` | Fails if `<path>` already exists. The resulting scaffold has `chapters: []` and an empty `pages/` — it cannot be bound until at least one page is authored. |
| `know validate <path>` | Validate every config file (`book.yml`, `bookbinder.yml`, `plugins.yml`, `variables.yml`) and every page's frontmatter against its JSON Schema; reports duplicate page IDs. | `know validate /path/to/book` | Read-only — makes no changes. Use to see *why* a `bind` failed. |
| `know repair <path>` | Auto-fix common issues: generate a missing `book.yml` `uuid`, sync missing scaffold files back in, write a derived `id` into any page frontmatter missing one, re-sync `book.php`'s header. | `know repair /path/to/book` | Run after `validate` surfaces a problem, then re-run `bind`. |
| `know bind <path>` | Compile ("bind") the book — the normal-path command for "compile the docs." Produces one output tree per entry in `bookbinder.yml`. | `know bind /path/to/book` | Runs validation and page-sync internally (see below). This is the only command a documentation author normally needs. |
| `know flush [path]` | Clear the book's `.cache/` directory and truncate its log file. | `know flush /path/to/book` | Rarely needed; use if bind output looks stale after external file changes. |
| `know upgrade <path>` | Run any pending schema/config migrations against an older book. | `know upgrade /path/to/book` | Only relevant when opening a book created by an older version of the engine. |

Any other command name (including `transcode`, `compile`, `migrate`,
`init`, `page`, `plugin.create`) is unrecognized by `know` and produces
`Unknown command: %s`.

## What `know bind` runs internally

A single `know bind <path>` invocation:

1. Runs the same validation pass as `know validate` (`OnValidateBook`)
   against every config file and page.
2. Syncs each chapter's `pages:` array in `book.yml` against the actual
   files on disk under `pages/<chapter-id>/` — a new page file is picked
   up automatically; editing `book.yml`'s `pages:` array by hand is only
   needed to control display order within a chapter, never to make a page
   appear.
3. Iterates every entry in `bookbinder.yml` in array order, producing one
   independent output tree per entry (e.g. one `markdown` pass and one or
   more `website` passes) from the same page source.

Because validation and page-sync already run as part of `bind`, a normal
"author a page, then compile" loop never needs a separate `validate` or
`repair` step first — those two commands exist purely for diagnosing and
fixing a book that a `bind` has already reported as broken.

A freshly `know create`d book has zero pages and cannot bind — `know bind`
against it fails immediately with "There are no pages in this book."
Author at least one page before the first bind.

## Orphaned commands — never suggest these

A second, disconnected command set (`compile`, `migrate`, `init`, `page`,
`plugin.create`, `transcode`) exists in the project's `app.php` +
`src_legacy/Command/*` classes. This code exists only in the project's own
source tree for historical reasons; it is not wired into Composer's
autoloader and nothing in the live `bin/`, `src/`, or `includes/` tree
references it. Running any of these names through the real `know` binary
produces `Unknown command: %s`. Never suggest `transcode`, `compile`,
`migrate`, `init`, `know page`, or `plugin.create` — they do not exist in
the command surface `know` actually dispatches.

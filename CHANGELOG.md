# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes between that release and the immediately preceding published release, not later changes on `master`.

**Release scope:** The `1.0.3...1.0.5` comparison includes an intermediate `V 1.0.4` commit, but no published GitHub Release for `1.0.4` was found.

## 1.0.5 - 2026-10-19

### Changed

- Replaced the plugin's SVG icon (`pix/icon.svg`) with a PNG icon (`pix/icon.png`).
- Added Git-reference-based concurrency configuration to the existing GitHub release workflow, without cancelling in-progress release runs.
- Updated the root plugin release identifier from `1.0.3` to `1.0.5` and the build number from `2026101500` to `2026101900`. The intervening version commit `V 1.0.4` did not correspond to a published GitHub Release.
- Removed a Moodle 4.5 comment from the minimum-version declaration in `version.php`; the required Moodle build remains `2024100700`.

No changes to video-rubric grading, submissions, or rubric functionality were identified between the published `1.0.3` and `1.0.5` tags.

[Compare 1.0.3...1.0.5](https://github.com/EduardoKrausME/moodle-mod_videorubric/compare/1.0.3...1.0.5)

## 1.0.3 - 2026-10-15

### Changed

- Removed explicit `$PAGE->requires->css('/mod/videorubric/styles.css')` calls from the grading (`grade.php`), rubric management (`rubric.php`), and submission (`submission.php`) pages. The tag comparison does not establish any additional styling changes.
- Updated the GitHub Actions CI PostgreSQL service from version 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, revising its startup environment variables and health-check settings.
- Updated the plugin release identifier from `1.0.2` to `1.0.3` and its build number from `2026101200` to `2026101500`.

[Compare 1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-mod_videorubric/compare/1.0.2...1.0.3)

# Conventional Commits Rules
#
# Commit message format:
#   <type>(<scope>): <description>
#
# Types:
#   feat     → New feature (MINOR version bump)
#   fix      → Bug fix (PATCH version bump)
#   perf     → Performance improvement (PATCH version bump)
#   refactor → Code refactoring (PATCH version bump)
#   docs     → Documentation change (does not trigger release)
#   style    → Code style change (does not trigger release)
#   test     → Add/update tests (does not trigger release)
#   chore    → Maintenance tasks (does not trigger release)
#   ci       → CI/CD changes (does not trigger release)
#   build    → Build system changes (does not trigger release)
#
# Breaking Change (MAJOR version bump):
#   feat!: breaking change description
#   or in commit body: BREAKING CHANGE: description
#
# Examples:
#   feat: add proxy authentication support
#   feat(scraper): add dataSafety scraper
#   fix(reviews): fix pagination token parsing
#   fix: handle empty response for search request
#   perf(http): add connection pooling
#   refactor(utils): extract common mapping logic
#   docs: update README
#   test: add ReviewsScraper unit tests
#   chore: update dependencies
#   feat!: GooglePlayScraper constructor parameters changed
#
# Scope examples:
#   scraper, app, search, reviews, list, developer,
#   http, utils, enum, config, test, docs

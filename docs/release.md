# Release Workflow

Cerberus uses Conventional Commits and `standard-version` to bump versions and maintain `CHANGELOG.md`.

## Commit Messages

Use Conventional Commit subjects:

```text
feat(applications): add anonymisation controls
fix(users): include avatar URL on serialized users
chore(release): configure automated changelog generation
```

Use `feat` for new capabilities, `fix` for bug fixes, and `chore` for maintenance. Add `!` or a `BREAKING CHANGE:` footer for breaking changes.

## Release

Preview the release first:

```bash
npm run release:dry-run
```

Create the release commit and tag:

```bash
npm run release
```

Push the release commit and tag:

```bash
git push --follow-tags
```

The admin UI reads the displayed version from `package.json`, and the changelog page reads `CHANGELOG.md`.

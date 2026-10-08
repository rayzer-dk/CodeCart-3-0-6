# Publish source to GitHub — Build 2.0.4

[Українська](GITHUB.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

Canonical repository: [CodeCartPro/CodeCartPro-3.0.6.0](https://github.com/CodeCartPro/CodeCartPro-3.0.6.0). The following publishes the existing local `main`; it does not create a new branch or replace the existing `origin` remote.

Before committing, inspect changed/untracked files. Never include real `config.php`, `admin/config.php`, credentials, API/license keys, runtime storage, logs, sessions or customer data. `.gitignore` does not remove secrets already tracked. Commit only explicitly reviewed files. Run required release checks before publishing.

```sh
git switch main
git status --short
git diff --check
git diff
git remote -v
```

If the `codecartpro` remote does not yet exist:

```sh
git remote add codecartpro https://github.com/CodeCartPro/CodeCartPro-3.0.6.0.git
```

Inspect destination history before pushing:

```sh
git fetch codecartpro
git log --oneline --all -10
git push codecartpro main
```

If Git rejects a non-fast-forward push, stop and review destination history. Do not force-push or overwrite another history. GitHub login must have write permission to the destination repository.

Source excludes installed Composer dependencies. For source setup, use the repository's lock file:

```sh
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php tools/release_check.php
```

A source “Download ZIP” is not the production ZIP. Production packaging installs vendor dependencies and includes them. Releases must reference reviewed commits already on `main`; publishing source does not itself create a verified production release.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)

# Publish source to GitHub
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

## Update through GitHub Codespaces in your browser

Open the repository's Codespace and open **Terminal → New Terminal**. Confirm that the workspace contains the correct `origin` and no uncommitted work:

```sh
pwd
git remote -v
git status --short
git switch main
git pull --ff-only origin main
```

If `git status --short` is not empty, **stop** and commit or stash your own work first. Never overwrite unknown changes.

Drag the verified **GitHub Source Update** ZIP into the Codespaces file explorer at the root of the repository. The ZIP must contain paths such as `upload/`, `README.md` and `documentation/`; do not extract any full production installer or runtime storage into the source repository. In the terminal substitute the exact uploaded ZIP filename:

```sh
unzip -o CodeCart_3.0.6_Source_Update_2.0.8_to_2.1.0.zip -d .
git status --short
git diff --check
git diff --stat
```

Review changes in the editor, including newly added files, then explicitly stage and commit the intended update:

```sh
git add CHANGELOG.md README.md README.uk.md README_FIRST.txt documentation upload/admin/view/template/catalog/product_list.twig upload/catalog/view/theme/codecart/template/extension/module/category_wall.twig upload/system/config/codecart_integrity.json
git add upload/admin/controller/catalog/product.php upload/admin/controller/catalog/public_document.php upload/admin/view/stylesheet/codecart-product-files.css upload/admin/view/template/catalog/download_list.twig upload/admin/view/template/catalog/product_form.twig upload/admin/view/template/catalog/public_document_form.twig upload/admin/view/template/catalog/public_document_list.twig upload/catalog/controller/common/header.php upload/system/library/seopro.php
git diff --cached --check
git diff --cached --stat
git commit -m "Update CodeCart 3.0.6"
git push origin main
```

Delete the uploaded ZIP from your workspace after use. The ZIP is only a source overlay; use a separate full production ZIP for deployments. If your repository already has these changes, do not reapply the overlay or create an empty commit.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)

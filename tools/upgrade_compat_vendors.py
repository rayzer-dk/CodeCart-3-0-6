#!/usr/bin/env python3
"""Vendor compatibility refresh; source packages are pinned by the release workflow."""
import json
import pathlib
import shutil

ROOT = pathlib.Path(__file__).resolve().parents[1]
NODE = pathlib.Path("/tmp/codecart-compat-vendors/node_modules")
changed = []

def copy(package, source, dest):
    origin = NODE / package / source
    target = ROOT / dest
    if not origin.is_file():
        raise FileNotFoundError(str(origin))
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(origin, target)
    changed.append(dest)

for side in ("admin", "catalog"):
    base = f"upload/{side}/view/javascript/jquery"
    copy("jquery", "dist/jquery.min.js", f"{base}/jquery-3.8.0.min.js")
    copy("jquery", "dist/jquery.min.map", f"{base}/jquery-3.8.0.min.map")
    copy("jquery-migrate", "dist/jquery-migrate.min.js", f"{base}/jquery-migrate-3.6.0.min.js")
    dtp = f"{base}/datetimepicker"
    copy("eonasdan-bootstrap-datetimepicker", "build/js/bootstrap-datetimepicker.min.js", f"{dtp}/bootstrap-datetimepicker.min.js")
    copy("eonasdan-bootstrap-datetimepicker", "build/css/bootstrap-datetimepicker.min.css", f"{dtp}/bootstrap-datetimepicker.min.css")
    if side == "admin":
        copy("eonasdan-bootstrap-datetimepicker", "src/js/bootstrap-datetimepicker.js", f"{dtp}/bootstrap-datetimepicker.js")
    for source in ("moment.min.js", "moment-with-locales.js", "moment-with-locales.min.js", "locales.js", "locales.min.js"):
        copy("moment", f"min/{source}", f"{dtp}/moment/{source}")

# The native datepicker and proprietary SVG dashboards remain the primary implementations.
flot = "upload/admin/view/javascript/jquery/flot"
for old in (ROOT / flot).glob("jquery.*.js"):
    name = old.name
    origin = NODE / "flot" / "dist" / "es5" / name
    if origin.is_file():
        copy("flot", f"dist/es5/{name}", f"{flot}/{name}")
# Do not silently leave a legacy 0.8.1 primary Flot implementation.
if f"{flot}/jquery.flot.js" not in changed:
    raise RuntimeError("Flot 4.2.6 core could not be located")
# Flot 4.2.6 distributes the combined ES5 bundle already minified under .js.
# Match both legacy entry points; older add-on plugin files remain for extensions.
copy("flot", "dist/es5/jquery.flot.js", f"{flot}/jquery.flot.min.js")
jqv = "upload/admin/view/javascript/jquery/jqvmap"
for file in ("jquery.vmap.js", "jquery.vmap.min.js"):
    copy("jqvmap", f"dist/{file}", f"{jqv}/{file}")
copy("jqvmap", "dist/jqvmap.css", f"{jqv}/jqvmap.css")

# Update core references only. Old names remain in place for external extensions.
for path in (ROOT / "upload").rglob("*"):
    if not path.is_file() or path.suffix not in (".twig", ".php"):
        continue
    if "/storage/" in path.as_posix():
        continue
    content = path.read_text(encoding="utf-8")
    updated = content.replace("jquery/jquery-3.7.1.min.js", "jquery/jquery-3.8.0.min.js")
    updated = updated.replace("jquery/jquery-migrate-3.4.1.min.js", "jquery/jquery-migrate-3.6.0.min.js")
    if content != updated:
        path.write_text(updated, encoding="utf-8")
        changed.append(str(path.relative_to(ROOT)))

composer = ROOT / "composer.json"
config = json.loads(composer.read_text(encoding="utf-8"))
config["require"]["guzzlehttp/guzzle"] = "^8.5.5"
config["require"]["guzzlehttp/promises"] = "^3.1"
config["require"]["guzzlehttp/psr7"] = "^3.5.1"
composer.write_text(json.dumps(config, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print("Updated vendor files:", len(changed))
for item in changed:
    print(item)

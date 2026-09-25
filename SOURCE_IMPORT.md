# Source import status

The repository scaffolding, Composer manifest and CI release pipeline are configured.

The current CodeCart PRO 3.0.6.0 Build 1.7.5 source package must be imported at repository root preserving its existing directories:
- upload/
- install/
- documentation/
- tools/
- README_FIRST.txt
- CHANGELOG.md

Do not commit the bundled upload/system/storage/vendor directory from the ZIP. CI reconstructs it from composer.lock.

After the source import, generate and commit composer.lock from the repository composer.json, then the Production Release Check workflow becomes the release gate.

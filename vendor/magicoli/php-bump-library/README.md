# PHP Library for Project Version Management

[![Version](https://img.shields.io/badge/Version-1.0.2-blue.svg)](#) [![Stable](https://img.shields.io/badge/Stable-1.0.2-green.svg)](#) [![License](https://img.shields.io/badge/License-AGPLv3-purple.svg)](#)

This library provides tasks for automating versioning of your PHP projects.

It allows you to increment the version based on different levels (major, minor, patch, dev, beta, rc), and update version references in various files such as PHP files, README.md, package.json, and readme.txt.

## Installation

Run the following command in your project directory:

```bash
composer require --dev magicoli/php-bump-library
```

And add the following script to your `composer.json` file:

```json
 "scripts": {
    "bump-version": "robo --load-from=vendor/magicoli/php-bump-library/RoboFile.php bump:version"
  }
```

## Usage

```bash
composer bump-version [level]
```

Replace `[level]` with the desired level of version increment, such as `major`, `minor`, `patch`, `rc`, `beta`, or `dev`. If you ommit it, the default level is `patch`.

Alternatively, you can run the script directly with the following command:

```bash
robo bump:version
# or
robo --load-from=path/to/RoboFile.php bump:version
```

Make sure to adjust RoboFile.php to the actual path of the file in your project.

## About Versioning

Semantic Versioning follows a specific order of version increments:

Development stages (M.m.p-stage):

- Dev: development versions that are not yet stable or released.
- Beta: pre-release versions that are closer to the stable release but may still have minor issues.
- RC: release candidates, which are close to the final release but may require additional testing.

Releases (M.m.p):

- Patch: backward-compatible bug fixes.
- Minor: added functionality, still backward-compatible manner.
- Major: big bada boom.

Note that `dev`, `beta`, and `rc` versions are considered inferior to the normal versions and are typically used in pre-release stages or development cycles: `1.0-dev` < `1.0-beta` < `1.0-rc` < `1.0`.

For example, if your version is `1.0.0` and you bump it on the `dev` level, new version will be `1.0.1-dev` (note the pach increment). If you bump the `dev` to `beta`, it will keep its main version and become `1.0.1-beta`. And if you bump `1.0.1-beta` without arguments, the new version will be `1.0.1`.

## License

This library is licensed under the AGPL-v3 License.

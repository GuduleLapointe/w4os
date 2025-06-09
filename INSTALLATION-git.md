# Installation Instructions for Developers / Night Builds

To ensure that all submodules are included (especially the helpers submodule), make sure to clone the repository using the `--recurse-submodules` option:

```bash
cd wp-content/plugins/
git clone --recurse-submodules https://github.com/GuduleLapointe/w4os.git
cd w4os
./setup-git.sh
wp plugin activate w4os
```

If you've already cloned the repository without submodules, you can initialize and update them with:

```bash
cd wp-content/plugins/w4os
git submodule update --init --recursive
./setup-git.sh
```

Then follow instructions from step 2 in INSTALLATION.md

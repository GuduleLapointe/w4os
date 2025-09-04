# W4OS Themes Compatibility

Although we try to get the plugin performances totally independant from the site context, we noticed conflicts with some themes. Here is a non-exaustive list of popular plugins we had the opportunity to test.

## Default WordPress Theme: fully compatible

- ✅ Twenty Twenty-Five
  Status: ALL PASSED

- ✅ twentytwentyfour
  Status: ALL PASSED

- ✅ twentytwentythree
  Status: ALL PASSED

- ✅ twentytwentyone
  Status: ALL PASSED

- ✅ twentytwenty
  Status: ALL PASSED

## ✅ Salient Theme

Excellent display out of the box

Status: ALL PASSED

## ✅ Unos

Status: ALL PASSED

## ✅ GeneratePress Theme

Status: ALL PASSED

## ✅ Storefront (woocommerce default theme): Fully compatible

Status: ALL PASSED

## Astra Theme

Status: ALL PASSED

## Neve Theme

Status: ALL PASSED (v3 or v2 with v3 features enabled)
Failed Tests: (v2 only)
  1. Page title must contain 'Avatar not found' or default 404 message (got: '')

## The7 Theme

Status: ALL PASSED (v3 or v2 with v3 features enabled)
Failed Tests: (v2 only)
  1. Page title must contain 'Avatar not found' or default 404 message (got: 'Profile')

## Divi Theme: compatible (with additional plugin)

Status: ALL PASSED (v3 or v2 with v3 features enabled)
Failed Tests: (v2 only)
  1. [test-profile] Avatar name (Way Forest)  must appear in page title ()
Failed Tests: (v2 and v3)
  2. Gutenberg blocks not supported

We used to love Divi, but they live in their own world, with few regards to integration and WP standards, which makes very difficult to adapt the plugin for full compatibility with their theme.

Some support is provided with the complimentary plugin, [w4os Divi Theme adapter](https://github.com/GuduleLapointe/w4os-divi) though, but we cannot guarantee support for any issue. Contributions are welcome if you want to complete the adapter, we will gratefully integrate your additions.

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

## ✅ GeneratePress Theme

Status: ALL PASSED

## ✅ Storefront (woocommerce default theme): Fully compatible

Status: ALL PASSED

## Astra Theme

Failed Tests:
  1. Page title must contain 'Avatar not found' or default 404 message (got: '')

## Neve Theme

Failed Tests:
  1. Page title must contain 'Avatar not found' or default 404 message (got: '')

## The7 Theme

Failed Tests:
  1. Page title must contain 'Avatar not found' or default 404 message (got: 'Profile')

## Divi Theme: compatible (with additional plugin)

Failed Tests:
  1. Avatar name must be in head title (Profile – W4OS)
  2. Proper head title for Avatar Not Found page (Profile – W4OS)

Divi doesn't recognize Gutenberg blocks by default, although they can still be used in Divi Builder with their shortcode. The complimentary plugin, [w4os Divi Theme adapter](https://github.com/GuduleLapointe/w4os-divi) adds full support for Divi-style blocks.

[![CircleCI](https://circleci.com/gh/creativewave/ps-cwblockstores/tree/master.svg?style=shield&circle-token=3e6c2f631fa574fafff98cf2265da5d5ae2f142c)](https://circleci.com/gh/creativewave/ps-cwblockstores/tree/master)

# Block Stores

## About

Block Stores is a Prestashop module to display a dropdown of stores to view the current page. The destination page defaults to the selected store's home page if it hasn't the origin page.

This module is currently used in production websites with Prestashop 1.6 and PHP 7+, but you may need to tweak some CSS and/or JS for your needs. The best way to make changes and still get updates is to create your own git branch and rebase/merge/cherry-pick new versions or specific commits.

## Installation

This module is best used with Composer managing your Prestashop project globally. This method follows best practices for managing external dependencies of a PHP project.

Create or edit `composer.json` in the Prestashop root directory:

```json
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-cwblockstores"
  }
],
"require": {
  "creativewave/ps-cwmedia": "^1"
},

```

Then run `composer update`.

## Todo

* Feature: configuring default page link

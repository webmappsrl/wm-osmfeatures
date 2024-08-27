# Changelog

All notable changes to `wm-osmfeatures` will be documented in this file.

## Changed data retrieved from json string to json array - 2024-08-27

This release introduce a breaking change to the data provided by the package. Infact, the package now provides osmfeatures data as a json array and not string anymore

## Added model option in wm osmfeatures command - 2024-05-08

This release add the model option to wm osmfeatures command.

**Full Changelog**: https://github.com/webmappsrl/wm-osmfeatures/compare/1.2.0...1.3.0

## Added new trait and manual import from txt file - 2024-05-07

This Release add the `OsmfeaturesImportableTrait` along with 2 new commands:

- `wm-osmfeatures:import-first {model} {filepath}`  for import specified model based on a osmfeatures id list from a .txt file
- `wm-osmfeatures:import-sync` that uses local osmfeatures_ids to update the database.

**Full Changelog**: https://github.com/webmappsrl/wm-osmfeatures/compare/1.1.1...1.2.0

## 1.1.1 - 2024-05-06

Fixed keywords

**Full Changelog**: https://github.com/webmappsrl/wm-osmfeatures/compare/1.1.0...1.1.1

## 1.1.0 - 2024-05-06

**Full Changelog**: https://github.com/webmappsrl/wm-osmfeatures/compare/1.0.0...1.1.0

## 1.0.0 - 2024-05-03

This is the initial release of the package.
This release include base configuration for initializing the application's models to receive osmfeatures data, as long as testing basic functionalities

### What's Changed

* Bump dependabot/fetch-metadata from 1.6.0 to 2.1.0 by @dependabot in https://github.com/webmappsrl/wm-osmfeatures/pull/1

### New Contributors

* @dependabot made their first contribution in https://github.com/webmappsrl/wm-osmfeatures/pull/1

**Full Changelog**: https://github.com/webmappsrl/wm-osmfeatures/commits/1.0.0

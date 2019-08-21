# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html), with breaking changes allowed in `0.x.0` beta releases. See the README for more information.

## Unreleased

BC breaks:

- objects are not exported through `__set_state()` anymore
- `VarExporter::NO_SET_STATE` constant has been removed
- `VarExporter` constant values have been changed

## [0.2.1] - 2019-04-17

**New option**: `VarExporter::INLINE_NUMERIC_SCALAR_ARRAY` ([#3](https://github.com/brick/varexporter/issues/3))

Formats numeric arrays containing only scalar values on a single line.

## [0.2.0] - 2019-04-09

**Experimental support for closures** 🎉

Minor BC break: `export()` does not throw an exception anymore when encountering a `Closure`. To get the old behaviour back, use the `NO_CLOSURES` option.

## [0.1.2] - 2019-04-08

**Bug fixes**

- Static properties in custom classes were wrongly included—`unset()`—in the output

**Improvements**

- Circular references are now detected, and throw an `ExportException` instead of erroring.

## [0.1.1] - 2019-04-08

**Bug fixes**

- Single-letter properties were wrongly exported using `->{'x'}` notation.

**Improvements**

- Exception messages now contain the path (array keys / object properties) to the failure:

    > `[foo][bar][0]` Type "resource" is not supported.

## [0.1.0] - 2019-04-07

First release.

[0.2.1]: https://github.com/brick/varexporter/releases/tag/0.2.1
[0.2.0]: https://github.com/brick/varexporter/releases/tag/0.2.0
[0.1.2]: https://github.com/brick/varexporter/releases/tag/0.1.2
[0.1.1]: https://github.com/brick/varexporter/releases/tag/0.1.1
[0.1.0]: https://github.com/brick/varexporter/releases/tag/0.1.0

# Unique Text Input Field for Symphony CMS

A field for [Symphony CMS][ext-symphony-cms] that enforces uniqueness

-   [Installation](#installation)
-   [Requirements](#dependencies)
-   [Dependencies](#dependencies)
-   [Basic Usage](#basic-usage)
-   [Support](#support)
-   [Contributing](#contributing)
-   [License](#license)

## Installation

Clone the latest version to your `/extensions` folder and run composer to install required packaged with

### Manually (git + composer)
```bash
$ git clone https://github.com/pointybeard/symext-field-unique-input.git field_uniquetextinput
$ composer update -vv --profile -d ./field_uniquetextinput
```
After finishing the steps above, enable "Section Model Builder" though the administration interface or, if using [Orchestra][ext-orchestra], with `bin/extension enable field_uniquetextinput`.

### With Orchestra

1. Add the following extension defintion to your `.orchestra/build.json` file in the `"extensions"` block:

```json
{
    "name": "field_uniquetextinput",
    "repository": {
        "url": "https://github.com/pointybeard/symext-field-unique-input.git"
    }
}
```

2. Run the following command to rebuild your Extensions

```bash
$ bin/orchestra build \
    --skip-import-sections \
    --database-skip-import-data \
    --database-skip-import-structure \
    --skip-create-author \
    --skip-seeders \
    --skip-git-reset \
    --skip-composer \
    --skip-postbuild
```

# Requirements

- This extension works with PHP 7.4 or above.

# Dependencies

This extension depends on the following Composer libraries:

-   [PHP Helpers][dep-helpers]
-   [Symphony CMS: Extended Base Class Library][dep-symphony-extended]

## Usage

Enable this field via the interface and add it to your sections like any other field.

This field behaves identically to a standard text input field, however, it enforces uniqueness of the handle. Note, output in Data Sources cannot be grouped by a Unique Text Input field.

There are two (2) modes which allow the choice between throwing an error, e.g. "This must be unique", or maintain uniqueness by automatically by appending a number to the handle value, eg. `my-entry-handle-2`.

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker][ext-issues],
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing to this project][doc-contributing] documentation for guidelines about how to get involved.

## Author
-   Alannah Kearney - http://github.com/pointybeard
-   See also the list of [contributors][ext-contributor] who participated in this project

## License
"Unique Text Input Field for Symphony CMS" is released under the MIT License. See [LICENCE][doc-licence] for details.

[doc-contributing]: https://github.com/pointybeard/symext-field-unique-input/blob/master/CONTRIBUTING.md
[doc-licence]: http://www.opensource.org/licenses/MIT
[dep-helpers]: https://github.com/pointybeard/helpers
[dep-symphony-extended]: https://github.com/pointybeard/symphony-extended
[ext-issues]: https://github.com/pointybeard/symext-field-unique-input/issues
[ext-symphony-cms]: http://getsymphony.com
[ext-orchestra]: https://github.com/pointybeard/orchestra
[ext-contributor]: https://github.com/pointybeard/symext-field-unique-input/contributors

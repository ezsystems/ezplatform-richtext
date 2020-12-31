# Ibexa RichText Field Type Bundle

This Bundle provides RichText (`ezrichtext`) Field Type
for [Ibexa DXP](https://www.ibexa.co/products) and Ibexa Open Source. It is a Field Type for
supporting rich formatted text stored in a structured XML format.

This Field Type succeeds the former [XMLText](https://github.com/ezsystems/ezplatform-xmltext-fieldtype)
Field Type found in eZ Publish 5.x and before.

## Installation

1. In your Ibexa project, require this package by the Composer.

    ```bash
        composer require ezsystems/ezplatform-richtext
    ```

2. Enable the Bundle in `config/bundles.php`:

    ```php
        return [
            // ...
            EzSystems\EzPlatformRichTextBundle\EzPlatformRichTextBundle::class => ['all' => true],
            // ...
        ];
   ```

3. Remember to clear the Symfony Cache (for `SYMFONY_ENV` your project uses).
    ```bash
        php bin/console cache:clear
    ```

## Background

When looking to find a structured text format for Ibexa, we wanted to pick something that
was widely used in the industry and which could support the custom & embed structures we have
had in eZ Publish for years, which has enabled us to seamlessly target several channels / formats
based on the same internal stored formats.

What we had at the time was inspired by early drafts of XHTML 2.0, a standard made for the most
part obsolete by html5.

We also knew from experience we had to support html5 as an input/output format for RichText editors
to reduce the number of customizations we had to apply on top of available editors. Which would make
it hard to keep up to date, and forces us to deal with edge cases ourselves instead of relying on
the editor doing it for us.

In RichText we have ended up with a solution that is built on a more widely used internal format,
moved closer to html5 supported by editors, and better suited to support wider range of formats.

## Format

### Storage format

Storage format in RichText is [DocBook](http://docbook.org/), for further info on its schema and how we
extend it with RELAX NG, see [Resources/schemas/docbook/](src/bundle/Resources/schemas/docbook).

### Input/Output formats

This Field Type supports several output and input formats, DocBook, ezxml _(legacy format)_, and
two forms of HTML5 _(edit and output)_.

Further reading on these formats and how they uses schemas, XSLT and DTD, see [Resources/](src/bundle/Resources).

## Migrating

The architecture allows for migration to and from several formats in the future, currently
the following is the main one supported:

### From eZ Publish

For migrating from eZ Publish's XMLText format, have a look at the seperate [XMLText Field Type](https://github.com/ezsystems/ezplatform-xmltext-fieldtype).

## COPYRIGHT
Copyright (C) 1999-2021 Ibexa AS (formerly eZ Systems AS). All rights reserved.

## LICENSE
This source code is available separately under the following licenses:

A - Ibexa Business Use License Agreement (Ibexa BUL),
version 2.3 or later versions (as license terms may be updated from time to time)
Ibexa BUL is granted by having a valid Ibexa DXP (formerly eZ Platform Enterprise) subscription,
as described at: https://www.ibexa.co/product
For the full Ibexa BUL license text, please see:
https://www.ibexa.co/software-information/licenses-and-agreements (latest version applies)

AND

B - GNU General Public License, version 2
Grants an copyleft open source license with ABSOLUTELY NO WARRANTY. For the full GPL license text, please see:
https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

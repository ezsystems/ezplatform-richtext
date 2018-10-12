# eZ Platform RichText Field Type Bundle

This Bundle provides RichText (`ezrichtext`) Field Type for eZ Platform 2.4 and higher.
It is a Field Type for supporting rich formatted text stored in a structured XML format.

This package overrides services provided by the `eZ\Publish\Core\FieldType\RichText` namespace of
`ezsystems/ezpublish-kernel` package.

This Field Type succeeds the former [XMLText](https://github.com/ezsystems/ezplatform-xmltext-fieldtype)
Field Type found in eZ Publish 5.x and before.

## Installation

1. In your eZ Platform 2.4+ project, require this package by the Composer.

    ```bash
        composer require ezsystems/ezplatform-richtext
    ```

2. Enable the Bundle in `AppKernel.php`:

    ```php
        public function registerBundles()
        {
           $bundles = [
               // ...
               new EzSystems\EzPlatformRichTextBundle\EzPlatformRichTextBundle(),
           ];

           // ...
        }
   ```

3. Remember to clear the Symfony Cache (for `SYMFONY_ENV` your project uses).
    ```bash
        php bin/console cache:clear
    ```

## Background

When looking to find a structured text format for eZ Platform, we wanted to pick something that
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
Copyright (C) 1999-2018 eZ Systems AS. All rights reserved.

## LICENSE
http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2

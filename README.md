[![Build Status](https://travis-ci.org/lucascherifi/PdfParserBundle.svg?branch=master)](https://travis-ci.org/lucascherifi/PdfParserBundle) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/b5492302-98fd-4698-ba33-fd3251276adb/big.png)](https://insight.sensiolabs.com/projects/b5492302-98fd-4698-ba33-fd3251276adb)

PdfParserBundle
===============

The purpose of this bundle is to parse the contents of PDF files using DocumentProcessors. It returns formatted data ready to use.

For now, three are available in this bundle:
- "LCL - Relevé de compte courant particulier"
- "BforBank - Relevé de compte courant particulier"
- "Société Générale - Relevé de compte courant professionnel"

Feel free to propose new DocumentProcessors using Pull Requests.

Make good use and do not hesitate to contribute to this project.

Installation
------------

### Prerequisites

The binary `pdftotext` is required to use this bundle.

It's available as the `poppler-utils` apt package (Debian/Ubuntu) or the OSX brew package `poppler`.

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require lucascherifi/pdf-parser-bundle "dev-master"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Kasifi\PdfParserBundle\PdfParserBundle(),
        );

        // ...
    }

    // ...
}
```

### Step 3: Usage

#### The `pdf-parser:parse` command

Parse document of many types.

Usage:
```sh
  pdf-parser:parse [options] [--] [<processor>] [<filepath>]
Arguments:
  processor                The id of the processor
  filepath                 The absolute path to the PDF file to parse.

Options:
      --format=FORMAT      The output format (console, json, yml) [default: "console"]
```

##### Example in interactive mode (no arguments):

Type the following command:
```sh
> $ sf pdf-parser:parse
```
The console command asks for a processor. Type `bfb`.
```sh
Which processor to use?
  [bfb   ] B For Bank - Compte courant particulier
  [lcl   ] LCL - Compte courant particulier
  [sg_pro] Société Générale - Compte courant professionnel
 > bfb
```
The console command asks for a file. Select the first one (`0`).
```sh
Which file? Enter the key.
  [0] /var/www/app/data/pdf/bfb.pdf
  [1] /var/www/app/data/pdf/lcl.pdf
  [2] /var/www/app/data/pdf/sg.pdf
 > 0
```
Now the command will print the parsed rows.
```sh
+--------------------------+---------------------------+-------+-------+
| date                     | label                     | value | debit |
+--------------------------+---------------------------+-------+-------+
| 2015-08-05T12:00:00+0200 | VIREMENT                  | XXX   |       |
|                          | XXXX                      |       |       |
| 2015-08-10T12:00:00+0200 | VIREMENT                  | XXX   | 1     |
| 2015-08-24T12:00:00+0200 | VIREMENT                  | XXX   | 1     |
|                          | XXXXXXX                   |       |       |
| 2015-08-24T12:00:00+0200 | PAIEMENT PAR CARTE        | XX    | 1     |
|                          | XXXXXXXXXXXXXXXXXX        |       |       |
|                          | XXXXXXXXXXXXXXXXXX        |       |       |
| 2015-08-25T12:00:00+0200 | PAIEMENT PAR CARTE        | XX.XX | 1     |
|                          | XXXXXXXXXXXXXX            |       |       |
|                          | XXXXXXXXXXXXXXXXX         |       |       |
| 2015-08-27T12:00:00+0200 | PAIEMENT PAR CARTE        | X.X   | 1     |
|                          | XXXXXXXXXXXXXXXXXX        |       |       |
|                          | XXXXXXXXXXXXXXXXX         |       |       |
| 2015-08-28T12:00:00+0200 | VIREMENT                  | XX    |       |
|                          | XXXXXXXXXXXXXXXX          |       |       |
| 2015-08-28T12:00:00+0200 | VIREMENT                  | XXX   | 1     |
|                          | XXXXXXX                   |       |       |
| 2015-08-31T12:00:00+0200 | VIREMENT                  | XXX   | 1     |
|                          | XXXXXXXXXXX               |       |       |
| 2015-08-31T12:00:00+0200 | PAIEMENT PAR CARTE        | XX.XX | 1     |
|                          | XXXXXXXXXXXXXX            |       |       |
|                          | XXXXXXXXXXXXXXX           |       |       |
| 2015-08-31T12:00:00+0200 | PAIEMENT PAR CARTE        | XX    | 1     |
|                          | XXXXXXXXXXX               |       |       |
|                          | XXXXXXXXXXXXXXXX          |       |       |
| 2015-08-31T12:00:00+0200 | PAIEMENT PAR CARTE        | XX    | 1     |
|                          | XXXXXXXXXXX               |       |       |
|                          | XXXXXXXXXXXXXXXX          |       |       |
| 2015-08-31T12:00:00+0200 | PAIEMENT PAR CARTE        | XX.X  | 1     |
|                          | XXXXXXXXXXXXXXXX          |       |       |
|                          | XXXXXXXXXXXXXXXX          |       |       |
+--------------------------+---------------------------+-------+-------+
```
(values has been hidden in this example)
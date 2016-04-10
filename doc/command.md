Usage
=====

The `pdf-parser:parse` command
------------------------------

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

### Example in interactive mode (no arguments):

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

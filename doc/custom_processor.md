Custom processor
================

How to add a custom document `Processor`?

Step 1: Create the `Processor` class
------------------------------------

Creates a `AppBundle/Processor/MyProcessor.php` file with the following content:

```php
<?php
// AppBundle/Processor/MyProcessor.php

// ...

namespace AppBundle\Processor;

use Kasifi\PdfParserBundle\Processor\ProcessorInterface;
use Kasifi\PdfParserBundle\Processor\Processor;
use Doctrine\Common\Collections\ArrayCollection;

class MyProcessor extends Processor implements ProcessorInterface
{
    protected $configuration = [
        'id'                   => 'my',
        'name'                 => 'Title of my processor',
        'startConditions'      => ['/A text content in the header row ot the table to parse/'],
        'endConditions'        => ['/A text content after the table/', '/Another text content after the table found in another page/'],
        'rowMergeColumnTokens' => [1], // Columns that when an empty space is found in the following line then this line is merged with the previous one.
        'rowSkipConditions'    => ['useless string', 'another useless string'],
        'rowsToSkip'           => [0, 1],
    ];

    /**
     * @param ArrayCollection $data
     *
     * @return ArrayCollection
     */
    public function format(ArrayCollection $data)
    {
        $data = $data->map(function ($item) {
            // Date
            $dateRaw = $item[1];
            $date = new \DateTime();
            $date->setDate(substr($dateRaw, 6, 4), substr($dateRaw, 3, 2), substr($dateRaw, 0, 2));
            $date->setTime(12, 0, 0);

            // Value
            if (strlen($item[3])) {
                $value = abs((float)str_replace(',', '.', str_replace(' ', '', $item[3])));
                $debit = true;
            } else {
                $value = (float)str_replace(',', '.', str_replace(' ', '', $item[4]));
                $debit = false;
            }

            return [
                'date'  => $date,
                'label' => $item[2],
                'value' => $value,
                'debit' => $debit,
            ];
        });

        return $data;
    }
}

```

Step 2: Register this class as a `processor` tagged service
-----------------------------------------------------------

Edit your `app/config/services.yml` file by adding the following lines:

```yaml
services:
    ...
    app.my_processor:
      class : AppBundle/Processor/MyProcessor
      tags:
        -  { name: kasifi_pdfparser.processor }
```

Step 3: Now you can use your custom processor to parse your PDF files
---------------------------------------------------------------------

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
  [my    ] Title of my processor
 > my
```

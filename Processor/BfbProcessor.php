<?php

namespace Kasifi\PdfParserBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class BfbProcessor
 */
class BfbProcessor extends Processor implements ProcessorInterface
{
    protected $configuration = [
        'id'                   => 'bfb',
        'name'                 => 'B For Bank - Compte courant particulier',
        'startConditions'      => ['/Libellé de l\'opération/'],
        'endConditions'        => ['/BforBank vous informe/', '/Nous vous rappelons qu\'en cas de différend/'],
        'rowMergeColumnTokens' => [1],
        'rowSkipConditions'    => ['Votre ancien solde', '063584840313690801pli', 'Votre nouveau solde'],
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

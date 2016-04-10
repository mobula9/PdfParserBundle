<?php

namespace Kasifi\PdfParserBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class LclProcessor.
 */
class LclProcessor extends Processor implements ProcessorInterface
{
    protected $configuration = [
        'id' => 'lcl',
        'name' => 'LCL - Compte courant particulier',
        'startConditions' => ['/ DATE\s+LIBELLE\s+/'],
        'endConditions' => ['/Page \d \/ \d/', '/LCL vous informe/'],
        'rowMergeColumnTokens' => [0],
        'rowSkipConditions' => ['ANCIEN SOLDE', 'TOTAUX', 'SOLDE EN EUROS', 'SOLDE INTERMEDIAIRE A'],
        'rowsToSkip' => [0],
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
            $dateRaw = $item[2];
            $date = new \DateTime();
            $date->setDate(2000 + (int) substr($dateRaw, 6, 2), (int) substr($dateRaw, 3, 2), (int) substr($dateRaw, 0, 2));
            $date->setTime(12, 0, 0);
            // Transaction
            $transaction = $this->frenchTransactionFormatter($item[3], $item[4]);

            return [
                'date'  => $date,
                'label' => $item[1],
                'value' => $transaction['value'],
                'debit' => $transaction['debit'],
            ];
        });

        return $data;
    }
}

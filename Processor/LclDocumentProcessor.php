<?php

namespace Kasifi\PdfParserBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;

class LclDocumentProcessor extends DocumentProcessor implements DocumentProcessorInterface
{
    public function getConfiguration()
    {
        return [
            'startConditions'      => ['/ DATE\s+LIBELLE\s+/'],
            'endConditions'        => ['/Page \d \/ \d/', '/LCL vous informe/'],
            'rowMergeColumnTokens' => [0],
            'rowSkipConditions'    => ['ANCIEN SOLDE', 'TOTAUX', 'SOLDE EN EUROS', 'SOLDE INTERMEDIAIRE A'],
            'rowsToSkip'           => [0],
        ];
    }

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
            $date->setDate(2000 + (int)substr($dateRaw, 6, 2), (int)substr($dateRaw, 3, 2), (int)substr($dateRaw, 0, 2));
            $date->setTime(12, 0, 0);
            // Value
            $debitRaw = $item[3];
            if (strlen($debitRaw)) {
                $value = abs((float)str_replace(',', '.', str_replace(' ', '', $debitRaw)));
                $debit = true;
            } else {
                $creditRaw = $item[4];
                $value = (float)str_replace(',', '.', str_replace(' ', '', $creditRaw));
                $debit = false;
            }

            return [
                'date'  => $date,
                'label' => $item[1],
                'value' => $value,
                'debit' => $debit,
            ];
        });

        return $data;
    }
}

<?php

namespace Kasifi\PdfParserBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class SgProDocumentProcessor
 * @package Kasifi\PdfParserBundle\Processor
 */
class SgProDocumentProcessor extends DocumentProcessor implements DocumentProcessorInterface
{
    /**
     * @return array
     */
    public function getConfiguration()
    {
        return [
            'startConditions'      => ['/Date\s+Valeur\s+Nature de l\'opération/'],
            'endConditions'        => [
                '/1 Depuis l\'étranger/', '/N° d\'adhérent JAZZ Pro/', '/Société Générale\s+552 120 222 RCS Paris/',
            ],
            'rowMergeColumnTokens' => [0],
            'rowSkipConditions'    => ['SOLDE PRÉCÉDENT AU', 'TOTAUX DES MOUVEMENTS', 'RA4-01K', 'NOUVEAU SOLDE AU'],
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
            $dateRaw = $item[0];
            $date = new \DateTime();
            $date->setDate((int)substr($dateRaw, 6, 4), (int)substr($dateRaw, 3, 2), (int)substr($dateRaw, 0, 2));
            $date->setTime(12, 0, 0);

            // Value Date
            $dateRaw = $item[1];
            $valueDate = new \DateTime();
            $valueDate->setDate((int)substr($dateRaw, 6, 4), (int)substr($dateRaw, 3, 2), (int)substr($dateRaw, 0, 2));
            $valueDate->setTime(12, 0, 0);

            // Value
            $debitRaw = $item[3];
            if (strlen($debitRaw)) {
                $value = abs((float)str_replace(',', '.', str_replace('.', '', $debitRaw)));
                $debit = true;
            } else {
                $creditRaw = $item[4];
                $value = (float)str_replace(',', '.', str_replace('.', '', $creditRaw));
                $debit = false;
            }

            return [
                'date'       => $date,
                'value_date' => $valueDate,
                'label'      => $item[2],
                'value'      => $value,
                'debit'      => $debit,
            ];
        });

        return $data;
    }
}

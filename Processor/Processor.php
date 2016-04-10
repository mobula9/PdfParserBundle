<?php

namespace Kasifi\PdfParserBundle\Processor;

/**
 * Class Processor.
 */
abstract class Processor
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function __toString()
    {
        return (string) $this->configuration['name'];
    }

    /**
     * @param string $debitRaw
     * @param string $creditRaw
     *
     * @return array
     */
    public function frenchTransactionFormatter($debitRaw, $creditRaw)
    {
        if (strlen($debitRaw)) {
            $value = abs((float) str_replace(',', '.', str_replace(' ', '', $debitRaw)));
            $debit = true;
        } else {
            $value = (float) str_replace(',', '.', str_replace(' ', '', $creditRaw));
            $debit = false;
        }
        return [
            'value' => $value,
            'debit' => $debit,
        ];
    }
}

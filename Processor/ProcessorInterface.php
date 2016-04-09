<?php

namespace Kasifi\PdfParserBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface ProcessorInterface
 * @package Kasifi\PdfParserBundle\Processor
 */
interface ProcessorInterface
{
    /**
     * @return array
     */
    public function getConfiguration();

    /**
     * @param ArrayCollection $data
     *
     * @return ArrayCollection
     */
    public function format(ArrayCollection $data);
}

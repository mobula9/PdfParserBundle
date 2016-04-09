<?php

namespace Kasifi\PdfParserBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface DocumentProcessorInterface
 * @package Kasifi\PdfParserBundle\Processor
 */
interface DocumentProcessorInterface
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

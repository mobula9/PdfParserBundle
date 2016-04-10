<?php

namespace Kasifi\PdfParserBundle;

use Kasifi\PdfParserBundle\DependencyInjection\ProcessorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class PdfParserBundle
 */
class PdfParserBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ProcessorCompilerPass());
    }
}

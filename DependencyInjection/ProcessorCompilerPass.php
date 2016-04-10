<?php

namespace Kasifi\PdfParserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('kasifi_pdfparser.pdf_parser')) {
            return;
        }

        $definition = $container->findDefinition(
            'kasifi_pdfparser.pdf_parser'
        );

        $taggedServices = $container->findTaggedServiceIds('kasifi_pdfparser.processor');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addAvailableProcessor',
                array(new Reference($id))
            );
        }
    }
}

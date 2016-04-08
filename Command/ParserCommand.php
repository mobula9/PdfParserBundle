<?php
namespace PdfParserBundle\Command;

use PdfParserBundle\PdfParser;
use PdfParserBundle\Processor\BfbDocumentProcessor;
use PdfParserBundle\Processor\LclDocumentProcessor;
use PdfParserBundle\Processor\SgProDocumentProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ParserCommand extends ContainerAwareCommand
{
    /** @var SymfonyStyle */
    private $io;

    /**
     * @var PdfParser
     */
    private $pdfParser;

    protected function configure()
    {
        $this
            ->setName('pdf-parser')
            ->setDescription('Parse document of many types.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'The kind of document (lcl, bfb, sg)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->pdfParser = $this->getContainer()->get('app.pdf_parser');

        // Get kind
        $kind = $input->getArgument('kind');
        if (!$kind) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Which kind of document?', ['lcl, bfb, sg']);
            $kind = $helper->ask($input, $output, $question);
        }

        $path = null;
        switch ($kind) {
            case 'lcl':
                $this->pdfParser->setProcessor(new LclDocumentProcessor());
                $path = __DIR__ . '/../fixtures/lcl.pdf';
                break;
            case 'sg':
                $this->pdfParser->setProcessor(new SgProDocumentProcessor());
                $path = __DIR__ . '/../fixtures/sg.pdf';
                break;
            case 'bfb':
                $this->pdfParser->setProcessor(new BfbDocumentProcessor());
                $path = __DIR__ . '/../fixtures/bfb.pdf';
                break;
        }

        // Parse
        $rows = $this->pdfParser->parse($path);

        // Dump
        dump($rows);
    }
}
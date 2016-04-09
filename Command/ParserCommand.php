<?php
namespace Kasifi\PdfParserBundle\Command;

use Kasifi\PdfParserBundle\PdfParser;
use Kasifi\PdfParserBundle\Processor\BfbDocumentProcessor;
use Kasifi\PdfParserBundle\Processor\LclDocumentProcessor;
use Kasifi\PdfParserBundle\Processor\SgProDocumentProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
            ->setName('pdf-parser:parse')
            ->setDescription('Parse document of many types.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'The kind of document (lcl, bfb, sg)')
            ->addArgument('filepath', InputArgument::OPTIONAL, 'The absolute path to the PDF file to parse.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->pdfParser = $this->getContainer()->get('app.pdf_parser');
        $kernel = $this->getContainer()->get('kernel');
        $fixturesDirectoryPath = realpath($kernel->getRootDir() . '/../data/fixtures/pdf');

        // Get kind
        $kind = $input->getArgument('kind');
        if (!$kind) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Which kind of document?', ['bfb', 'lcl', 'sg'], 0);
            $kind = $helper->ask($input, $output, $question);
        }

        // Select file
        $filePath = $input->getArgument('filepath');
        if (!$filePath) {
            $helper = $this->getHelper('question');
            $finder = new Finder();
            $finder->files()->in($fixturesDirectoryPath);
            $files = [];
            foreach ($finder as $key => $file) {
                /** @var SplFileInfo $file */
                $files[] = $file->getRealPath();
            }

            $question = new ChoiceQuestion('Which file? Enter the key.', $files, 0);
            $filePath = $helper->ask($input, $output, $question);
        }

        $path = null;
        switch ($kind) {
            case 'lcl':
                $this->pdfParser->setProcessor(new LclDocumentProcessor());
                break;
            case 'sg':
                $this->pdfParser->setProcessor(new SgProDocumentProcessor());
                break;
            case 'bfb':
                $this->pdfParser->setProcessor(new BfbDocumentProcessor());
                break;
        }

        // Parse
        $rows = $this->pdfParser->parse($filePath);

        // Dump
        dump($rows);
    }
}
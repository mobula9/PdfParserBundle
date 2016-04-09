<?php
namespace Kasifi\PdfParserBundle\Command;

use Kasifi\PdfParserBundle\Processor\BfbDocumentProcessor;
use Kasifi\PdfParserBundle\Processor\LclDocumentProcessor;
use Kasifi\PdfParserBundle\Processor\SgProDocumentProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ParserCommand
 * @package Kasifi\PdfParserBundle\Command
 */
class ParserCommand extends ContainerAwareCommand
{
    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName('pdf-parser:parse')
            ->setDescription('Parse document of many types.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'The kind of document (lcl, bfb, sg)')
            ->addArgument('filepath', InputArgument::OPTIONAL, 'The absolute path to the PDF file to parse.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdfParser = $this->getContainer()->get('kasifi_pdfparser.pdf_parser');
        $pdfDirectoryPath = $this->getContainer()->getParameter('kasifi_pdfparser.pdf_directory_path');

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
            $finder->files()->in($pdfDirectoryPath);
            $files = [];
            foreach ($finder as $key => $file) {
                /** @var SplFileInfo $file */
                $files[] = $file->getRealPath();
            }

            $question = new ChoiceQuestion('Which file? Enter the key.', $files, 0);
            $filePath = $helper->ask($input, $output, $question);
        }

        switch ($kind) {
            case 'lcl':
                $pdfParser->setProcessor(new LclDocumentProcessor());
                break;
            case 'sg':
                $pdfParser->setProcessor(new SgProDocumentProcessor());
                break;
            case 'bfb':
                $pdfParser->setProcessor(new BfbDocumentProcessor());
                break;
        }

        // Parse
        $rows = $pdfParser->parse($filePath);

        // Dump
        dump($rows);

        return;
    }
}

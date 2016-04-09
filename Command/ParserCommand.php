<?php
namespace Kasifi\PdfParserBundle\Command;

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
            ->addArgument('processor', InputArgument::OPTIONAL, 'The id of the processor')
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
        $container = $this->getContainer();
        $pdfParser = $container->get('kasifi_pdfparser.pdf_parser');
        $pdfDirectoryPath = $container->getParameter('kasifi_pdfparser.pdf_directory_path');

        // Select processor
        $processors = $pdfParser->getAvailableProcessors();
        $processorId = $input->getArgument('processor');
        if (!$processorId) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Which processor to use?', $processors);
            $processorId = $helper->ask($input, $output, $question);
        }
        $pdfParser->setProcessor($processors[$processorId]);

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

            $question = new ChoiceQuestion('Which file? Enter the key.', $files);
            $filePath = $helper->ask($input, $output, $question);
        }

        // Parse
        $rows = $pdfParser->parse($filePath);

        // Dump
        dump($rows);

        return;
    }
}

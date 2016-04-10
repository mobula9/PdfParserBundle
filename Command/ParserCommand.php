<?php
namespace Kasifi\PdfParserBundle\Command;

use Kasifi\PdfParserBundle\PdfParser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Dumper;

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
            ->addArgument('filepath', InputArgument::OPTIONAL, 'The absolute path to the PDF file to parse.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (console, json, yml)', 'console');
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

        // Select format
        $format = $input->getOption('format');

        // Parse
        $rows = $pdfParser->parse($filePath);

        $data = PdfParser::inlineDates($rows->toArray());

        // Write output
        switch ($format) {
            case 'json':
                $outputData = json_encode($data);
                $output->write($outputData);
                break;
            case 'yml':
                $dumper = new Dumper();
                $outputData = $dumper->dump($data, 1);
                $output->write($outputData);
                break;
            case 'console':
                if (count($data)) {
                    $table = new Table($output);
                    $table
                        ->setHeaders([array_keys($data[0])])
                        ->setRows($data);
                    $table->render();
                }
                break;
        }

        return;
    }
}

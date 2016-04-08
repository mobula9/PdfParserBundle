<?php

namespace PdfParserBundle;

use Doctrine\Common\Collections\ArrayCollection;
use PdfParserBundle\Processor\DocumentProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PdfParser
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var SymfonyStyle */
    private $io;

    /** @var DocumentProcessorInterface */
    private $processor;

    /** @var array */
    private $processorConfiguration;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function parse($filePath)
    {
        $this->processorConfiguration = $this->processor->getConfiguration();

        $rawData = $this->getTextVersion($filePath);

        $rows = $this->doParse($rawData);
        $rows = new ArrayCollection($rows);

        $formattedRows = $this->processor->format($rows);

        return $formattedRows;
    }

    /**
     * @return DocumentProcessorInterface
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param DocumentProcessorInterface $processor
     */
    public function setProcessor($processor)
    {
        $this->processor = $processor;
    }

    public function setIo(SymfonyStyle $io)
    {
        $this->io = $io;

        return $this;
    }

    private function doParse($data)
    {

        $blocks = [];

        while ($startPos = $this->findPosition($data, $this->processorConfiguration['startConditions'])) {
            // Find start
            if (is_null($startPos) && !count($blocks)) {
                throw new \Exception('Start condition never reached.');
            }
            $data = substr($data, $startPos);
            $data = substr($data, strpos($data, "\n"));

            // Find end

            $endPos = $this->findPosition($data, $this->processorConfiguration['endConditions']);
            if (is_null($endPos)) {
                throw new \Exception('End condition not reached at the ' . (count($blocks) + 1) . 'nth loop of block.');
            } else {
                $blockData = substr($data, 0, $endPos);
                $data = substr($data, $endPos);
            }
            $blockData = rtrim($blockData);

            $block = $this->parseBlock(
                $blockData,
                $this->processorConfiguration['rowsToSkip'],
                $this->processorConfiguration['rowMergeColumnTokens'],
                $this->processorConfiguration['rowSkipConditions']
            );

            $blocks[] = $block;
        }

        // MERGE BLOCKS
        $data = [];
        foreach ($blocks as $block) {
            $data = array_merge($data, $block);
        }

        return $data;
    }

    private function findPosition($data, $startConditions)
    {
        $firstResult = null;
        foreach ($startConditions as $startCondition) {
            preg_match($startCondition, $data, $matches);
            if (count($matches)) {
                $pos = strpos($data, $matches[0]);
                if (is_null($firstResult) || $pos < $firstResult) {
                    $firstResult = $pos;
                }
            }
            unset($matches);
        }

        return $firstResult;
    }

    private function parseBlock($blockData, $skipKeys, $rowMergeColumnTokens, $rowSkipConditions)
    {
        $rows = [];
        $rawRows = explode("\n", $blockData);
        $rawRows = $this->prepareRows($rawRows, $skipKeys, $rowSkipConditions);
        //dump(implode("\n", $rawRows));
        $previousIndex = 0;
        $colWidths = $this->guessWidth($rawRows);
        foreach ($rawRows as $key => $rawRow) {
            //dump($rawRow);
            $row = $this->parseRow($colWidths, $rawRow);
            $toMergeWithPrevious = false;
            if ($key > 0) {
                foreach ($rowMergeColumnTokens as $rowMergeColumnToken) {
                    if (!strlen($row[$rowMergeColumnToken])) {
                        $toMergeWithPrevious = true;
                    }
                }
            }

            if ($toMergeWithPrevious) {
                $rows[$previousIndex] = $this->mergeRows($rows[$previousIndex], $row);
            } else {
                $rows[] = $row;
                $previousIndex = count($rows) - 1;
            }
        }

        return $rows;
    }

    private function prepareRows($rawRows, $skipKeys, $rowSkipConditions)
    {
        $rows = [];
        $maxWidth = 0;
        foreach ($rawRows as $key => $rawRow) {
            // BYPASS EMPTY ROWS OR SPECIFIED ONES TO BE BYPASSED
            if (!strlen(trim($rawRow)) || in_array($key, $skipKeys)) {
                continue;
            }
            // SKIP ROWS TO SKIP
            foreach ($rowSkipConditions as $rowSkipCondition) {
                if (strpos($rawRow, $rowSkipCondition) !== false) {
                    continue 2;
                }
            }

            if (strlen($rawRow) > $maxWidth) {
                $maxWidth = strlen($rawRow);
            }
            $rows[] = $rawRow;
        }
        // SET SAME PADDING FOR EACH ROWS
        foreach ($rows as &$row) {
            $row = str_pad($row, $maxWidth, ' ');
        }
        unset($row);

        return $rows;
    }

    private function guessWidth($rawRows)
    {
        $spaceGroups = $this->findSpaceGroups($rawRows);

        $widths = [];
        $spaceEnd = 0;
        foreach ($spaceGroups as $spaceGroupKey => $spaceGroup) {
            $spaceStart = $spaceGroup['start'];
            $widths[] = ['start' => $spaceEnd, 'length' => $spaceStart - $spaceEnd];
            $spaceEnd = $spaceGroup['end'];
        }
        $widths[] = ['start' => $spaceEnd, 'length' => strlen($rawRows[0]) - $spaceEnd];

        return $widths;
    }

    private function parseRow($colWidths, $rawRow)
    {
        $colValues = [];
        foreach ($colWidths as $item) {
            $colValues[] = trim(substr($rawRow, $item['start'], $item['length']));
        }

        return $colValues;
    }

    private function getSpacePositions($rawRow)
    {
        $spacePositions = [];
        foreach (str_split($rawRow) as $key => $char) {
            if ($char == ' ') {
                $spacePositions[] = $key;
            }
        }

        return $spacePositions;
    }

    private function findSpaceGroups($rawRows)
    {
        $globalSpacePositions = [];
        foreach ($rawRows as $rawRow) {

            $spacePositions = $this->getSpacePositions($rawRow);

            if (count($globalSpacePositions)) {
                $globalSpacePositions = array_intersect($globalSpacePositions, $spacePositions);
            } else {
                $globalSpacePositions = $spacePositions;
            }
        }
        $globalSpacePositions = array_values($globalSpacePositions);

        $spaceGroups = [];
        $spaceGroupIndex = 0;
        foreach ($globalSpacePositions as $key => $spacePosition) {
            $nextPos = null;
            if ($key == 0) {
                $spaceGroups[$spaceGroupIndex] = ['start' => $spacePosition, 'end' => $spacePosition + 1];
            } else {
                $previousPos = $globalSpacePositions[$key - 1];
                $increase = $spacePosition - $previousPos;
                if ($increase == 1) {
                    $spaceGroups[$spaceGroupIndex]['end']++;
                } else {
                    $spaceGroupIndex++;
                    $spaceGroups[$spaceGroupIndex] = ['start' => $spacePosition, 'end' => $spacePosition + 1];
                }
//                dump([
//                    'key'         => $key,
//                    'value'       => $spacePosition,
//                    'increase'    => $increase,
//                    'group_index' => $spaceGroupIndex,
//                    'length'      => $spaceGroups[$groupIndex],
//                ]);
            }
        }

        // clean "false positive" space groups
        $spaceGroups = array_filter($spaceGroups, function ($spaceGroup) {
            return $spaceGroup['end'] - $spaceGroup['start'] > 1;
        });

        return $spaceGroups;
    }

    private function mergeRows($row, $newRow)
    {
        foreach ($newRow as $newRowColumnIndex => $newRowColumnValue) {
            if (strlen($newRowColumnValue)) {
                $row[$newRowColumnIndex] = trim($row[$newRowColumnIndex] . "\n" . $newRowColumnValue);
            }
        }

        return $row;
    }

    private function getTextVersion($filePath)
    {
        $tmpPath = sys_get_temp_dir() . '/' . rand(0, 10000) . '.txt';
        $process = new Process('/usr/bin/pdftotext -layout ' . $filePath . ' ' . $tmpPath);
        $this->logger->info('Execute Pdftotext', ['file' => $filePath]);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $content = file_get_contents($tmpPath);
        unlink($tmpPath);

        return $content;
    }
}
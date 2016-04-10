<?php

namespace Kasifi\PdfParserBundle;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Kasifi\PdfParserBundle\Processor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PdfParser
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var ProcessorInterface */
    private $processor;

    /** @var array */
    private $processorConfiguration;

    /** @var string */
    private $temporaryDirectoryPath;

    /** @var ProcessorInterface[] */
    private $availableProcessors = [];

    /**
     * PdfParser constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->temporaryDirectoryPath = sys_get_temp_dir();
    }

    /**
     * @param ProcessorInterface $processor
     */
    public function addAvailableProcessor(ProcessorInterface $processor)
    {
        $this->availableProcessors[$processor->getConfiguration()['id']] = $processor;
    }

    /**
     * @return ProcessorInterface[]
     */
    public function getAvailableProcessors()
    {
        return $this->availableProcessors;
    }

    /**
     * @param $filePath
     *
     * @return ArrayCollection
     *
     * @throws Exception
     */
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
     * @return ProcessorInterface
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param ProcessorInterface $processor
     */
    public function setProcessor(ProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param $data
     *
     * @return array|string
     *
     * @throws Exception
     */
    private function doParse($data)
    {
        $blocks = [];

        while ($startPos = $this->findPosition($data, $this->processorConfiguration['startConditions'])) {
            // Find start
            if (is_null($startPos) && !count($blocks)) {
                throw new Exception('Start condition never reached.');
            }
            $data = substr($data, $startPos);
            $data = substr($data, strpos($data, "\n"));

            // Find end

            $endPos = $this->findPosition($data, $this->processorConfiguration['endConditions']);
            if (is_null($endPos)) {
                throw new Exception('End condition not reached at the ' . (count($blocks) + 1) . 'nth loop of block.');
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

    /**
     * @param $data
     * @param $startConditions
     *
     * @return bool|int|null
     */
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

    /**
     * @param $blockData
     * @param $skipKeys
     * @param $rowMergeColumnTokens
     * @param $rowSkipConditions
     *
     * @return array
     */
    private function parseBlock($blockData, $skipKeys, $rowMergeColumnTokens, $rowSkipConditions)
    {
        $rows = [];
        $rawRows = explode("\n", $blockData);
        $rawRows = $this->prepareRows($rawRows, $skipKeys, $rowSkipConditions);
        $this->logger->debug(implode("\n", $rawRows));
        $previousIndex = 0;
        $colWidths = $this->guessWidth($rawRows);
        foreach ($rawRows as $key => $rawRow) {
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

    /**
     * @param $rawRows
     * @param $skipKeys
     * @param $rowSkipConditions
     *
     * @return array
     */
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

    /**
     * @param $rawRows
     *
     * @return array
     */
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

    /**
     * @param $colWidths
     * @param $rawRow
     *
     * @return array
     */
    private function parseRow($colWidths, $rawRow)
    {
        $colValues = [];
        foreach ($colWidths as $item) {
            $colValues[] = trim(substr($rawRow, $item['start'], $item['length']));
        }

        return $colValues;
    }

    /**
     * @param $rawRow
     *
     * @return array
     */
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

    /**
     * @param $rawRows
     *
     * @return array
     */
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
            if ($key == 0) {
                $spaceGroups[$spaceGroupIndex] = ['start' => $spacePosition, 'end' => $spacePosition + 1];
            } else {
                $previousPos = $globalSpacePositions[$key - 1];
                $increase = $spacePosition - $previousPos;
                if ($increase == 1) {
                    ++$spaceGroups[$spaceGroupIndex]['end'];
                } else {
                    ++$spaceGroupIndex;
                    $spaceGroups[$spaceGroupIndex] = ['start' => $spacePosition, 'end' => $spacePosition + 1];
                }
            }
        }

        // clean "false positive" space groups
        $spaceGroups = array_filter($spaceGroups, function ($spaceGroup) {
            return $spaceGroup['end'] - $spaceGroup['start'] > 1;
        });

        return $spaceGroups;
    }

    /**
     * @param $row
     * @param $newRow
     *
     * @return mixed
     */
    private function mergeRows($row, $newRow)
    {
        foreach ($newRow as $newRowColumnIndex => $newRowColumnValue) {
            if (strlen($newRowColumnValue)) {
                $row[$newRowColumnIndex] = trim($row[$newRowColumnIndex] . "\n" . $newRowColumnValue);
            }
        }

        return $row;
    }

    /**
     * @param $filePath
     *
     * @return string
     */
    private function getTextVersion($filePath)
    {
        $tmpPath = $this->temporaryDirectoryPath . '/' . rand(0, 10000) . '.txt';
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

    /**
     * @param $rows
     *
     * @return mixed
     */
    public static function inlineDates($rows)
    {
        foreach ($rows as &$row) {
            foreach ($row as &$col) {
                $col = $col instanceof \DateTime ? $col->format(\DateTime::ISO8601) : $col;
            }
        }

        return $rows;
    }
}

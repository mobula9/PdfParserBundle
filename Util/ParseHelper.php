<?php

namespace Kasifi\PdfParserBundle\Util;

class ParseHelper {
    /**
     * @param $data
     * @param $startConditions
     *
     * @return bool|int|null
     */
    public static function findPosition($data, $startConditions)
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
     * @param $rawRow
     *
     * @return array
     */
    public static function getSpacePositions($rawRow)
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
     * @param $row
     * @param $newRow
     *
     * @return mixed
     */
    public static function mergeRows($row, $newRow)
    {
        foreach ($newRow as $newRowColumnIndex => $newRowColumnValue) {
            if (strlen($newRowColumnValue)) {
                $row[$newRowColumnIndex] = trim($row[$newRowColumnIndex] . "\n" . $newRowColumnValue);
            }
        }

        return $row;
    }

    /**
     * @param $colWidths
     * @param $rawRow
     *
     * @return array
     */
    public static function parseRow($colWidths, $rawRow)
    {
        $colValues = [];
        foreach ($colWidths as $item) {
            $colValues[] = trim(substr($rawRow, $item['start'], $item['length']));
        }

        return $colValues;
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

    /**
     * @param $rawRows
     * @param $skipKeys
     * @param $rowSkipConditions
     *
     * @return array
     */
    public static function prepareRows($rawRows, $skipKeys, $rowSkipConditions)
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

}

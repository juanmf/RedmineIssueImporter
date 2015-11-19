<?php

namespace In\Parsers;

use In\Parsers\RecordDefinition\RecordDefinition;
use In\Parsers\RecordDefinition\VisitorRecord;

/**
 * Handles parsing of CSV files, following the record structure imposed by 
 * {@link SheetRecordParserAbstract}. Sheets that are of CSV format must use an
 * instance of this class to be traversed.
 * 
 * @author Juan Manuel Fernadnez <juanmf@gmail.com>
 */
class CsvSheetRecordParser extends SheetRecordParserAbstract
{
    protected 
        $sheet,
        $fieldParser,
        $key;

    /**
     * Initializes this RecordParser.
     * 
     * @param resource         $sheetFile             The path to the Sheet file in File System
     * @param RecordDefinition $sheetRecordDefinition Record Definition from importSchema.yml 
     * config file
     * @param string           $delimiter             The delimiter character used to 
     * separate fields in Sheet.
     * 
     * @return void
     */
    public function __construct(
        $sheetFile,  RecordDefinition $sheetRecordDefinition, $delimiter
    ) {
        parent::__construct($sheetFile, $sheetRecordDefinition, $delimiter);
        if (($handle = fopen($sheetFile, "r")) !== FALSE) {
            $index = 0;
            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $this->sheet[$index] = $data;
                $index++;
            }
        }
        $this->rewind();
    }
    
    /**
     * Return the current record.
     * 
     * @return array with the format specified by {@link SheetRecordParserAbstract}
     */
    public function current()
    {
        return $this->currentRecord;
    }

    /**
     * Return the key of the current record.
     * 
     * @return int The current Key. 
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Uses the increment values defined in importSchema.yml to move from each field
     * in the sheet to the next one.
     * 
     * @return void
     */
    public function next()
    {
        foreach ($this->currentRecord as $field) {
            /* @var $field \In\Parsers\RecordDefinition\VisitorField */
            $field->getCoord()->incrementCoord();
            $field->setCurrentValue($this->getElementAt(
                $field->getCoord()->x,
                $field->getCoord()->y
            ));
        }
        $this->key++;
    }

    /**
     * Sets th key and record to the initial state.
     * 
     * @return void
     */
    public function rewind()
    {
        $currentRecord = new VisitorRecord($this->sheetRecordDefinition);
        foreach ($currentRecord as $field) {
            /* @var $field RecordDefinition\VisitorField */
            $field->setCurrentValue(
                    $this->getElementAt(
                        $field->getCoord()->x,
                        $field->getCoord()->y
                    )
                );
        }
        $this->currentRecord = $currentRecord;
        $this->key = 0;
    }

    /**
     * Checks if current position is valid. If any field has a not null value,
     * this record is valid.
     *
     * @return boolean Wether this record is valid or not.
     */
    public function valid()
    {
        $anyValueSet = false;
        foreach ($this->currentRecord as $field) {
            if (! empty($field->getCurrentValue())) {
                $anyValueSet = true;
                break;
            }
        }
        return $anyValueSet;
    }

    /**
     * Gets an element from sheet at position ($x, $y). Zero Based.
     * 
     * @param int $x X Coordinates
     * @param int $y Y Coordinates
     * 
     * @return string With the value at cell ($x, $y)
     */
    protected function getElementAt($x, $y)
    {
        return isset ($this->sheet[$y]) && isset ($this->sheet[$y][$x])
            ? $this->sheet[$y][$x]
            : null;
    }
}

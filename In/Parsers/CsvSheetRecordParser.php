<?php

namespace Parsers;

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
        $_sheet,
        $_fieldParser,
        $_key;

    /**
     * Initializes this RecordParser.
     * 
     * @param resource $sheetFile             The path to the Sheet file in File System
     * @param array    $sheetRecordDefinition Record Definition from importSchema.yml 
     * config file
     * @param string   $delimiter             The delimiter character used to 
     * separate fields in Sheet.
     * 
     * @return void
     */
    public function __construct(
        $sheetFile, array $sheetRecordDefinition, $delimiter
    ) {
        parent::__construct($sheetFile, $sheetRecordDefinition, $delimiter);
        if (($handle = fopen($sheetFile, "r")) !== FALSE) {
            $index = 0;
            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $this->_sheet[$index] = $data;
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
        return $this->_currentRecord;
    }

    /**
     * Return the key of the current record.
     * 
     * @return int The current Key. 
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * Uses the increment values defined in importSchema.yml to move from each field
     * in the sheet to the next one.
     * 
     * @return void
     */
    public function next()
    {
        foreach ($this->_currentRecord as $index => $field) {
            $this->_currentRecord[$index]['x'] += $this->_currentRecord[$index]['x_inc'];
            $this->_currentRecord[$index]['y'] += $this->_currentRecord[$index]['y_inc'];
            $this->_currentRecord[$index]['value'] = $this->getElementAt(
                $this->_currentRecord[$index]['x'], 
                $this->_currentRecord[$index]['y']
            );
        }
        $this->_key++;
    }

    /**
     * Sets th key and record to the initial state.
     * 
     * @return void
     */
    public function rewind()
    {
        $index = 0;
        foreach ($this->_sheetRecordDefinition['fields'] as $fname => $field) {
            $_currentRecord[$index]['value'] = $this->getElementAt(
                $field['coord']['x'],
                $field['coord']['y']
            );
            $_currentRecord[$index]['x'] = $field['coord']['x'];
            $_currentRecord[$index]['y'] = $field['coord']['y'];
            $_currentRecord[$index]['x_inc'] = $field['increment']['x'];
            $_currentRecord[$index]['y_inc'] = $field['increment']['y'];
            $_currentRecord[$index]['name'] = $fname;
            $index++;
        }
        $this->_currentRecord = $_currentRecord;
        $this->_key = 0;
    }

    /**
     * Checks if current position is valid. If any field has a not null value,
     * this record is valid.
     *
     * @return boolean Wether this record is valid or not.
     */
    public function valid()
    {
        $allNulls = false;
        foreach ($this->_currentRecord as $index => $field) {
            if (! empty($field['value'])) {
                $allNulls = true;
                break;
            }
        }
        return $allNulls;
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
        return isset ($this->_sheet[$y]) && isset ($this->_sheet[$y][$x])
            ? $this->_sheet[$y][$x]
            : null;
    }
}

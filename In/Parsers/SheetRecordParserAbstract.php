<?php

namespace In\Parsers;

use \In\Parsers\RecordDefinition\RecordDefinition;

/**
 * Represents a common interface for <FileType>SheetRecordParser every descendant
 * of this class must traverse the sheet and generate a unified record with the
 * following structure:
 * For the outside world:
 *  $currentRecord[$fieldIndex]['value'] = $concrteteParser->getelementAt($x,$y);
 *  $currentRecord[$fieldIndex]['name'] = key($sheetRecDefinition['fields']);
 * And to make life easier in iteratoins:
 *  $currentRecord[$fieldIndex]['x'] = $sheetRecDefinition['coord']['x'];
 *  $currentRecord[$fieldIndex]['y'] = $sheetRecDefinition['coord']['y'];
 *  $currentRecord[$fieldIndex]['x_inc'] = $sheetRecDefinition['increment']['x'];
 *  $currentRecord[$fieldIndex]['y_inc'] = $sheetRecDefinition['increment']['y'];
 *
 * @author Juan Manuel Fernadnez <juanmf@gmail.com>
 */
abstract class SheetRecordParserAbstract implements \Iterator
{
    protected $_sheetFile;
    protected $_delimiter;
    
    /**
     * @var \In\Parsers\RecordDefinition\RecordDefinition
     */
    protected $sheetRecordDefinition;
    
    /**
     * @var \In\Parsers\RecordDefinition\VisitorRecord
     */
    protected $currentRecord;
            
    /**
     * @var SheetIterator This delegate knows the methos it should call on 
     * next() and rewind()
     */
    protected $_sheetIterator;

    protected $_handlesMultipleSheets = false;
        
    /**
     * Creates a Sheet Iterator.
     * 
     * @return void.
     */
    protected function createSheetIterator()
    {
        $this->_sheetIterator = SheetIterator::getInstance($this);
    }

    /**
     * Returns $_handlesMultipleSheets
     * 
     * @return bool Wether this *SheetRecordParser supports multiple sheets.
     */
    public function handlesMultipleSheets()
    {
        return $this->_handlesMultipleSheets;
    }

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
        $sheetFile, RecordDefinition $sheetRecordDefinition, $delimiter = null
    ) {
        $this->_sheetFile = $sheetFile;
        $this->_delimiter = $delimiter;
        $this->sheetRecordDefinition = $sheetRecordDefinition;
    }

    /**
     * Gets an element from sheet at position ($x, $y). Zero Based.
     * 
     * @param int $x X Coordinates
     * @param int $y Y Coordinates
     * 
     * @return string With the value at cell ($x, $y)
     */
    protected abstract function getelementAt($x, $y);

    /**
     * Accessor for the Sheet Record Definition.
     * 
     * @return array With the Sheet Record Definition.
     */
    public function getSheetRecordDefinition()
    {
        return $this->sheetRecordDefinition;
    }
    
    /**
     * On sameSchema cases sheetParser has many sheets, this method should set 
     * the right one active. for parsing required sheets (tables) as dependent 
     * tables fail to load.
     * 
     * @param string $foreignColumn The foreign key column name with an unment 
     * value, that caused the need for loading a required table 1st.
     * 
     * @return void
     */
    public function setCurrentSheetForForeignCol($foreignColumn)
    {
        throw new \MethodNotImplementedException(__METHOD__ . ' Not implemented');
    }

    /**
     * On sameSchema cases sheetParser has many sheets, this method should set 
     * the previous selected sheet active, to continue loading
     * 
     * @return void
     */
    public function popCurrentSheetStack()
    {
        throw new \MethodNotImplementedException(__METHOD__ . ' Not implemented');
    }

    /**
     * On sameSchema cases sheets map to entities, and there could be many 
     * importing at the same time. When unmet precedences are found, excetution 
     * jumps to the required sheet to load it before continuing with the former.
     * For error checking, knowing the currentSheet/currentEntity being loaded 
     * might be necesary.
     * 
     * @return void
     */
    public function getCurrentEntity()
    {
        // TODO: if possible, implement this returning null.
        throw new \Exception(__METHOD__ . ' Not implemented');
    }

    /**
     * Used to iterate through sheets in a sheet Collection, for parsers that can 
     * handle more than one sheet.
     * 
     * @see MultiEntityPopulator
     * @see SameSchemaSheetRecordParser
     * @return Iterator Any foreach-able object|array of sheets.
     */
    public function iterateSheets()
    {
        throw new \Exception(__METHOD__ . ' Not implemented');
    }
    
    /**
     * Sets current sheet to the next one in sheet collection, for parsers that can 
     * handle more than one sheet.
     * 
     * @return void
     */
    public function setNextSheet()
    {
        throw new \Exception(__METHOD__ . ' Not implemented');
    }

    /**
     * Sets current sheet to the next one in sheet collection, for parsers that can 
     * handle more than one sheet.
     * 
     * @return void
     */
    public function setFirstSheet()
    {
        throw new \Exception(__METHOD__ . ' Not implemented');
    }
    
    /**
     * Determines if current Sheet is usable, for parsers that can 
     * handle more than one sheet.
     * 
     * @return void
     */
    public function isValidSheet()
    {
        throw new \Exception(__METHOD__ . ' Not implemented');
    }
    
    /**
     * Returns an object of the class responsible for parsing the file type
     * declared by the user.
     *
     * @param array             $sheetFileSpecs The submitted File temp data.
     * @param string            $fileType       The file type we have to parse.
     * @param RecordDefinition  $recordDef      Record Definition from importSchema.yml 
     * config file
     * @param string            $delimiter      The delimiter character used to 
     * separate fields in Sheet.
     * 
     * @return SheetRecordParserAbstract
     */
    final public static function getInstance(
        array $sheetFileSpecs, $fileType, RecordDefinition $recordDef = null, $delimiter = '|'
    ) {
        $parserClass = 'In\\Parsers\\';
        $parserClass .= $fileType . 'SheetRecordParser';
        $sheetPath = $sheetFileSpecs['tmp_name'];
        if (! class_exists($parserClass)) {
            throw new \Exception(sprintf('Class %s not found', $parserClass));
        }
        return new $parserClass($sheetPath, $recordDef, $delimiter);
    }
}

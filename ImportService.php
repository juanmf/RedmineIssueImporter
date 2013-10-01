<?php

require_once ('autoload.php');

use \Parsers\SheetRecordParserAbstract,
    \Config\Config, 
    \EntityPopulator\EntityPopulator;

class ImportService
{
    const DEFAULT_DATA_FILE = '/tmp/datos.csv';
    const CONFIG_SHEET = 'demandas';
    const RECORDTYPE = 'demanda';
    const FILETYPE = 'Csv';
    const DELIMITER = ';';

    private static $instance = null;
    
    /**
     * Singleton implementation
     * 
     * @param string $fileName The absolute pat to data csv file
     * 
     * @return ImportService an instance of self
     */
    public static function getInstance($fileName = self::DEFAULT_DATA_FILE)
    {
        if (null === self::$instance) {
            self::$instance = new self($fileName);
        }
        return self::$instance;
    }
    
    /**
     * @var SheetRecordParserAbstract
     */
    private $parser;
    
    /**
     * @var \Redmine\Client
     */
    private $client;
    
    /**
     * Array version of {@see Config.yml}
     * 
     * @var array
     */
    private $sheetDefinition;
            
    protected function __construct($fileName = self::DEFAULT_DATA_FILE)
    {
        $redmineAccount = Config::get('redmine_account');
        $host = $redmineAccount['host'];
        $key = $redmineAccount['api_key'];
        $this->client = new Redmine\Client($host, $key);
        
        $impData = array (
            'sheet'     => self::CONFIG_SHEET,
            'delimiter' => self::DELIMITER,
            'file_type' => self::FILETYPE,
            'record'    => self::RECORDTYPE,
        );
        $file = array('tmp_name' => $fileName);
        
        $this->parser = $this->configureImport($impData, $file);
    }
    
    /**
     * Handles Creation of SheetRecordParserAbstract
     * 
     * @param array $impData Import config data as given by the user. 
     * {@see SelectSheetForm}
     * @param array $file    The File, with the sheet data. $fileName['tmp_name']
     * 
     * @return array Numeric indexed, with a an instance of 
     * SheetRecordParserAbstract in index 0 and an instance of sfImportSheetRecordForm
     * in index 1. array(SheetRecordParserAbstract, sfImportSheetRecordForm)
     */
    private function configureImport($impData, $file)
    {
        $sheets = Config::get('sheets');
        $this->sheetDefinition = $sheets;

        $record = $sheets[$impData['sheet']]['records'][$impData['record']];
        $recordParser = SheetRecordParserAbstract::getInstance(
            $file, $impData['file_type'], $record, $impData['delimiter']
        );
        return $recordParser;
    }
    
    /**
     * uses EntityPopulator to parse csv sheet and create redmine API entities (issue, user, etc)
     * and persist them through the API.
     * 
     * @return void 
     */
    public function createTickets()
    {
        EntityPopulator::populateEntities($this->parser);
    }
    
    // <editor-fold defaultstate="collapsed" desc="Accessors&Mutators">
    public function getParser() {
        return $this->parser;
    }

    public function setParser(SheetRecordParserAbstract $parser) {
        $this->parser = $parser;
    }

    public function getClient() {
        return $this->client;
    }

    public function setClient(\Redmine\Client $client) {
        $this->client = $client;
    }
    // </editor-fold>
}

$import = ImportService::getInstance('/tmp/test-backlogs.csv');
$import->createTickets();
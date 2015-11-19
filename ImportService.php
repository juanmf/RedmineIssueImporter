<?php

use \In\Parsers\SheetRecordParserAbstract;
use \Config\Config;
use \Transform\EntityPopulator\EntityPopulator;
use \In\Parsers\RecordDefinition\RecordDefinition;
use Transform\EntityPopulator\Helper\EntityPersistenceHelper;

/**
 * Manages the Client instantiation and runs the import process.
 * 
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class ImportService
{
    /**
     * The default absolute file path to the file containing the sheet to be imported
     */
    const DEFAULT_DATA_FILE = '/tmp/datos.csv';
    
    /**
     * The default sheet name config to be used to interpret data in input sheet file.
     * {@see Config/config.yml}:
     * <pre>
     * sheets: 
     *   demandas: #sheetName
     */
    const CONFIG_SHEET = 'demandas';
    
    /**
     * The default record name config to be used to interpret data in input sheet file.
     * {@see Config/config.yml}:
     * <pre>
     * sheets: 
     *   demandas: #sheetName
     *     records:
     *       demanda: #record name
     */
    const RECORDTYPE = 'demanda';
    
    /**
     * The default file Format to be used, its case sensitive as the parser class name
     * beggins with this string and ends with 'SheetRecordParser' i.e. 'CsvSheetRecordParser'.
     */
    const FILETYPE = 'Csv';
    
    /**
     * The default sheet field delimiter, for CSV.
     */
    const DELIMITER = ';';

    /**
     * file where we stroe a serialized array with just created Ids. in case we want to 
     * delete them, change something in config and try again.
     */
    const LAST_CREATED_ISSUES_IDS_FNAME = '/lastIssuesId.serialized';
    
    /**
     * File where er write out a print_r of the entities that had errors and the error message.
     */
    const ERROR_FILE_NAME = "/errors.dump";
    
    /**
     * The singleton
     * 
     * @var self
     */
    private static $instance = null;
    
    /**
     * @var SheetRecordParserAbstract
     */
    private $parser;
    
    /**
     * @var \Redmine\Client
     */
    private $client;
    
    /**
     * Import process Config parameters follows
     * @var string
     */
    private $dataFileName;
    private $sheet;
    private $delimiter;
    private $file_type;
    private $record;
    
    /**
     * Used to retrieve the rigth set of createdIds, for issue deletion
     * @var string
     */
    private $currentProject;
    
    /**
     * Singleton implementation
     * 
     * @param string $fileName The absolute pat to data csv file
     * 
     * @return ImportService an instance of self
     */
    public static function getInstance(
        $fileName = self::DEFAULT_DATA_FILE, $sheet = self::CONFIG_SHEET, 
        $delimiter = null, $fileType = self::FILETYPE, $record = self::RECORDTYPE
    ) {
        if (null === self::$instance) {
            self::$instance = new self($fileName, $sheet, $delimiter, $fileType, $record);
        }
        return self::$instance;
    }
    
    /**
     * Initializes API client and configuration from {@link Config/config.yml}.
     * 
     * @param string $fileName  {@see self::DEFAULT_DATA_FILE}
     * @param string $sheet     {@see self::CONFIG_SHEET}
     * @param string $delimiter {@see self::DELIMITER}
     * @param string $fileType  {@see self::FILETYPE}
     * @param string $record    {@see self::RECORDTYPE}
     * 
     * @return void 
     */
    protected function __construct(
        $fileName = self::DEFAULT_DATA_FILE, $sheet = self::CONFIG_SHEET, 
        $delimiter = null, $fileType = self::FILETYPE, $record = self::RECORDTYPE
    ) {
        /**
         * TODO: MANDAR ESTO AL OUT.
         */
        $redmineAccount = Config::get('redmine_account');
        $host = $redmineAccount['host'];
        $key = $redmineAccount['api_key'];
        $this->client = new Redmine\Client($host, $key);

        $this->dataFileName = $fileName;
        $this->sheet = $sheet;
        $this->delimiter = $delimiter;
        $this->file_type = $fileType;
        $this->record = $record;
    }
    
    /**
     * Initialize the sheet parser.
     * 
     * @return void 
     */
    public function initImporter()
    {
        $input_format = Config::get('input_format');
        $impData = array (
            'sheet'     => $this->sheet,
            'delimiter' => $this->delimiter ? : $input_format['delimiter'],
            'file_type' => $this->file_type,
            'record'    => $this->record,
        );
        $file = array('tmp_name' => $this->dataFileName);
        $this->setCurrentProject();
        
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
        $recordDef = $sheets[$impData['sheet']]['records'][$impData['record']];
        $persistenceEngine = $sheets[$impData['sheet']]['persistence_engine'];
        $record = RecordDefinition::getInstance($recordDef, $persistenceEngine);
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
    public function executeCreate()
    {
        $this->initImporter();
        Transform\Transformers\Transformer::unSerializeMappings();
        EntityPopulator::populateEntities($this->parser);
        $this->serializeLastCreatedIds();
        $this->checkForErrors();
        Transform\Transformers\Transformer::serializeMappings();
    }
    
    /**
     * uses EntityPopulator to parse csv sheet and create redmine API entities (issue, user, etc)
     * and persist them through the API.
     * 
     * @return void 
     */
    public function executeUpdate()
    {
        $this->initImporter();
        Transform\Transformers\Transformer::unSerializeMappings();
        EntityPopulator::populateEntities($this->parser);
        $this->serializeLastCreatedIds();
        $this->checkForErrors();
        Transform\Transformers\Transformer::serializeMappings();
    }
    
    /**
     * Checks if the entityPopulator registered any errors, if so dumps them in
     * a file called __DIR__ . {@link self::ERROR_FILE_NAME}
     * 
     * @return void 
     */
    private function checkForErrors()
    {
        // TODO: MAKE THIS WORK WITH ANY PERSISTENCE ENGINE
        $erroeFileName = __DIR__ . self::ERROR_FILE_NAME;
        $conflicts = EntityPersistenceHelper::getConflicts();
        if (0 < $count = count($conflicts['Transform\EntityPopulator\Entities\Issue'])) {
            file_put_contents($erroeFileName, print_r(($conflicts), true));
            echo sprintf('check For errors in %s, %s found' . PHP_EOL, $erroeFileName, $count);
        }
    }
    
    /**
     * Throws an exception if expcted condition doesn't apply.
     * 
     * @param type $value
     * @param type $expected
     * 
     * @throws Excetion
     */
    protected function assertExpected($value, $expected, $message)
    {
        $throw = false;
        switch (true) {
            case is_object($expected):
                if (null !== $value && ! ($value instanceof $expected)) {
                    $throw = true;
                }
                break;
            case is_array($expected):
                if (null !== $value && ! is_array($value)) {
                    $throw = true;
                }
                break;
            case is_scalar($expected):
                if (null !== $value && ! is_scalar($value)) {
                    $throw = true;
                }
                break;
        }
        if ($throw) {
            throw new Exception($message);
        }
    }
    
    /**
     * Deletes all issues in the given project.
     * 
     * @param string $projectIdentifier The Redmine project identifier
     * 
     * @return void 
     */
    public function deleteIssuesInProject($projectIdentifier) 
    {
        $issueApi = $this->client->api('issue');
        /* @var $issueApi \Redmine\Api\Issue */
        while (true) {
            $issues = $issueApi->all(array('project_id' => $projectIdentifier, 'limit' => 100, 'status_id' => '*'));
            $this->assertExpected($issues, array(), sprintf('I Expected an Array, "%s" given.', print_r($issues, true)));
            if (0 === $issues['total_count']) {
                break;
            }
            $this->deleteIssueList($issues, $issueApi);
        }
    }
    
    /**
     * Deletes all issues in the given project.
     * 
     * @param string $projectIdentifier The Redmine project identifier
     * 
     * @return void 
     */
    public function deleteLastRunCreatedIssues($progectidentifier)
    {
        $issues = $this->unSerializeLastCreatedIds();
        $this->assertExpected(
            $issues[$progectidentifier], array(), 
            sprintf('I Expected an Array, "%s" given.', print_r($issues, true))
        );
        $this->deleteIssueList($issues[$progectidentifier]);
    }
    
    /**
     * iterates over a list of issues and deletes them using their Ids.
     * 
     * @param array              $issues   The Issue List
     * @param \Redmine\Api\Issue $issueApi The Issue Api object, optional.
     * 
     * @return void 
     */
    private function deleteIssueList(array $issues, \Redmine\Api\Issue $issueApi = null)
    {
        $issueApi = $issueApi ? : $this->client->api('issue');
        /* @var $issueApi \Redmine\Api\Issue */
        foreach ($issues['issues'] as $issue) {
            $issueApi->remove($issue['id']);
        }
    }
    
    /**
     * Tries to find in __DIR__ a file with name lastIssuesId.serialized which should 
     * contain an array of generated Issues Id of a previous run.
     * 
     * @return array The list of Issues Id, created in last Run.
     */
    protected function unSerializeLastCreatedIds()
    {
        $savedIds = __DIR__ . self::LAST_CREATED_ISSUES_IDS_FNAME;
        if (! file_exists($savedIds)) {
            return;
        }
        $serialized = file_get_contents($savedIds);
        return unserialize($serialized);
    }
    
    /**
     * Serializes this run created Ids in __DIR__  with name lastIssuesId.serialized
     * 
     * @return void
     */
    protected function serializeLastCreatedIds() 
    {
        $ids = \Transform\EntityPopulator\Entities\Issue::$createdIds;
        $fName = __DIR__ . self::LAST_CREATED_ISSUES_IDS_FNAME;
        file_put_contents($fName, serialize($ids));
    }
    
    // <editor-fold defaultstate="collapsed" desc="Accessors&Mutators">
    public function getParser()
    {
        return $this->parser;
    }
    

    public function setParser(SheetRecordParserAbstract $parser)
    {
        $this->parser = $parser;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setClient(\Redmine\Client $client)
    {
        $this->client = $client;
    }

    public function getCurrentProject() 
    {
        return $this->currentProject;
    }

    public function setCurrentProject()
    {
        $sheets = Config::get('sheets');
        $this->currentProject = $sheets[$this->sheet]['records'][$this->record]['project_id'];
        ini_set('xdebug.var_display_max_data', '99999');
    }

    // </editor-fold>
}

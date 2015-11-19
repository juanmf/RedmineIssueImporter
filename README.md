RedmineIssueImporter
====================

Please download and use release 1.0.0 currently master version is not tested/guaranteed 
to work.
Use the Command Line and a config yml File to import CVS Sheets as Issues in Redmine. *With Custom Fields support*. And easily mapping your CSV different/corrupt fields values to the values accepted by Redmine (lists, boolean, date, etc..) fields 


The Import process:
-------------------
 1) Identify the fields in the CVS sheet that match fields in Redmine Issues.
 
 2) Add connection info to the config file following the example. 
 
 3) Add Custom fields info into the config file, name and id, to allow importer to work properly.
 
 4) add mapping info, relating fields in sheet with Redmine fields.
 
 5) Fill Issue default values to use for the issues when the sheet field comes empty or when there is 
no such field in sheet but redmine forces you to use it.

 6) Run the application from console.
 
 7) Optionally delete created Issues if mappings should be fixed.
 

The Update Process:
-------------------
1 & 2 & 3 & 4 from above Import Process

5) As oposite to Import, only use default values for fields present in issues sheet. Otherwise you'll override current values in existing issues with their defaults.

6) Run Application from Comman Line. e.g. 

```
$ php importSheet.php update /tmp/updateDemandas.csv --sheet=updatedemandas --record=demanda
```

In this example I just used issues Id from /tmp/updateDemandas.csv and set a default value for a new Field (field added after Import). @see Config/Consig.yml

You have three commands:
```
import --sheet="..." --record="..." [--delimiter="..."] [--fileType="..."] dataFile
update --sheet="..." --record="..." [--delimiter="..."] [--fileType="..."] dataFile
delete [--all] --project="..."

$ php importSheet.php list
..
Available commands:
  delete   Deletes all issues (only if --all) in a project or issued created in last import command run. Does nothing if cant find serielized created issues ids file
  import   Imports issues in a Redmine project, from a data sheet
  update   update issues in a Redmine project, from a data sheet, given that Config/Config.yml denotes the "id" field in sheet

$ php importSheet.php help import
Usage:
 import [--sheet="..."] [--record="..."] [--delimiter="..."] [--fileType="..."] dataFile

Arguments:
 dataFile              absolute file path to the data sheet

Options:
 --sheet               The sheet name config to be used to interpret data in input sheet file.
 --record              The default record name config to be used to interpret data in input sheet file.
 --delimiter           The sheet field delimiter, for CSV.
 --fileType            The default file Format to be used, its case sensitive as the parser class name beggins with this string and ends with 'SheetRecordParser' i.e. 'CsvSheetRecordParser'. (default: "Csv")

$ php importSheet.php help update
Usage:
 update [--sheet="..."] [--record="..."] [--delimiter="..."] [--fileType="..."] dataFile

Arguments:
 dataFile              absolute file path to the data sheet

Options:
 --sheet               The sheet name config to be used to interpret data in input sheet file.
 --record              The default record name config to be used to interpret data in input sheet file.
 --delimiter           The sheet field delimiter, for CSV.
 --fileType            The default file Format to be used, its case sensitive as the parser class name beggins with this string and ends with 'SheetRecordParser' i.e. 'CsvSheetRecordParser'. (default: "Csv")

$ php importSheet.php help delete
Usage:
 delete [--all] [--project="..."]

Options:
 --all                 if Specified, deletes all issues in a project. WARNING! all issues, not just the imported ones.
 --project             This must be set either if --all is set or just last Run Ids need to be deleted. Its the project identifier, the one that appears in the URL. i.e.  <RedmineDomain>/projects/<projectIdentifier>/issues?...
```

The Config File
---------------

Use the following config file format to import sheets into redmine. So far it creates Issues, 
with custom fields, but it's extensible to add users, etc..

You also have [this simpler example](https://gist.github.com/juanmf/8f75706af3ec7ed74918) Config file I made for a budy in need.

```yaml
################################################################################
# Run customization
################################################################################
redmine_account:
  # http://www.redmine.org/projects/redmine/wiki/Rest_api#Authentication # show API Key.
  api_key: 'fc..8' # siup
  host: 'http://redmine.myserver.com.ar/'

input_format:
  file_type: 'Csv' # maps to CsvSheetRecordParser
  delimiter: '#'   # field delimiter
  
# false Makes everithing but call save on entities, for tests before hitting API
save_records: true 
################################################################################
# Sheets mapping configuration
################################################################################
on_error:
  behavior: continue # [continue, stop, rollback]
  display_exceptions: false # [true | false]
  
# redmine custom fields settings <objectType> => <customFieldName> => id => <idValue>
custom_fields: 
  issue: 
    sprint: 
      id: 1 # for issues, Intermediario field
    Localidad: 
      id: 19 # for issues, Localidad field
    "Fecha Comprometido": 
      id: 21 # for issues, Fecha Comprometido field
    Telefono: 
      id: 20 # for issues, Telefono field
    Beneficiario: 
      id: 18 # for issues, Beneficiario field
    Intermediario: 
      id: 17 # for issues, Intermediario field

# all sheets that can be importes should be mapped here
sheets: 
  #1st sheet definition
  demandas:  
    # sheet Name, not used unless you add a web form that might use this name.
    name: Planilla de Relevamiento de Demandas Area de Nuevos Medios
    # each sheet could host several record types, here's each definition
    records:
      # recordName use as index to select this config in [Run customization]
      demanda:
        # record Label, not used.
        name: Demanda
        # Redmine Objects/Entities that are related to sheet's data.
        entities:
          # Issue object
          Issue:
            # previously relatd to Doctrien 1.X adapted to Redmine issue types.
            # Not used schema_entity: ~ # si entities[entName] no coincide con el esquema setear este valor
            # object deefault values (in this case Issue) can be [callbackClass, Callbackmethod]
            # these default need not to be in the sheet, but might be mandatory for the API.
            # you could also use defaults for cusotm fields here, just with the name.
            defaults:
              project: A Nuevos Medios, Seguimiento de Demandas
              status: Nueva
              priority: Normal
              assigned_to_id: 92
              author: 'juan'
              due_date: ~
              start_date: [Transform\Defaults\Defaults, startDate]
              tracker: Demanda
              "Fecha Comprometido": '2013-11-01'
              Beneficiario: N/N
              subject: 'Sin Asunto'
              Intermediario: '-----------------'
        
        # These are the fields expected to be present in the CSV or any other sheet like input
        # next definition matched a CSV as follows (showing two records): 
        # [Subject;Description;Sprint] the rest of mandatory values are defined above for "Issue"
        # subject1;Description1;8
        # subject2;Description2;8
        fields:
          # field name
          beneficiario:
            # this key relates this field with Redmine's model described above
            model: {entity: 'Issue', column: 'Beneficiario'}
            # This are the coordinates, where the parser tries to find the 1st occurrence of the field. 
            # Zero based. from the upper-left corner
            coord: {x: 1, y: 0}
            # default value for this field. ovverides other defaults possibly defined above. 
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            # necesary moves to reach next instance of this field, e.i. next record. Normally it'll be just 
            # one step down. (Y+1, X+0). But for values in headers, that appears only once, might be 
            # (Y+0, X+0) so, the same value is used for every record.
            increment: {x: 0, y: 1}  
            # callback that might be needed to transform input data before being persisted.
            transform: ~ # a callback method
            
          subject:
            model: {entity: 'Issue', column: 'subject'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 3, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: [Transformers\Transformer, asunto] # a callback method
          
          intermediario:
            model: {entity: 'Issue', column: 'Intermediario'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 2, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: [Transformers\Transformer, intermediario] # a callback method
           
          description:
            model: {entity: 'Issue', column: 'description', glue: '| '} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 3, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: ~ # a callback method
           
          localidad:
            model: { entity: 'Issue', column: 'Localidad'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 0, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: [Transformers\Transformer, localidad] # a callback method
           
          estado:
            model: { entity: 'Issue', column: 'status'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 4, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: [Transformers\Transformer, estado] # a callback method
          
          observaciones:
            model: { entity: 'Issue', column: 'description', glue: '| '} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 5, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: ~ # a callback method
          
          fecha_inicio:
            model: { entity: 'Issue', column: 'start_date'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 6, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: [Transformers\Transformer, fecha] # a callback method
           
          localidad_en_descripcion:
            model: { entity: 'Issue', column: 'description', glue: '| '} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 0, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: ~ # a callback method
  #2st sheet definition
  iprodich:  
    # sheet Name, not used unless you add a web form that might use this name.
    name: iprodich
    # each sheet could host several record types, here's each definition
    records:
      # recordName use as index to select this config in [Run customization]
      iprodich:
        # record Label, not used.
        name: iprodich
        # Redmine Objects/Entities that are related to sheet's data.
        entities:
          # Issue object
          Issue:
            # previously relatd to Doctrien 1.X adapted to Redmine issue types.
            # Not used schema_entity: ~ # si entities[entName] no coincide con el esquema setear este valor
            # object deefault values (in this case Issue) can be [callbackClass, Callbackmethod]
            # these default need not to be in the sheet, but might be mandatory for the API.
            # you could also use defaults for cusotm fields here, just with the name.
            defaults:
              project: Gestión I.Pro.Di.Ch
              status: Nueva
              priority: Normal
              assigned_to: juanmf
              author: 'juan'
              due_date: ~
              start_date: [Transform\Defaults\Defaults, startDate]
              tracker: Demanda
              "Fecha Comprometido": '2013-11-01'
              subject: 'Sin Asunto'
              sprint: 8
              
        # These are the fields expected to be present in the CSV or any other sheet like input
        # next definition matched a CSV as follows (showing two records): 
        # [Subject] the rest of mandatory values are defined above for "Issue"
        # subject1
        fields:
          # field name
          subject:
            model: {entity: 'Issue', column: 'subject'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 0, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: [Transformers\Transformer, asunto] # a callback method
  #3rd sheet definition for updates
  updatedemandas:  
    # sheet Name, not used unless you add a web form that might use this name.
    name: Las demandas que se exportaron por algo (enviar a evelyn) y se desea actualizar en batch
    # each sheet could host several record types, here's each definition
    records:
      # recordName use as index to select this config in [Run customization]
      demanda:
        # record Label, not used.
        name: Demanda
        project_id: demandas
        entities:
          # Issue object
          Issue:
            defaults:
              project: A Nuevos Medios, Seguimiento de Demandas
              # just adding a boolean true for boolean Field "Derivado"
              Derivado: 1
        
        # These are the fields expected to be present in the CSV or any other sheet like input
        # next definition matched a CSV as follows (showing two records): 
        # [id] the rest of mandatory values are defined above for "Issue"
        # id1
        # id2
        fields:
          # field name
          id:
            # this key relates this field with Redmine's model described above
            model: {entity: 'Issue', column: 'id'}
            # This are the coordinates, where the parser tries to find the 1st occurrence of the id ield. 
            # Zero based. from the upper-left corner I use y:1 as the y:0 is used by Redmine field name headings
            # because I used a CSV resulting from a Redmine Export for the update.
            coord: {x: 0, y: 1}
            default: ~
            increment: {x: 0, y: 1}
            transform: ~ # a callback method    
```

config Process & config File filling
------------------------------------

You complete each part of this config file in each step of the definition process explained above as follows
1) Identify the fields in the CVS sheet that match fields in Redmine Issues.
  Let say we have the following csv record and its the 1st record of the file: 
  ```
  The kid is not my child;Father claims he's not the father;2013-10-05;Waiting for Revision;John Doe
  ^ Subject               ^ description                     ^ stdate   ^ status             ^ relative
  ^ coord: {x:0, y:0}     ^ coord: {x:1, y:0}               ^ {2, 0}   ^ {x:3, y:0}         ^ {x:4, y:0} # see coord is in fields: {<fieldName>: {coord: {x:, y:}}}
  ```

2) Add connection info to the config file following the example. 
  fill 
```
redmine_account:
  api_key: 'fc..8' # siup
  host: http://redmine...
```
3) Add Custom fields info into the config file, name and id, to allow importer to work properly.
  In this case relative is a text custom field
```
custom_fields: 
  issue: 
    relative: 
      id: 1
```
This is necessary so the importer can add the right id value in the REST request sent to Redmine API.

4) add mapping info, relating fields in sheet with Redmine fields.
fill, for each sheet field of interest (note you don't HAVE to use all fields in input sheet) 
the following structure in config file:
```
  subject:
    model: {entity: 'Issue', column: 'subject'} 
    coord: {x: 3, y: 0}
    default: ~ 
    increment: {x: 0, y: 1} 
    transform: [Transformers\Transformer, asunto] 
```
Where subject: is the sheet field name, name it as you wish.
Where model refers to the Redmine entity and column/field name either native or custom
where coord is the position, zero based, of the field in the record. as shown above
where default is the value or function return value that replaces empty sheet cell values.
where increment is the step that the parser must make to find the next field of same type. i.e. 
the subject of the next record.
where transform is the function that the entity populator calls to replace the non-empty 
values that are found in the subject fields. This is useful for transforming despair human written 
values to Redmine List fields or dates. @see Transformers\Transformer. to avoid transformers use ~

5) Fill Issue default values to use for the issues when the sheet field comes empty or when there is 
no such field in sheet but redmine forces you to use it.
```
sheets: 
  demandas:  
    records:
      demanda:
        name: Demanda
        entities:
          Issue:
            defaults:
              project: A Nuevos Medios, Seguimiento de Demandas
              status: Nueva
              priority: Normal
              assigned_to: 'juan'
              author: 'juan'
              due_date: ~
              start_date: [Transform\Defaults\Defaults, startDate]
              tracker: Demanda
              "Fecha Comprometido": '2013-11-01'  #custom field
              Beneficiario: N/N  #custom field
              subject: 'Sin Asunto' 
              Intermediario: '-----------------' #custom field
```

6) Run the application from console.
So far we already have a working config, try your 1st import:
```
import --sheet="..." --record="..." [--delimiter="..."] [--fileType="..."] dataFile
```
if it worked out just how you wanted, you are done.

7) Optionally delete created Issues if mappings should be fixed.
If there were errors, check your mappings, transformations, defaults, project name and identifier. 
```
delete [--all] --project="projectIdentifier"
```
WARNING 
if --all is specified, this comand will fetch all issue ids in project and delete them
if you don't specify --all, the application will try to find the  lastIssuesId.serialized
that get written every time we finish an import process without a system crash, and holds the 
ids under the project identifier. And it will deletes all ids present in it.

Dependencies
------------

Depends on @see composer.json
[kbsali/php-redmine-api](https://github.com/kbsali/php-redmine-api)

[symfony/yaml](https://github.com/symfony/Yaml)

[symfony/console](https://github.com/symfony/Console)

Instalation
-----------

1) git clone this repo on your directory of choise

2) install [Composer](http://getcomposer.org/doc/00-intro.md#installation-windows)

3) run [composer install](http://getcomposer.org/doc/00-intro.md#using-composer) in this application directory so Composer fills the vendor directory with dependencies

Usage
-----
```
php importSheet.php help import
php importSheet.php help delete

php importSheet.php import --sheet="sheetNameInConfig" --record="recordNameInConfig" [--delimiter="..."] [--fileType="..."] dataFile
php importSheet.php delete [--all] --project="projectIdentifier"
```
Example:
```
php importSheet.php import /home/juanmf/newIssuesCreatedInExcel.csv --sheet=demandas --record=demanda
```
ups, forgot to add default value for start_date, edit config File [Config/config.yml]:
```
php importSheet.php delete --project=projectId
php importSheet.php import /home/juanmf/newIssuesCreatedInExcel.csv --sheet=demandas --record=demanda
```
Where projectId is the project identifier of the project for wich sheet "demandas" was configured.
If you are importing issues to an empty project, for 1st time, then its safe to add --all option to delete command.

Extending It
------------

So far I just play with Issues [EntityPopulator/Entities/Issue.php](/EntityPopulator/Entities/Issue.php). In [EntityPopulator/Entities](/EntityPopulator/Entities) you can add any Redmine entity, as a class sharing its name and extending "Entity" implement the save method:

Here's the Issue::save(), it's basically a proxy for the real [kbsali/php-redmine-api](https://github.com/kbsali/php-redmine-api) that adapt the field/values of custom fields before sending the create request, and takes care of updates if Id field already exists. 
```php
    public function save()
    {
        parent::adaptCustomFields($this);
        if (isset($this['id'])) {
            return $this->update();
        }
        $importService = \ImportService::getInstance();
        $api = $importService->getClient()->api(self::API);
        /* @var $api \Redmine\Api\Issue */
        $return = $api->create($this->toArray());
        $this->checkErrors($return);
        $this->addIdToCreatedIds($return, $importService);
    }
```

You are welcome to implement any entity (Users, Projects, etc.) from the Redmine [API](http://www.redmine.org/projects/redmine/wiki/Rest_api)
then all you need to do is adapt the [Config/Config.yml] to map your CSV to the right Entities fields (one sheet could map to several Entities at ones).

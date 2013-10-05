RedmineIssueImporter
====================

Use the following config file format to import sheets into redmine. So far it creates Issues, 
with custom fields, but it's extensible to add users, etc..

```yaml
################################################################################
# Run customization
################################################################################
redmine_account:
  # http://www.redmine.org/projects/redmine/wiki/Rest_api#Authentication # show API Key.
  api_key: 'f6a...7e' 
  host: 'http://redmine.iprodich:8181/redmine/'
# values that determines the config that is to be used for next run  
sheet_selection:
  sheet: 'demandas' # sheets => demandas
  record: 'demanda' # sheets => demandas => record => demanda
input_format:
  file_type: 'Csv' # maps to CsvSheetRecordParser
  delimiter: ';'   # field delimiter
  
################################################################################
# Sheets mapping configuration
################################################################################
on_error:
  behavior: continue # [continue, stop, rollback]
  display_exceptions: false # [true | false]
  
save_records: true # false hace todo el proceso de validación sin hacer save. no saltan los errores de Doctrine, solo los del Form.

custom_fields: # redmine custom fields settings <objectType> => <customFieldName> => id => <idValue>
  issue: 
    sprint: 
      id: 1 # for issues, sprint field

# all sheets that can be importes should be mapped here
sheets: 
  #1st sheet definition
  demandas:  
    # sheet Name, not used unless you add a web form that might use this name.
    name: Planilla de Relevamiento Inicial del Legajo Unico de Alumnos -Chaco-
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
              project: Gestión I.Pro.Di.Ch
              status: Nueva
              priority: Normal
              assigned_to: 'diego'
              author: 'juanmf'
              due_date: ~
              start_date: ~
              tracker: Tareas
        
        # These are the fields expected to be present in the CSV or any other sheet like input
        # next definition matched a CSV as follows (showing two records): 
        # [Subject;Description;Sprint] the rest of mandatory values are defined above for "Issue"
        # subject1;Description1;8
        # subject2;Description2;8
        fields:
          # field name
          subject:
            # this key relates this field with Redmine's model described above
            model: { entity: 'Issue', column: 'subject' }
            # This are the coordinates, where the parser tries to find the 1st occurrence of the field. 
            # Zero based. from the upper-left corner
            coord: {x: 0, y: 0}
            # default value for this field. ovverides other defaults possibly defined above. 
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            # necesary moves to reach next instance of this field, e.i. next record. Normally it'll be just 
            # one step down. (Y+1, X+0). But for values in headers, that appears only once, might be 
            # (Y+0, X+0) so, the same value is used for every record.
            increment: {x: 0, y: 1}  
            # callback that might be needed to transform input data before being persisted.
            transform: [Transformation, nombre] # a callback method

          description:
            model: { entity: 'Issue', column: 'description'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 1, y: 0}
            default: ~ # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: ~ # a callback method
           
          sprint:
            model: { entity: 'Issue', column: 'sprint'} # entity referencia entities[entity] no al schema. Para eso está entities[entity][schema_entity], en caso de que difiera.
            coord: {x: 2, y: 0}
            default: 9 # Si !== ~ pisa al default del schema y al default en [entities]
            increment: {x: 0, y: 1}  # ~ = {x: 0, y: 0} if field is recurrent increment determines the relative loction of the next sibling. ~ means the field is no recurrent, only appears once in a sheet-
            transform: ~ # a callback method

```

Depends on [kbsali/php-redmine-api](https://github.com/kbsali/php-redmine-api)

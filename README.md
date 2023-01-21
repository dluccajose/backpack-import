# dlucca/backpack-import
`dlucca/backpack-import` is a Laravel package and a Backpack Laravel addon. Allows you to add a "import operation" to your CRUDs to easily import records from an excel file into to your database. This is a personal project and still under development, its not recommended to use it in
a production environment. If you want to play with it, you can install this package as a local composer package, as still is not available via composer install.

# Features
- Import records into your DB from an excel file
- Validations
- Customize import logic
- UI for file upload

# Future features
- Table with a preview of the data to be imported

# Preview
Coming soon...

# Example

```
...
class CityCrudController extends CrudController
{
    ...
    use \Dlucca\BackpackImport\ImportOperation;

    protected function setupImportOperation()
    {
        $this->backpackImport->setUpdateField('code');
        
        $this->backpackImport->setImportValidationRules([
            'name' => ['required'],
        ]);

        $this->backpackImport->setImportColumnMapping([
            'name' => 'Ciudad',
            'code' => 'Codigo',
            'created_at' => [
                'name' => 'Fecha',
                'import_logic' => function(&$entry, $row, $value) {
                    if ($value) {
                        $entry->created_at = Date::excelToDateTimeObject($value);
                    }
                },
            ],
        ]);
    }
    ...
```


# Documentation 
- Setup
- Insert records
- Update records
- Validations
- Relationships
- Views
- Hooks
- Advance options

## Setup
To start, you have to use the operation in your backpack crud by adding the trait
`use \Dlucca\BackpackImport\ImportOperation;`.Then you should create a function `protected function setupImportOperation()`. Inside this function you will have access to the Importer object via `$this->backpackImport`.

## Insert records
To import records into to your database yo have to tell importer which columns from the excel correspond to your table fields, you can do it using the  method `$this->backpackImport->setImportColumnMapping()`, this method accepts and associative array where the keys is the name of your field in the table and the value is the name in your excel column header. For example, lets assume we have a excel file in which the header columns are "Name" and "Age", and the fields in our people table are "first_name" and "person_age"

```
$this->backpackImport->setImportColumnMapping([
  'first_name' => 'Name',
  'person_age' => 'Age',
]);
```

#### Advanced options
For more complex operations, you can provide additional options to the array:

```
$this->backpackImport->setImportColumnMapping([
    'complex_field' => [
        'name' => 'Nombre'                  // Name of the column in the excel file
        'set_after_save' => true            // If true, the import logic will be execute after the model
                                            // was saved
        'import logic' => function (&$entry, $row, $value, $operation) {     // Provide a custom logic for the import

        },
        'fake' => true                      // If true, makes the column data available in the row
                                            // variable, but it wont execute any import logic or be assign
                                            // to any property on the model. It is usefully when you need to
                                            // use that data in other field
    ]
]);
```

## Update Records
For default, every time you do a import, the package will try to insert the records in the database, even if already exists. If you want to update the records that already exist, you can set an "update field". The package will do a search by the field provided, and will update the record if there any result. 

```
$this->backpackImport->setUpdateField('code');
```

## Validations
When performing the upload and import of the excel file, you can set validations just that you would do in your typical create/update CRUD. 

Set validations rule

```
$this->backpackImport->setImportValidationRules([
    'name' => ['required'],
]);
```

Set validation attributes names

```
$this->backpackImport->setImportValidationAttributes([
    'name' => 'Nombre',
]);
```

## Hooks
Hooks are functions that you can attach to execute in different moments of the import operation. List of available hooks

```
$this->backpackImport->doHook('before_import', function ($mappedRows) {});
$this->backpackImport->doHook('before_insert', function ($entry, $row) {});
$this->backpackImport->doHook('after_insert', function ($entry, $row) {});
$this->backpackImport->doHook('after_import', function () {});
```
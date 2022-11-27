# dlucca/backpack-import
backpack-import is a Laravel package and addon for Backpack package. Allows you to add a "import operation" to your CRUDs to easily import records from an excel file into to your database.. This is a personal project and still under development, its not recomended to use it in
a production enviroment. If you want to play with it, you can install this package as a local composer package, as still is not available via composer install.

# Features
- Import records into your DB from an excel file
- Validations
- UI for file upload

# Preview
Coming soon...

# How to use it
Coming soon...

# Documentation 
- Configuration
- Insert records
- Update records
- Validations
- Relationships
- Views
- Hooks
- Advance options

## Configuration
To start, you have to use the operation in your backpack crud by adding the trait
`use \Dlucca\BackpackImport\ImportOperation;`.Then you should create a function `protected function setupImportOperation()`. Inside this function you will have access to the importer object via $this->backpackImport 

## Insert records
To import records into to your database yo have to tell importer wich columns from the excel correspond to your table fields, you can do it usign the  method `$this->backpackImport->setImportColumnMapping()`, this method accepts and associative array where the keys is the of your field in the table and the value is the name in your column excel. For example, lets assume we have a excel file in wich the columns headers are "Name" and "Age", and the fields in our people table are "first_name" and "person_age"

```
$this->backpackImport->setImportColumnMapping([
  'first_name' => 'Name',
  'person_age' => 'Age',
]);
```

## Update Records
Coming soon...

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

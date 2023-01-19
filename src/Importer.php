<?php

namespace Dlucca\BackpackImport;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Importer
{
    private ?string $updateField = null;

    private array $hooks = [];

    private array $importColumnMapping;

    private array $importValidationRules;

    private array $importValidationAttributes;

    private Model $model;

    private $importModelCallback;

    private $importCollectionCallback;

    private array $settings;

    public function __construct($importColumnMapping = [], $importValidationRules = [], $importValidationAttributes = [])
    {
        $this->importColumnMapping = $importColumnMapping;
        $this->importValidationRules = $importValidationRules;
        $this->importValidationAttributes = $importValidationAttributes;
    }

    private function setSetting($key, $value)
    {
        $this->settings[$key] = $value;
    }

    private function getSetting(string $key, $defaultValue = null)
    {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        } else {
            return $defaultValue;
        }
    }

    public function setExcelPageNumber(int $page): void
    {
        $this->setSetting('excel.page_number', $page);
    }

    public function getExcelPageNumber()
    {
        return $this->getSetting('excel.page_number', 0);
    }

    public function setHeadingRow(int $headingRow): void
    {
        $this->setSetting('excel.heading_row', $headingRow);
    }

    public function getHeadingRow()
    {
        return $this->getSetting('excel.heading_row', 1);
    }

    public function setUpdateField(string $name): void
    {
        $this->updateField = $name;
    }

    public function setImportColumnMapping($array): void
    {
        $this->importColumnMapping = $array;
    }

    public function setImportValidationRules($array): void
    {
        $this->importValidationRules = $array;
    }

    public function setImportValidationAttributes($array): void
    {
        $this->importValidationAttributes = $array;
    }

    public function setModel($model): void
    {
        $this->model = $model;
    }

    public function setImportModelCallback($callback): void
    {
        $this->importModelCallback = $callback;
    }

    public function setImportCollectionCallback($callback): void
    {
        $this->importCollectionCallback = $callback;
    }

    /**
     * Import the excel file into the database
     *
     * @param [type] $importFile
     * @return boolean
     */
    public function import($importFile): bool
    {
        $excelImporter = new ExcelImport($this->getHeadingRow());

        $rows = Excel::toCollection($excelImporter, $importFile)[$this->getExcelPageNumber()];

        $mappedRows = $this->getMappedData($rows);

        // Validate data if validation rules are provided
        if ($this->importValidationRules) {
            $arrayRules = $this->getValidationRules();

            $validator = Validator::make(
                $mappedRows,
                $arrayRules,
                $this->formatValidationMessages(count($mappedRows)),
                $this->validationAttributesToArraySyntax()
            );

            // who handles the validation exception?
            $validator->validate();
        }

        // Override import collection logic if callback is provided 
        if ($this->importCollectionCallback) {
            ($this->importCollectionCallback)($mappedRows);

            return true;
        }

        // Remove empty rows
        $mappedRows = collect($mappedRows)->filter(function ($row) {
            return collect($row)->filter()->count();
        })->toArray();

        DB::beginTransaction();

        try {
            $this->doHook('before_import', $mappedRows);

            foreach ($mappedRows as $row) {
                // Override import model logic if callback is provided
                if ($this->importModelCallback) {
                    ($this->importModelCallback)($row);
                } else {
                    $this->importModel($row);
                }
            }
        } catch (Exception $e) {
            // If somethings fails, we rollback all the DB changes and throw the exception
            DB::rollBack();
            throw $e;
        }

        $this->doHook('after_import');

        DB::commit();

        return true;
    }

    /**
     * Save/update the row into a model in the database
     *
     * @param $row
     * @return void
     */
    private function importModel($row): void
    {
        if ($this->updateField) {
            $entry = $this->model::where($this->updateField, $row[$this->updateField])->first() ?? clone $this->model;
        } else {
            $entry = clone $this->model;
        }

        $operation = $entry->getKey() ? 'update' : 'create';

        // Props that should change before save
        $colsBeforeSave = collect($row)->filter(function ($value, $col) {
            return !($this->importColumnMapping[$col]['set_after_save'] ?? false);
        });

        // Props that should change after save
        $colsAfterSave = collect($row)->filter(function ($value, $col) {
            return ($this->importColumnMapping[$col]['set_after_save'] ?? false);
        });

        $entry = $this->setRowDataToEntryModel($entry, $colsBeforeSave, $operation);

        $this->doHook('before_insert', $entry, $row);

        $entry->save();

        $entry = $this->setRowDataToEntryModel($entry, $colsAfterSave, $operation);

        $entry->update();

        $this->doHook('after_insert', $entry, $row);
    }

    /**
     * Get the data from the excel file using the mapping definition provided. The keys of the
     * resulting array will be the name of the database fields specify on the mapping definition
     *
     * @param $rows
     * @return array
     */
    private function getMappedData($rows): array
    {
        $mappedRows = [];

        // flip the column mapping so we can used as a dictionary and check if
        // the a column from the excel file is present in the column mapping definition
        $columnsMappingDefinition = array_flip($this->columnSlugMapping());

        foreach ($rows as $row) {
            $mapRow = [];

            foreach ($row as $column => $value) {
                // ignore columns that are in the excel file but are not present in the column mapping
                if (isset($columnsMappingDefinition[$column])) {
                    $mapRow[$columnsMappingDefinition[$column]] = $value;
                }
            }

            $mappedRows[] = $mapRow;
        }

        return $mappedRows;
    }
    
    /**
     * Get an array with the column mapping. The first key correspond to the name of the field
     * on the database for the model and the value is the name of the column in the excel cell
     * Example [model_field => "Name on the excel cell"]
     *
     * @return array
     */
    private function getColumnMapping(): array
    {
        return collect($this->importColumnMapping)->map(function ($mappingData, $modelField) {
            
            // The mapping data for the model field can be an array holding some configuration
            if (is_array($mappingData)) {
                if (isset($mappingData['name'])) {
                    return $mappingData['name'];
                } else {
                    throw new Exception('Missing "name" for column mapping "'.$modelField.'"');
                }
            } else {
                return $mappingData;
            }
        })->toArray();
    }

    /**
     * Slugify the column names of the mapping to match the name of the array key of
     * the rows fetched from the excel file
     *
     * @return array
     */
    private function columnSlugMapping(): array
    {
        return array_map(function ($column) {
            return Str::slug($column, '_');
        }, $this->getColumnMapping());
    }

    /**
     * Add a hook to the be executed
     *
     * @param [type] $eventName
     * @param [type] $callback
     * @return void
     */
    public function addHook($eventName, $callback): void
    {
        $this->hooks[$eventName] = $callback;
    }

    /**
     * Execute the indicate hook
     *
     * @param string $eventName
     * @param [type] ...$params
     * @return void
     */
    private function doHook(string $eventName, &...$params): void
    {
        if (isset($this->hooks[$eventName])) {
            call_user_func_array($this->hooks[$eventName], [...$params]);
        }
    }

    public function getValidationRules(): array
    {
        // Convert validations rules to dot syntax so we can use array validations
        $arrayRules = [];

        foreach ($this->importValidationRules as $name => $rules) {
            $arrayRules['*.' . $name] = $rules;
        }

        return $arrayRules;
    }

    private function validationAttributesToArraySyntax(): array
    {
        $validationAttributes = [];

        if (!$this->importValidationAttributes) {
            return [];
        }

        foreach ($this->importValidationAttributes as $key => $name) {
            $validationAttributes['*.' . $key] = $name;
        }

        return $validationAttributes;
    }

    private function formatValidationMessages($rowsCount): array
    {
        $formattedMessages = [];

        $validationRules = $this->importValidationRules;

        for ($i = 0; $i < $rowsCount; $i++) {
            $rowNumber = $i + 2;

            foreach ($validationRules as $attribute => $rules) {
                if (!is_array($rules)) {
                    $rules = explode('|', $rules);
                }

                foreach ($rules as $rule) {
                    $ruleName = explode(':', $rule)[0];

                    if (is_array(__("validation.$ruleName"))) {
                        // TODO: para las validaciones que contienen arrays (aplican diferente
                        // dependiendo del tipo de validación anterior)
                        $message = collect(__("validation.$ruleName"))->first();
                    } else {
                        $message = __("validation.$ruleName");
                    }

                    $formattedMessages["$i.$attribute.$ruleName"] = "Fila $rowNumber: " . $message;
                }
            }
        }

        return $formattedMessages;
    }
    
    /**
     * Loop through the row data and set it to the entry model
     * 
     * @todo permitir al desarrollador omitir columnas al actualizar un modelo
     *
     * @param $entry
     * @param $row
     * @param string $operation
     */
    private function setRowDataToEntryModel($entry, $row, string $operation)
    {
        foreach ($row as $column => $value) {
            $customImportLogic = $this->importColumnMapping[$column]['import_logic'] ?? false;
            
            if ($customImportLogic) {
                $customImportLogic($entry, $row, $value, $operation);
            } else {
                $entry->{$column} = $value;
            }
        }

        return $entry;
    }
}

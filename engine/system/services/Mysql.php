<?php

namespace system\services {

    abstract class Mysql {

        protected static $connector = null;
        protected static $connectors = [];
        private  static $builtEntityNames = [];
        private $configParamName = "";
        private $lastSelectAdvanceWhere = null;

        const ATTR_AUTOCOMMIT = \PDO::ATTR_AUTOCOMMIT;
        const ATTR_CASE = \PDO::ATTR_CASE;
        const ATTR_CLIENT_VERSION = \PDO::ATTR_CLIENT_VERSION;
        const ATTR_CONNECTION_STATUS = \PDO::ATTR_CONNECTION_STATUS;
        const ATTR_DRIVER_NAME = \PDO::ATTR_DRIVER_NAME;
        const ATTR_ERRMODE = \PDO::ATTR_ERRMODE;
        const ATTR_ORACLE_NULLS = \PDO::ATTR_ORACLE_NULLS;
        const ATTR_PERSISTENT = \PDO::ATTR_PERSISTENT;
        const ATTR_PREFETCH = \PDO::ATTR_PREFETCH;
        const ATTR_SERVER_INFO = \PDO::ATTR_SERVER_INFO;
        const ATTR_SERVER_VERSION = \PDO::ATTR_SERVER_VERSION;
        const ATTR_TIMEOUT = \PDO::ATTR_TIMEOUT;

        private static $lockSelectedRows = false;
        private static $prevAutocommiteValue = null;
        private $lastSelectQuery = false;
        private $lastUpdateAdvanceQuery = false;

        /**
         * Initializes DBMS pointer.
         */
        function __construct($configParamName = 'mysql') {
            $this->configParamName = $configParamName;
            self::init($this->configParamName);
        }

        public static function init($configParamName) {
            if (!isset(self::$connectors[$configParamName])) {
                $mysqlConfig = Sys()->getConfig($configParamName);
                if (!isset($mysqlConfig)) {
                    \system\SysExceptions::noMysqlConfig();
                }
                self::$connectors[$configParamName] = new \system\connectors\MySql($mysqlConfig['host'], $mysqlConfig['user'], $mysqlConfig['pass'], $mysqlConfig['name']);
            }
        }

        private function hasEntityClass($reflector = null) {
            $ref = $reflector ?? (new \ReflectionClass($this->getNewEntity()));
            $parentClass = $ref->getParentClass();
            return isset($parentClass) && $parentClass->name === 'system\entities\SysEntity';
        }

        private function getEntityClassProperties($reflector = null) {
            $ref = $reflector ?? (new \ReflectionClass($this->getNewEntity()));
            if (!$this->hasEntityClass($ref)) {
                return false;
            }
            $ret = [];
            foreach ($ref->getProperties() as $p) {
                if ($ref->name === $p->class) {
                    $ret[] = $p->name;
                }
            }
            return $ret;
        }

        public function buildEntityClass() {
            $reflector = new \ReflectionClass($this->getNewEntity());
            if (isset(self::$builtEntityNames[$reflector->name] )){
                return false;
            }
            self::$builtEntityNames[$reflector->name] = true;
            
            
            if (!$this->hasEntityClass($reflector)) {
                return false;
            }

            $tableFieldNames = $this->getTableFieldNames();
            $classPropertyNames = $this->getEntityClassProperties();

            $fieldsToAddIntoClass = array_diff($tableFieldNames, $classPropertyNames);
            if (empty($fieldsToAddIntoClass)) {
                return 0;
            }
            $lastPropertyLineIndent = 8;
            $lastPropertyLineNumber = $reflector->getStartLine();
            $classFileSplObject = new \SplFileObject($reflector->getFileName(), 'r+');
            
            if (!empty($classPropertyNames)) {
                $lastPropertyName = $classPropertyNames[count($classPropertyNames) - 1];
                foreach ($classFileSplObject as $line => $content) {
                    $matches == [];
                    if (preg_match('/(private|protected|public|var)\s+\$' . $lastPropertyName . '/x', $content, $matches, PREG_OFFSET_CAPTURE)) {
                        $lastPropertyLineIndent = $matches[0][1];
                        $lastPropertyLineNumber = $line;
                        break;
                    }
                }
            }
            $classFileSplObject->seek($lastPropertyLineNumber + 1);
            $tail = $classFileSplObject->fread($classFileSplObject->getSize() - $classFileSplObject->ftell());
            $classFileSplObject->fseek($classFileSplObject->ftell() - strlen($tail));

            foreach ($fieldsToAddIntoClass as $fieldToAddIntoClass) {
                $classFileSplObject->fwrite(str_repeat(' ', $lastPropertyLineIndent) . 'public $' . $fieldToAddIntoClass . ';' . PHP_EOL);
            }
            $classFileSplObject->fwrite($tail);
            $classFileSplObject->fflush();
            return count($fieldsToAddIntoClass);
        }

        public function getTableFieldNames($returnTypes = false) {
            $tableStructure = $this->getTableStructure();
            $ret = [];
            foreach ($tableStructure as $value) {
                if ($returnTypes) {
                    $ret[$value['COLUMN_NAME']] = $value['DATA_TYPE'];
                } else {
                    $ret[] = $value['COLUMN_NAME'];
                }
            }
            return $ret;
        }

        public function getTableStructure() {
            $dbName = $this->getConnector()->getDbName();
            $sqlQuery = sprintf("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '%s'", $this->_getTableName());
            $res = $this->prepare($sqlQuery);
            $results = $res->execute();
            if ($results == false) {
                return false;
            }
            return $res->fetchAll(\PDO::FETCH_ASSOC);
        }

        public function getLastInsertId() {
            return $this->getConnector()->lastInsertId();
        }

        protected function getConnector() {
            self::$connector = self::$connectors[$this->configParamName];
            return self::$connector;
        }

        private function _getTableName() {
            return $this->getTablePrefix() . $this->getTableName();
        }

        abstract public function getTableName();

        protected function getTablePrefix() {
            return "";
        }

        public function getNewEntity() {
            return new \system\entities\SysEntity();
        }

        /**
         * Inserts object into table.
         *
         * @param object $object
         * @param object $esc [optional] - shows if the textual values must be escaped before setting to DB
         * @return autogenerated id or -1 if something goes wrong
         */
        public function insertArray($array, $ignoreError = false) {
            $ignore = $ignoreError ? 'IGNORE' : '';
            $sqlQuery = sprintf("INSERT %s INTO `%s` SET ", $ignore, $this->_getTableName());
            $availableFields = [];
            foreach ($array as $fieldName => $fieldValue) {
                if (isset($fieldValue)) {
                    $availableFields[$fieldName] = $fieldValue;
                    $sqlQuery .= sprintf(" `%s` = :%s,", $fieldName, $fieldName);
                }
            }

            if (empty($availableFields)) {
                $sqlQuery .= " `id` = NULL";
            }
            $res = $this->prepare(trim($sqlQuery, ','));

            if ($res) {
                $res->execute($availableFields);
                return $this->getConnector()->lastInsertId();
            }
            return null;
        }

        /**
         * Inserts object into table.
         *
         * @param object $object
         * @param object $esc [optional] - shows if the textual values must be escaped before setting to DB
         * @return autogenerated id or -1 if something goes wrong
         */
        public function insert($object, $ignoreError = false) {
//validating input params
            if ($object == null) {
                \system\SysExceptions::unknownError();
            }
            $fieldsNameValue = get_object_vars($object);
            return $this->insertArray($fieldsNameValue, $ignoreError);
        }

        /**
         * Updates object by ID.
         *
         * @param object $object
         * @param object $esc [optional] - shows if the textual values must be escaped before setting to DB
         * @return autogenerated id or -1 if something goes wrong
         */
        public function update($object) {
//validating input params
            if ($object == null) {
                \system\SysExceptions::unknownError();
            }
            $id = $object->getId();
            unset($object->id);
            $fieldsNameValue = get_object_vars($object);

//creating query
            $sqlQuery = sprintf("UPDATE `%s` SET ", $this->_getTableName());
            $availableFields = [];
            foreach ($fieldsNameValue as $fieldName => $fieldValue) {
                if (isset($fieldValue)) {
                    $availableFields[$fieldName] = $fieldValue;
                    $sqlQuery .= sprintf(" `%s` = :%s,", $fieldName, $fieldName);
                }
            }

            $sqlQuery = trim($sqlQuery, ',') . ' WHERE id=:id';
            $res = $this->prepare($sqlQuery);
            $object->id = $id;
            if ($res) {
                $availableFields['id'] = $id;
                $res->execute($availableFields);
                return true;
            }
            return false;
        }

        public function updateFieldsById($id, $fieldsValuesMapArray) {
            return $this->updateAdvance(['id', '=', $id], $fieldsValuesMapArray);
        }

        public function updateFieldById($id, $fieldName, $fieldValue) {
            $params = ["id" => $id];
            if (isset($fieldValue)) {
                if ($fieldName[0] === '*') {
                    $fieldName = substr($fieldName, 1);
                    $sqlQuery = sprintf("UPDATE `%s` SET `%s` = $fieldValue WHERE `id` = :id ", $this->_getTableName(), $fieldName);
                } else {
                    $sqlQuery = sprintf("UPDATE `%s` SET `%s` = :%s WHERE `id` = :id", $this->_getTableName(), $fieldName, $fieldName);
                    $params[$fieldName] = $fieldValue;
                }
            } else {
                $sqlQuery = sprintf("UPDATE `%s` SET `%s` = NULL WHERE `id` = :id ", $this->_getTableName(), $fieldName);
            }
            $res = $this->prepare($sqlQuery);
            if ($res) {
                $res->execute($params);
                return $res->rowCount();
            }
            return null;
        }

        public function deleteByIds($ids) {
            if (empty($ids)) {
                return null;
            }
            $sqlQuery = sprintf("DELETE FROM `%s` WHERE `id` in (%s) ", $this->_getTableName(), implode(',', $ids));
            $res = $this->prepare($sqlQuery);
            if ($res) {
                $res->execute();
                return $res->rowCount();
            }
            return null;
        }

        /**
         * Deletes the row by primary key
         *
         * @param object $id - the unique identifier of table
         * @return affacted rows count or -1 if something goes wrong
         */
        public function deleteById($id) {

            $sqlQuery = sprintf("DELETE FROM `%s` WHERE `id` = :id", $this->_getTableName());
            $res = $this->prepare($sqlQuery);
            if ($res) {
                $res->execute(array("id" => $id));
                return $res->rowCount();
            }
            return null;
        }

        public function selectRandomOne($filters = [], $fields = '*') {
            $objects = $this->selectAdvance($fields, $filters, [], ['RAND ( )' => 'ASC'], 0, 1);
            if (!empty($objects)) {
                return $objects[0];
            }
            return false;
        }

        public function getLastSelectQuery() {
            return $this->lastSelectQuery;
        }

        public function getLastUpdateAdvanceQuery() {
            return $this->lastUpdateAdvanceQuery;
        }

        public function fetchAll($sqlQuery, $params = array()) {
            $this->lastSelectQuery = $sqlQuery;
            $res = $this->prepare($sqlQuery);
            $results = $res->execute($params);
            if ($results == false) {
                return false;
            }
            $resultArr = [];
            $entityClass = get_class($this->getNewEntity());
            while ($row = $res->fetchObject($entityClass)) {
                $resultArr[] = $row;
            }
            return $resultArr;
        }

        /**
         * Executes the query and returns an row field of corresponding DTOs
         * if $row isn't false return first elem
         *
         * @param object $sqlQuery
         * @return
         */
        public function fetchOne($sqlQuery, $params = array()) {
            $rows = $this->fetchAll($sqlQuery, $params);
            if (!empty($rows) && is_array($rows)) {
                return $rows[0];
            }
            return false;
        }

        public function fetchField($sqlQuery, $fieldName, $params = array()) {
            $res = $this->prepare($sqlQuery);
            $results = $res->execute($params);
            if ($results) {
                return $res->fetchObject()->$fieldName;
            }
            return null;
        }

        /**
         * Selects all entries from table
         * @return
         */
        public function selectAll($orderByFieldsArray = [], $mapByField = null) {
            $order = $this->prepareOrderBy($orderByFieldsArray);
            $sqlQuery = sprintf("SELECT * FROM `%s` %s", $this->_getTableName(), $order);
            $ret = $this->fetchAll($sqlQuery);
            if (isset($mapByField)) {
                return $this->mapObjectsByField($ret, $mapByField);
            }
            return $ret;
        }

        /**
         * Returns rows ids array
         * @return
         */
        public function selectIds($filters = [], $orderByFieldsArray = []) {
            $rows = $this->selectAdvance('id', $filters, [], $orderByFieldsArray);
            $ret = [];
            foreach ($rows as $row) {
                $ret[] = $row->getId();
            }
            return $ret;
        }

        public function existsById($id) {
            return $this->selectAdvanceOne(['id', '=', $id], [], ['id']) !== false;
        }

        /**
         * Selects from table by primary key and returns corresponding DTO
         *
         * @param object $id
         * @return
         */
        public function selectById($id, $returninigFieldName = "*") {
            $sqlQuery = sprintf("SELECT $returninigFieldName FROM `%s` WHERE `id` = :id ", $this->_getTableName());
            $ret = $this->fetchOne($sqlQuery, ["id" => $id]);
            if (empty($ret)) {
                return null;
            }
            if ($returninigFieldName === '*' || empty($ret)) {
                return $ret;
            }
            return $ret->$returninigFieldName;
        }

        public function selectByIds($ids, $mapByField = null, $orderByGivenIdsSequence = false) {
            if (empty($ids)) {
                return [];
            }
            $idsStr = implode(',', $ids);
            $orderBy = "";
            if ($orderByGivenIdsSequence) {
                $orderBy = "ORDER BY FIELD(`id`,$idsStr)";
            }
            $sqlQuery = sprintf("SELECT * FROM `%s` WHERE `id` in (%s) %s", $this->_getTableName(), $idsStr, $orderBy);
            $ret = $this->fetchAll($sqlQuery);
            if ($mapByField) {
                return $this->mapObjectsByField($ret, $mapByField);
            }
            return $ret;
        }

        public function countAdvance($filters = null, $join = '') {
            $where = $filters;
            if (is_array($filters)) {
                $where = $this->getWhereSubQueryByFilters($filters);
            }
            $sqlQuery = sprintf("SELECT count(id) as `count` FROM `%s` %s %s ", $this->_getTableName(), $join, $where);
            return intval($this->fetchField($sqlQuery, 'count'));
        }

        public function getColumnSum($field, $filters = []) {
            $rows = $this->selectAdvance(["SUM(`$field`) as `$field`"], $filters);
            if (empty($rows)) {
                return false;
            }
            return $rows[0]->$field;
        }

        public function selectAdvanceOne($filters = [], $orderByFieldsArray = [], $fields = '*') {
            $objects = $this->selectAdvance($fields, $filters, [], $orderByFieldsArray, 0, 1);
            if (!empty($objects)) {
                return $objects[0];
            }
            return false;
        }

        private function prepareOrderBy($orderByFieldsArray) {
            $order = "";
            if (!empty($orderByFieldsArray) && is_array($orderByFieldsArray)) {
                $orderBySqlArray = [];
                foreach ($orderByFieldsArray as $fieldName => $ascDesc) {
                    if (!in_array(strtoupper($ascDesc), ['ASC', 'DESC'])) {
                        $ascDesc = 'ASC';
                    }
                    $orderBySqlArray [] = ((strpos($fieldName, ' ') === false) ? ('`' . $fieldName . '`') : $fieldName) . ' ' . $ascDesc;
                }
                $order = 'ORDER BY ' . implode(', ', $orderBySqlArray);
            }
            return $order;
        }

        public function setLockSelectedRows() {
            self::$lockSelectedRows = true;
        }

        public function selectAdvance($fieldsArray = '*', $filters = [], $groupByFieldsArray = [], $orderByFieldsArray = [], $offset = null, $limit = null, $join = null, $mapByField = False) {
            $where = $this->getWhereSubQueryByFilters($filters);
            $groupBy = $this->getGroupBySubQueryByFilters($groupByFieldsArray);
            $fields = $this->getFieldsSubQuery($fieldsArray);
            $order = $this->prepareOrderBy($orderByFieldsArray);
            $this->lastSelectAdvanceWhere = $where;
            if (empty($join)) {
                $join = "";
            }
            $sqlQuery = sprintf("SELECT %s FROM `%s` %s %s %s %s", $fields, $this->_getTableName(), $join, $where, $groupBy, $order);
            if (isset($limit) && $limit > 0) {
                $sqlQuery .= ' LIMIT ' . $offset . ', ' . $limit;
            }
            if (self::$lockSelectedRows === true) {
                $sqlQuery .= ' FOR UPDATE';
            }
            $ret = $this->fetchAll($sqlQuery);
            if ($mapByField) {
                return $this->mapObjectsByField($ret, $mapByField);
            }
            return $ret;
        }

        public function fieldExists($fieldName) {
            $ret = $this->fetchOne("SHOW COLUMNS FROM `skype_account_numbers` LIKE '$fieldName'");
            return !empty($ret);
        }

        public function updateAdvance($where, $fieldsValuesMapArray, $orderByFieldsArray = [], $limit = null) {
            $order = $this->prepareOrderBy($orderByFieldsArray);
            $where = $this->getWhereSubQueryByFilters($where);
            $subQuerySetValues = "";
            $fieldsValuesMapArrayCopy = $fieldsValuesMapArray;
            foreach ($fieldsValuesMapArrayCopy as $fieldName => $fieldValue) {
                if ($fieldName[0] !== '*') {
                    $subQuerySetValues .= "`$fieldName` = :$fieldName ,";
                } else {
                    $fieldNameClean = substr($fieldName, 1);
                    $subQuerySetValues .= "`$fieldNameClean` = $fieldValue,";
                    unset($fieldsValuesMapArray[$fieldName]);
                }
            }
            $subQuerySetValues = trim($subQuerySetValues);
            $subQuerySetValues = trim($subQuerySetValues, ',');

            $sqlQuery = sprintf("UPDATE `%s` SET %s %s %s", $this->_getTableName(), $subQuerySetValues, $where, $order);
            if (isset($limit) && intval($limit) > 0) {
                $sqlQuery .= ' LIMIT ' . intval($limit);
            }
            $this->lastUpdateAdvanceQuery = $sqlQuery;
            $res = $this->prepare($sqlQuery);
            if ($res) {
                $res->execute($fieldsValuesMapArray);
                return $res->rowCount();
            }
            return null;
        }

        public function deleteAdvance($where) {
            $where = $this->getWhereSubQueryByFilters($where);
            $sqlQuery = sprintf("DELETE FROM `%s` %s", $this->_getTableName(), $where);
            $res = $this->prepare($sqlQuery);
            if ($res) {
                $res->execute();
                return $res->rowCount();
            }
            return null;
        }

        public function getMinId() {
            $sqlQuery = sprintf("SELECT `id` FROM `%s` order by `id` ASC LIMIT 0, 1", $this->_getTableName());
            $ret = $this->fetchOne($sqlQuery);
            return $ret->getId();
        }

        public function getMaxId() {
            $sqlQuery = sprintf("SELECT `id` FROM `%s` order by `id` DESC LIMIT 0, 1", $this->_getTableName());
            $ret = $this->fetchOne($sqlQuery);
            if (empty($ret)) {
                return false;
            }
            return $ret->getId();
        }

        public function getLastSelectAdvanceRowsCount() {
            if (!isset($this->lastSelectAdvanceWhere)) {
                return 0;
            }
            return intval($this->countAdvance($this->lastSelectAdvanceWhere));
        }

        public function selectOneByField($fieldName, $fieldValue, $orderByFieldsArray = [], $returninigFieldName = "*") {
            $order = $this->prepareOrderBy($orderByFieldsArray);
            $sqlQuery = sprintf("SELECT * FROM `%s` WHERE `%s` = :value %s", $this->_getTableName(), $fieldName, $order);
            $ret = $this->fetchOne($sqlQuery, array("value" => $fieldValue));
            if ($returninigFieldName === '*' || empty($ret)) {
                return $ret;
            }
            return $ret->$returninigFieldName;
//            $fname = \system\util\TextHelper::camelCase($returninigFieldName, 'get');
//            return call_user_func(array($ret, $fname));
        }

        public function selectByField($fieldName, $fieldValue, $orderByFieldsArray = []) {
            $order = $this->prepareOrderBy($orderByFieldsArray);
            $sqlQuery = sprintf("SELECT * FROM `%s` WHERE `%s` = :value %s", $this->_getTableName(), $fieldName, $order);
            return $this->fetchAll($sqlQuery, array("value" => $fieldValue));
        }

        public function deleteByField($fieldName, $fieldValue) {
            $sqlQuery = sprintf("DELETE FROM `%s` WHERE `%s` = :value ", $this->_getTableName(), $fieldName);
            $res = $this->prepare($sqlQuery);
            if ($res) {
                $res->execute(["value" => $fieldValue]);
                return $res->rowCount();
            }
            return null;
        }

        public function query($query, $params = []) {
            $res = $this->prepare($query);
            return $res->execute($params);
        }

        public function truncate() {
            $sqlQuery = sprintf("Truncate table `%s`", $this->_getTableName());
            $res = $this->prepare($sqlQuery);
            return $res->execute();
        }

        public function startTransaction($lockSelectedRows = false) {
            self::$prevAutocommiteValue = $this->getConnector()->getAttribute(self::ATTR_AUTOCOMMIT);
            $this->getConnector()->setAttribute(self::ATTR_AUTOCOMMIT, false);
            $this->getConnector()->beginTransaction();
            self::$lockSelectedRows = $lockSelectedRows;
        }

        /**
         * Is in transaction
         */
        public function inTransaction() {
            return $this->getConnector()->inTransaction();
        }

        /**
         * Commits the current transaction
         */
        public function commitTransaction() {
            $this->getConnector()->commit();
            $this->getConnector()->setAttribute(self::ATTR_AUTOCOMMIT, self::$prevAutocommiteValue);
        }

        /**
         * Set an attribute
         * @link http://php.net/manual/en/pdo.setattribute.php
         * @param int $attribute
         * @param mixed $value
         * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
         */
        public function setAttribute($attribute, $value) {
            return $this->getConnector()->setAttribute($attribute, $value);
        }

        /**
         * Rollback the current transaction
         */
        public function rollbackTransaction() {
            $this->getConnector()->rollback();
        }

        public static function dump($filePath, $fileName = false, $configParamName = 'mysql', $tables = '*', $excludeTables = [], $bulkInsertCunck = 10) {
            $mysqlConfig = Sys()->getConfig($configParamName);
            if (empty($fileName)) {
                $fileName = $mysqlConfig['name'] . '_' . date('Y_m_d_H_i_s') . '.sql';
            }
            $handle = fopen(rtrim($filePath, '/\\') . DIRECTORY_SEPARATOR . $fileName, 'w+');

            $mysqli = new \mysqli($mysqlConfig['host'], $mysqlConfig['user'], $mysqlConfig['pass'], $mysqlConfig['name']);
            $mysqli->select_db($mysqlConfig['name']);
            $mysqli->query("SET NAMES 'utf8'");
            $queryTables = $mysqli->query('SHOW TABLES');
            $target_tables = [];
            while ($row = $queryTables->fetch_row()) {
                $target_tables[] = $row[0];
            }
            if ($tables !== '*') {
                $target_tables = array_intersect($target_tables, $tables);
            }

            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `" . $mysqlConfig['name'] . "`\r\n--\r\n\r\n\r\n");
            foreach ($target_tables as $table) {
                if (empty($table)) {
                    continue;
                }
                if (in_array($table, $excludeTables)) {
                    continue;
                }
                $result = $mysqli->query('SELECT * FROM `' . $table . '`');
                $fields_amount = $result->field_count;
                $rows_num = $mysqli->affected_rows;
                $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
                $TableMLine = $res->fetch_row();

                fwrite($handle, "\n\n" . "DROP TABLE $table" . ";\n\n");
                fwrite($handle, "\n\n" . $TableMLine[1] . ";\n\n");
                for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
                    while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
                        if ($st_counter % $bulkInsertCunck == 0 || $st_counter == 0) {
                            fwrite($handle, "\nINSERT INTO " . $table . " VALUES");
                        }
                        fwrite($handle, "\n(");
                        for ($j = 0; $j < $fields_amount; $j++) {
                            $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                            if (isset($row[$j])) {
                                fwrite($handle, '"' . $row[$j] . '"');
                            } else {
                                fwrite($handle, '""');
                            } if ($j < ($fields_amount - 1)) {
                                fwrite($handle, ',');
                            }
                        } fwrite($handle, ")");
                        //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                        if ((($st_counter + 1) % $bulkInsertCunck == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
                            fwrite($handle, ";");
                        } else {
                            fwrite($handle, ",");
                        } $st_counter = $st_counter + 1;
                    }
                } fwrite($handle, "\n\n\n");
            }
            fwrite($handle, "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;");
            fclose($handle);
            return $fileName;
        }

        public static function restore($filePath, $configParamName = 'mysql') {
            $mysqlConfig = Sys()->getConfig($configParamName);
            // Connect & select the database
            $mysqli = new \mysqli($mysqlConfig['host'], $mysqlConfig['user'], $mysqlConfig['pass'], $mysqlConfig['name']);
            $mysqli->select_db($mysqlConfig['name']);
            // Temporary variable, used to store current query
            $templine = '';

            // Read in entire file
            $lines = file($filePath);
            $error = '';

            // Loop through each line
            foreach ($lines as $line) {
                // Skip it if it's a comment
                if (substr($line, 0, 2) == '--' || $line == '') {
                    continue;
                }

                // Add this line to the current segment
                $templine .= $line;

                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';') {
                    // Perform the query
                    if (!$mysqli->query($templine)) {
                        $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $mysqli->error . '<br /><br />';
                    }

                    // Reset temp variable to empty
                    $templine = '';
                }
            }
            return !empty($error) ? $error : true;
        }

        public function getWhereSubQueryByFilters($filters, $includeWhereWord = true) {
            if (empty($filters) || !is_array($filters)) {
                return "";
            }
            $where = "";
            if ($includeWhereWord) {
                $where = "WHERE ";
            }
            foreach ($filters as $filter) {
                $strToLowerFilter = strtolower(trim($filter));
                if (in_array($strToLowerFilter, [')', '(', 'and', 'or', '<', '<=', '<>', '=', '>', '>=', 'is', 'null', 'not'])) {
                    $where .= ' ' . strtoupper($strToLowerFilter) . ' ';
                } else {
                    $where .= ' ' . (is_null($filter) ? "''" : $filter) . ' ';
                }
            }
            return $where;
        }

        private function getFieldsSubQuery($fieldsArray) {
            if (empty($fieldsArray) || $fieldsArray === '*') {
                return "*";
            }
            if (is_string($fieldsArray) && strpos($fieldsArray, ',') !== false) {
                $fieldsArray = explode(',', $fieldsArray);
            }
            if (!is_array($fieldsArray)) {
                $fieldsArray = [$fieldsArray];
            }
            $ret = "";
            foreach ($fieldsArray as $fieldName) {
                if (strpos($fieldName, '`') === false && $fieldName !== '*') {
                    $fieldName = '`' . $fieldName . '`';
                }
                $ret .= $fieldName . ',';
            }
            return trim($ret, ',');
        }

        private function getGroupBySubQueryByFilters($groupByFieldsArray) {
            if (empty($groupByFieldsArray)) {
                return "";
            }
            $ret = 'GROUP BY ';
            if (!is_array($groupByFieldsArray)) {
                $groupByFieldsArray = [$groupByFieldsArray];
            }

            foreach ($groupByFieldsArray as $fieldName) {
                if (strpos($fieldName, '`') === false) {
                    $fieldName = '`' . $fieldName . '`';
                }
                $ret .= $fieldName . ',';
            }
            return trim($ret, ',');
        }

        private function prepare($query, $configParamName = 'mysql') {
            $mysqlConfig = Sys()->getConfig($configParamName);
            if (!empty($mysqlConfig['profiler']) && !empty($mysqlConfig['profiler']['enabled']) &&
                    $this->getTableName() !== $mysqlConfig['profiler']['table_name'] && strpos($query, $mysqlConfig['profiler']['table_name']) === false) {
                $this->insertIntoProfiler(['query' => $query, 'created_at' => \system\util\Util::dateWithMillis()], $mysqlConfig['profiler']['table_name']);
            }
            return $this->getConnector()->prepare($query);
        }

        private function insertIntoProfiler($array, $profilerTableName) {
            $sqlQuery = sprintf("INSERT INTO `%s` SET ", $profilerTableName);
            $availableFields = [];
            foreach ($array as $fieldName => $fieldValue) {
                if (isset($fieldValue)) {
                    $availableFields[$fieldName] = $fieldValue;
                    $sqlQuery .= sprintf(" `%s` = :%s,", $fieldName, $fieldName);
                }
            }

            if (empty($availableFields)) {
                $sqlQuery .= " `id` = NULL";
            }
            $res = $this->prepare(trim($sqlQuery, ','));

            if ($res) {
                $res->execute($availableFields);
                return $this->getConnector()->lastInsertId();
            }
            return null;
        }

        private function mapObjectsByField($recs, $fieldName) {
            $mappedDtos = array();
            foreach ($recs as $rec) {
                $mappedDtos[$rec->$fieldName] = $rec;
            }
            return $mappedDtos;
        }
    }

}

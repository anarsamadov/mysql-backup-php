<?php
// initial settings.
ini_set('date.timezone', 'Europe/London');
ini_set('display_error',  1);

// Define database parameters here
define("DB_USER",     '');
define("DB_PASSWORD", '');
define("DB_NAME",     '');
define("DB_HOST",     '');
define("OUTPUT_DIR",  '');
define("TABLES",      ''); 

$backup = new Backup(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$status = $backup->backupTables(TABLES, OUTPUT_DIR) ? 'OK' : 'NOT OK';

print "Backup result: " . $status . PHP_EOL;

class Backup
{
  var $host      = '';
  var $username  = '';
  var $password  = '';
  var $dbName    = '';
  var $charset   = '';
  var $conn      = '';

  function __construct($host, $username, $password, $dbName, $charset = 'utf8') 
  {
    $this->host     = $host;
    $this->username = $username;
    $this->password = $password;
    $this->dbName   = $dbName;
    $this->charset  = $charset;
    
    $this->initializeDatabase();
  }
  
  private function initializeDatabase() 
  {
    // initialize & connect to database.
    $dsn = "mysql:host=".$this->host.";dbname=".$this->dbName.";charset=".$this->charset;
    try {
      $this->conn = new PDO($dsn, $this->username, $this->password);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(Exception $e) {
      $this->printError($e);
      exit;
    }
  }

  public function backupTables($tables = '*', $outputDir = '.') 
  {
    try {
      // get all tables. or the ones defines.
      if ($tables == '*') {
        $tablesStmt = $this->conn->prepare("SHOW TABLES");
        $tablesStmt->execute();
        $tables = array();
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
      } 
      else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
      }
      
      $sql  = 'CREATE DATABASE IF NOT EXISTS ' . $this->dbName . ";\n\n";
      $sql .= 'USE ' . $this->dbName . ";\n\n";
      
      // for each table
      foreach ($tables as $table) {
        print "Backing up " . $table . " table...";
        
        // get columns of this table
        $tableContentsStmt = $this->conn->prepare('SELECT * FROM ' . $table);
        $tableContentsStmt->execute();
        $columns = $tableContentsStmt->columnCount();
        
        $sql .= 'DROP TABLE IF EXISTS ' . $table . ';';

        // get table creation script. therefore, foreign keys will be kept.
        $tableCreationStmt = $this->conn->prepare('SHOW CREATE TABLE ' . $table);
        $tableCreationStmt->execute();
        $tableCreationScript = $tableCreationStmt->fetchAll(PDO::FETCH_COLUMN, 1)[0];

        $sql .= "\n\n" . $tableCreationScript . ";\n\n";

        // for each row in this table
        while ($row = $tableContentsStmt->fetch(PDO::FETCH_NUM)) {
          // build up an insertion script by iterating through columns.
          $sql .= 'INSERT INTO ' . $table . ' VALUES (';
          for ($column = 0; $column < $columns; $column++) {
            $row[$column] = addslashes($row[$column]);
            $row[$column] = preg_replace("[^\r]\n", "\\n", $row[$column]);

            if (isset($row[$column])) 
              $sql .= '"' . $row[$column] . '"';
            else 
              $sql .= '""';

            if ($column < ($columns - 1)) 
              $sql .= ',';
          }
          $sql .= ");\n";
        }

        $sql .= "\n\n\n";
        print " OK" . PHP_EOL;
      }
    }
    catch (Exception $e) {
      $this->printError($e->getMessage());
      return false;
    }
    
    return $this->saveFile($sql, $outputDir);
  }
  
  private function saveFile(&$sql, $outputDir = '.')
  {
    if (!$sql) return false;
    try {
      $handle = fopen($outputDir . '/db-backup-' . $this->dbName . '-' . date("Ymd-His", time()) . '.sql', 'w+');
      fwrite($handle, $sql);
      fclose($handle);
    }
    catch (Exception $e) {
      $this->printError($e->getMessage());
      return false;
    }
    return true;
  }

  private function printError($e) {
    print "An error occured! Check out following exception message:\n"; 
    print_r($e);
  }

}
<?php
/* 
| --------------------------------------------------------------
| 
| Frostbite Framework
|
| --------------------------------------------------------------
|
| Author: 		Steven Wilson
| Copyright:	Copyright (c) 2011, Steven Wilson
| License: 		GNU GPL v3
|
*/
namespace Database;

class Utilities
{
    protected $DB;

/*
| ---------------------------------------------------------------
| Constructor
| ---------------------------------------------------------------
|
| Creates the connection to the database using PDO
|
*/
    public function __construct($connection)
    {
        $this->DB = $connection;
    }

/*
| ---------------------------------------------------------------
| Function: runSqlFile()
| ---------------------------------------------------------------
|
| Runs a sql file on the database
|
*/
    public function runSqlFile($file)
    {
        // Open the sql file, and add each line to an array
        $handle = @fopen($file, "r");
        if($handle) 
        {
            while(!feof($handle)) 
            {
                $queries[] = fgets($handle);
            }
            fclose($handle);
        }
        else 
        {
            show_error('db_couldnt_open_sqlfile', array($file), E_WARNING);
            return FALSE;
        }
        
        // loop through each line and process it
        foreach ($queries as $key => $aquery) 
        {
            // If the line is empty or a comment, unset it
            if (trim($aquery) == "" || strpos ($aquery, "--") === 0 || strpos ($aquery, "#") === 0) 
            {
                unset($queries[$key]);
                continue;
            }
            
            // Check to see if the query is more then 1 line
            $aquery = rtrim($aquery);
            $compare = rtrim($aquery, ";");
            if($compare != $aquery) 
            {
                $queries[$key] = $compare . "|br3ak|";
            }
        }

        // Combine the query's array into a string, 
        // and explode it back into an array seperating each query
        $queries = implode($queries);
        $queries = explode("|br3ak|", $queries);

        // Process each query
        foreach ($queries as $query) 
        {
            // Dont query if the query is empty
            if(empty($query)) continue;
            $result = $this->DB->exec($query, 0);
            if($result === FALSE) return FALSE;
        }
        return TRUE;
    }
}
// EOF
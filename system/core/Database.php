<?php
/* 
| --------------------------------------------------------------
| Plexis
| --------------------------------------------------------------
| Author:       Steven Wilson 
| Copyright:    Copyright (c) 2011-2012, Plexis Dev Team
| License:      GNU GPL v3
| ---------------------------------------------------------------
| Class: Database
| ---------------------------------------------------------------
|
| Database factory class
|
*/
namespace Core;

// Register our class alias
use \Database\Driver;

class Database
{
    protected static $connections = array();
    
/*
| ---------------------------------------------------------------
| Method: Connect
| ---------------------------------------------------------------
|
| Initiates a new database connection
|
| @Param: (String | Int) $name - Name or ID of the connection
| @Param: (Array) $info - The database connection information
|   array(
|       'driver'
|       'host'
|       'port'
|       'database'
|       'username'
|       'password'
| @Return: (Object) Returns a Database Driver Object
| @Throws: DatabaseConnectError when a connection cannot be created
|
*/ 
    public static function Connect($name, $info, $new = false)
    {
        // If the connection already exists, and $new is false, return existing
        if(isset(self::$connections[$name]) && !$new)
            return self::$connections[$name];
        
        // Init a new connection
        try {
            self::$connections[$name] = new Driver($info);
        }
        catch( \Exception $e ) {
            throw new DatabaseConnectError($e->getMessage());
        }
        
        return self::$connections[$name];
    }
    
/*
| ---------------------------------------------------------------
| Method: GetConnection()
| ---------------------------------------------------------------
|
| Returns the connection object for the given Name or ID
|
| @Return: (Object) Returns the Database Driver Object
|
*/ 
    public static function GetConnection($name)
    {
        if(isset(self::$connections[$name]))
            return self::$connections[$name];
        return false;
    }
}

// Database Connect Exception
class DatabaseConnectError extends \ApplicationError {}

// Register the autoloader, where to find the database driver class
AutoLoader::RegisterNamespace('Database', path(SYSTEM_PATH, "core", "database"));
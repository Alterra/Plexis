<?php
/* 
| --------------------------------------------------------------
| Plexis Core
| --------------------------------------------------------------
| Author:       Steven Wilson
| Copyright:    Copyright (c) 2012, Plexis Dev Team
| License:      GNU GPL v3
| ---------------------------------------------------------------
| Class: Security
| ---------------------------------------------------------------
|
| This class is used for security cleaning of variables
| 
*/
namespace Core;

class Security
{
    // Array of tags and attributes
    protected static $tagsArray = array();
    protected static $attrArray = array();
    
    // Our tag and attribute cleaning methods
    protected static $tagsMethod = 0;
    protected static $attrMethod = 0;
    
    // Out xss cleaning method
    protected static $xssAuto = 1;

/*
| ---------------------------------------------------------------
| Constructor
| ---------------------------------------------------------------
*/
    public static function Init()
    {
        // Load the config file for this
        $path = path( SYSTEM_PATH, 'config', 'security.class.php' );
        if(!Config::Load($path, 'SecurityClass', false, true, false))
            throw new SystemError('Missing Security class configuration file.');
        
        // Add trace for debugging
        // \Debug::trace('Input class initiated successfully', __FILE__, __LINE__);
    }

/*
| ---------------------------------------------------------------
| PHP InputFilter
| ---------------------------------------------------------------
|
| NOTE: The below funtions where not created by myself, All i did
| was update the code and clean it up a bit. Here is the original 
| credits
|
| @project: PHP Input Filter
| @date: 10-05-2005
| @version: 1.2.2_php5
| @author: Daniel Morris
| @updated By: Steven Wilson
| @contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider, Chris Tobin and Andrew Eddie.
| @copyright: Daniel Morris
| @email: dan@rootcube.com
| @license: GNU General Public License (GPL)
|
*/


/*
| ---------------------------------------------------------------
| Method: SetRules
| ---------------------------------------------------------------
|
| Sets the cleaning rules such as allowed tags etc.
|
| @param: (Array) $tagsArray - list of user-defined tags
| @param: (Array) $attrArray - list of user-defined attributes
| @param: (Int) $tagsMethod - 0 = allow just user-defined, 1= allow all but user-defined
| @param: (Int) $attrMethod - 0 = allow just user-defined, 1= allow all but user-defined
| @param: (Int) $xssAuto - 0 = only auto clean essentials, 1= allow clean blacklisted tags/attr
| @Return (None)
|
*/
    public static function SetRules($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1) 
    {	
        // Count how many are in each for out loops
        $countTags = count($tagsArray);
        $countAttr = count($attrArray);
        
        // Loop through and lowercase all Tags
        for($i = 0; $i < $countTags; $i++)
        {
            $tagsArray[$i] = strtolower($tagsArray[$i]);
        }
        
        // Loop through and lowercase all attributes
        for($i = 0; $i < $countAttr; $i++)
        {
            $attrArray[$i] = strtolower($attrArray[$i]);
        }
        
        // Set our class variables
        self::$tagsArray = $tagsArray;
        self::$attrArray = $attrArray;
        self::$tagsMethod = $tagsMethod;
        self::$attrMethod = $attrMethod;
        self::$xssAuto = $xssAuto;
    }

/*
| ---------------------------------------------------------------
| Method: Clean()
| ---------------------------------------------------------------
|
| Main call function. Used to clean user input
|
| @Param: (Mixed) $source - String or array to be cleaned
| @Return (Mixed) Returns the cleaned source of $source
|
*/
    public static function Clean($source) 
    {
        // If in array, clean each value
        if(is_array($source)) 
        {
            foreach($source as $key => $value)
            {
                if(is_string($value)) 
                {
                    // filter element for XSS and other 'bad' code etc.
                    $source[$key] = self::Remove(self::Decode($value));
                }
            }
            return $source;
        } 
        elseif(is_string($source)) 
        {
            // filter element for XSS and other 'bad' code etc.
            return self::Remove(self::Decode($source));
        } 
        return $source;
    }

/*
| ---------------------------------------------------------------
| Method: Remove()
| ---------------------------------------------------------------
|
| Removes all unwanted tags and attributes
|
| @Param: (String) $source - String or array to be cleaned
| @Return (Mixed) Returns the cleaned source of $source
|
*/
    protected static function Remove($source) 
    {
        $loopCounter = 0;
        while($source != self::FilterTags($source)) 
        {
            $source = self::FilterTags($source);
            $loopCounter++;
        }
        return $source;
    }

/*
| ---------------------------------------------------------------
| Method: FilterTags()
| ---------------------------------------------------------------
|
| Internal method to strip a string of certain tags
|
| @Param: (String) $source - String or array to be cleaned
| @Return (Mixed) Returns the cleaned source of $source
|
*/
    protected static function FilterTags($source) 
    {
        $preTag = NULL;
        $postTag = $source;
        
        // find initial tag's position
        $tagOpen_start = strpos($source, '<');
        
        // interate through string until no tags left
        while($tagOpen_start !== false) 
        {
            // process tag interatively
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            $tagOpen_end = strpos($fromTagOpen, '>');
            if($tagOpen_end === false)
            {
                break;
            }
            
            // next start of tag (for nested tag assessment)
            $tagOpen_nested = strpos($fromTagOpen, '<');
            if(($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) 
            {
                $preTag .= substr($postTag, 0, ($tagOpen_nested + 1));
                $postTag = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start = strpos($postTag, '<');
                continue;
            } 
            $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = strlen($currentTag);
            if(!$tagOpen_end) 
            {
                $preTag .= $postTag;
                $tagOpen_start = strpos($postTag, '<');			
            }
            
            // iterate through tag finding attribute pairs - setup
            $tagLeft = $currentTag;
            $attrSet = array();
            $currentSpace = strpos($tagLeft, ' ');
            
            // is end tag
            if(substr($currentTag, 0, 1) == "/") 
            {
                $isCloseTag = TRUE;
                list($tagName) = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
            } 
            
            // is start tag
            else 
            {
                $isCloseTag = false;
                list($tagName) = explode(' ', $currentTag);
            }	

            // excludes all "non-regular" tagnames OR no tagname OR remove if xssauto is on and tag is blacklisted
            if(!preg_match("/^[a-z][a-z0-9]*$/i", $tagName) || !$tagName || ((in_array(strtolower($tagName), Config::GetVar('tagBlacklist', 'SecurityClass'))) && self::$xssAuto)) 
            { 				
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }
            
            // this while is needed to support attribute values with spaces in!
            while($currentSpace !== false) 
            {
                $fromSpace = substr($tagLeft, ($currentSpace+1));
                $nextSpace = strpos($fromSpace, ' ');
                $openQuotes = strpos($fromSpace, '"');
                $closeQuotes = strpos(substr($fromSpace, ($openQuotes+1)), '"') + $openQuotes + 1;
                
                // another equals exists
                if(strpos($fromSpace, '=') !== false) 
                {
                    // opening and closing quotes exists
                    if(($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes+1)), '"') !== false))
                    {
                        $attr = substr($fromSpace, 0, ($closeQuotes+1));
                    }
                    
                    // one or neither exist
                    else 
                    {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }
                
                // no more equals exist
                else
                {
                    $attr = substr($fromSpace, 0, $nextSpace);
                }
                
                // last attr pair
                if(!$attr) 
                {
                    $attr = $fromSpace;
                }
                
                // add to attribute pairs array
                $attrSet[] = $attr;
                
                // next inc
                $tagLeft = substr($fromSpace, strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }
            
            // appears in array specified by user
            $tagFound = in_array(strtolower($tagName), self::$tagsArray);

            // remove this tag on condition			
            if((!$tagFound && self::$tagsMethod || ($tagFound && !self::$tagsMethod)))
            {
                // reconstruct tag with allowed attributes
                if(!$isCloseTag) 
                {
                    $attrSet = self::FilterAttr($attrSet);
                    $preTag .= '<' . $tagName;
                    for($i = 0; $i < count($attrSet); $i++)
                    {
                        $preTag .= ' ' . $attrSet[$i];
                    }
                    
                    // reformat single tags to XHTML
                    if(strpos($fromTagOpen, "</" . $tagName))
                    {
                        $preTag .= '>';
                    }
                    else 
                    {
                        $preTag .= ' />';
                    }
                } 
                
                // just the tagname
                else 
                {
                    $preTag .= '</' . $tagName . '>';
                }
            }
            
            // find next tag's start
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');			
        }
        
        // append any code after end of tags
        $preTag .= $postTag;
        return $preTag;
    }

/*
| ---------------------------------------------------------------
| Method: FilterAttr()
| ---------------------------------------------------------------
|
| Internal method to strip a tag of certain attributes
|
| @Param: (String) $source - String or array to be cleaned
| @Return (Mixed) Returns the cleaned source of $source
|
*/
    protected static function FilterAttr($attrSet) 
    {	
        $newSet = array();
        
        // process attributes
        for($i = 0; $i <count($attrSet); $i++) 
        {
            // skip blank spaces in tag
            if(!$attrSet[$i])
            {
                continue; 
            }
            
            // split into attr name and value
            $attrSubSet = explode('=', trim($attrSet[$i]));
            list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);
            
            // removes all "non-regular" attr names AND also attr blacklisted
            if ((!preg_match("/^[a-z]*$/i", $attrSubSet[0])) || (self::$xssAuto && ((in_array(strtolower($attrSubSet[0]), Config::GetVar('attrBlacklist', 'SecurityClass'))) || (substr($attrSubSet[0], 0, 2) == 'on'))))
            {
                continue;
            }
            
            // xss attr value filtering
            if($attrSubSet[1]) 
            {
                // strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                
                // strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
                
                // strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                
                // [requested feature] convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
                if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'"))
                {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                }
                
                // strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }
            
            // auto strip attr's with "javascript:
            if(	
                ((strpos(strtolower($attrSubSet[1]), 'expression') !== false) && (strtolower($attrSubSet[0]) == 'style')) 
                || (strpos(strtolower($attrSubSet[1]), 'javascript:') !== false)
                || (strpos(strtolower($attrSubSet[1]), 'behaviour:') !== false) 
                || (strpos(strtolower($attrSubSet[1]), 'vbscript:') !== false) 
                || (strpos(strtolower($attrSubSet[1]), 'mocha:') !== false)
                || (strpos(strtolower($attrSubSet[1]), 'livescript:') !== false) 
            ) continue;
            
            // if matches user defined array
            $attrFound = in_array(strtolower($attrSubSet[0]), self::$attrArray);
            
            // keep this attr on condition
            if((!$attrFound && self::$attrMethod) || ($attrFound && !self::$attrMethod)) 
            {
                // attr has value
                if($attrSubSet[1])
                {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
                }
                
                // attr has decimal zero as value
                elseif($attrSubSet[1] == "0")
                {
                    $newSet[] = $attrSubSet[0] . '="0"';
                }
                
                // reformat single attributes to XHTML
                else
                {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
                }
            }	
        }
        return $newSet;
    }

/*
| ---------------------------------------------------------------
| Method: Decode()
| ---------------------------------------------------------------
|
| Converts to plain text
|
| @Param: (String) $source - String to be converted
| @Return (Mixed) Returns the cleaned source of $source
|
*/
    protected static function Decode($source) 
    {
        $source = html_entity_decode($source, ENT_QUOTES, "ISO-8859-1");
        $source = preg_replace('/&#(\d+);/me',"chr(\\1)", $source);
        $source = preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)", $source);
        return $source;
    }
}

// Init the class
Security::Init();

// EOF 
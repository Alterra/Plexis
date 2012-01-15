<?php
class Ajax_Model extends Application\Core\Model 
{

/*
| ---------------------------------------------------------------
| Constructer
| ---------------------------------------------------------------
|
*/
    function __construct()
    {
        parent::__construct();
    }
    
/*
| ---------------------------------------------------------------
| Method: command_string
| ---------------------------------------------------------------
|
*/
    function command_string($command, $type)
    {
        $command = trim($command);
        switch($type)
        {
            case "login":
                $command = explode(' ', $command);
                $command[0] = "<span class=\"c_keyword\">".$command[0]."</span>";
                $chars = str_split($command[2]);
                $command[2] = '';
                
                // Loop through each character in the pass and replace with "*"
                foreach($chars as $l)
                {
                    $command[2] .= '*';
                }
                $return = implode(' ', $command);
                break;
                
            case "send":
            case "character":  
            case "server":   
            case "ticket":
            case "unban":
            case "ban":
            case "banlist":
            case "guild":
            case "list":
            case "lookup":
            case "reset":
                $command = explode(' ', $command);
                $command[0] = "<span class=\"c_keyword\">".$command[0]."</span>";
                $command[1] = "<span class=\"c_keyword\">".$command[1]."</span>";
                $return = implode(' ', $command);
                break;
                
            case "account":
            case "reload":
                $command = explode(' ', $command);
                $command[0] = "<span class=\"c_keyword\">".$command[0]."</span>";
                
                if(isset($command[1]))
                {
                    if($command[1] == 'set' && isset($command[2])) $command[2] = "<span class=\"c_keyword\">".$command[2]."</span>";
                    $command[1] = "<span class=\"c_keyword\">".$command[1]."</span>";
                }
                $return = implode(' ', $command);
                break;
                
            default:
                $command = explode(' ', $command);
                $command[0] = "<span class=\"c_keyword\">".$command[0]."</span>";
                $return = implode(' ', $command);
        }
        
        return $return;
    }

/*
| ---------------------------------------------------------------
| Method: process()
| ---------------------------------------------------------------
|
| Returns an array for the DataTables JS script
|
| @Param: (Array) $aColumns - The array of DB columns to process
| @Param: (Array) $sIndexColumn - The index column such as "id"
| @Param: (Array) $sTable - The table we are query'ing
| @Return (Array)
|
*/    
    public function process_datatables($aColumns, $sIndexColumn, $sTable, $dB_key = 'DB')
    {
        /* 
         * Paging
         */
        $sLimit = "";
        if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
        {
            $sLimit = "LIMIT ".addslashes( $_POST['iDisplayStart'] ).", ".
                addslashes( $_POST['iDisplayLength'] );
        }
        
        
        /*
         * Ordering
         */
        if ( isset( $_POST['iSortCol_0'] ) )
        {
            $sOrder = "ORDER BY  ";
            for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
            {
                if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
                {
                    $sOrder .= $aColumns[ intval( $_POST['iSortCol_'.$i] ) ]."
                        ".addslashes( $_POST['sSortDir_'.$i] ) .", ";
                }
            }
            
            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" )
            {
                $sOrder = "";
            }
        }
        
        
        /* 
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = "";
        if ( $_POST['sSearch'] != "" )
        {
            $sWhere = "WHERE (";
            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                $sWhere .= $aColumns[$i]." LIKE '%".addslashes( $_POST['sSearch'] )."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }
        
        /* Individual column filtering */
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( $_POST['bSearchable_'.$i] == "true" && $_POST['sSearch_'.$i] != '' )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = "WHERE ";
                }
                else
                {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i]." LIKE '%".addslashes($_POST['sSearch_'.$i])."%' ";
            }
        }
        
        
        /*
         * SQL queries
         * Get data to display
         */
        $columns = str_replace(" , ", " ", implode(", ", $aColumns));
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS {$columns} FROM {$sTable} {$sWhere} {$sOrder} {$sLimit}";
        $rResult = $this->$dB_key->query( $sQuery )->fetch_array('BOTH');
        
        /* Data set length after filtering */
        $iFilteredTotal = $this->$dB_key->query( "SELECT FOUND_ROWS()" )->fetch_column();
        
        /* Total data set length */
        $iTotal = $this->$dB_key->query( "SELECT COUNT(".$sIndexColumn.") FROM   $sTable" )->fetch_column();
        
        
        /*
         * Output
         */
        $output = array(
            "sEcho" => intval($_POST['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
        
        foreach( $rResult as $aRow )
        {
            $row = array();
            for ( $i=0; $i < count($aColumns); $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {
                    /* Special output formatting for 'version' column */
                    $row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
                }
                else if ( $aColumns[$i] != ' ' )
                {
                    /* General output */
                    $row[] = $aRow[ $aColumns[$i] ];
                }
            }
            $output['aaData'][] = $row;
        }
        
        return $output;
    }
    
/*
| ---------------------------------------------------------------
| Method: process()
| ---------------------------------------------------------------
|
| Returns an array for the DataTables JS script
|
| @Param: (Array) $aColumns - The array of DB columns to process
| @Param: (Array) $sIndexColumn - The index column such as "id"
| @Param: (Array) $sTable - The table we are query'ing
| @Return (Array)
|
*/    
    public function get_characters_online($aColumns, $sIndexColumn, $sTable, $DB)
    {
        /* 
         * Paging
         */
        $sLimit = "";
        if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
        {
            if(is_numeric($_POST['iDisplayStart']) && is_numeric($_POST['iDisplayLength']))
            {
                $sLimit = "LIMIT ".addslashes( $_POST['iDisplayStart'] ).", ". addslashes( $_POST['iDisplayLength'] );
            }
        }
        
        
        /*
         * Ordering
         */
         $sOrder = "";
        if ( isset( $_POST['iSortCol_0'] ) )
        {
            $sOrder = "ORDER BY  ";
            for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
            {
                if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
                {
                    $sOrder .= $aColumns[ intval( $_POST['iSortCol_'.$i] ) ]." ".addslashes( $_POST['sSortDir_'.$i] ) .", ";
                }
            }
            
            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" )
            {
                $sOrder = "";
            }
        }
        
        
        /* 
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = "";
        if ( isset($_POST['sSearch']) &&  $_POST['sSearch'] != "" )
        {
            $sWhere = "WHERE (";
            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                $sWhere .= $aColumns[$i]." LIKE '%".addslashes( $_POST['sSearch'] )."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }
        
        /* Individual column filtering */
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( isset($_POST['bSearchable_'.$i]) && $_POST['bSearchable_'.$i] == "true" && $_POST['sSearch_'.$i] != '' )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = "WHERE ";
                }
                else
                {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i]." LIKE '%".addslashes($_POST['sSearch_'.$i])."%' ";
            }
        }
        
        // == EXTRA characters online processing! == //
        if($sWhere == '')
        {
            $sWhere = ' WHERE `online`=1';
        }
        else
        {
            $sWhere = ' AND `online`=1';
        }
        
        
        /*
         * SQL queries
         * Get data to display
         */
        $columns = str_replace(" , ", " ", implode(", ", $aColumns));
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS {$columns} FROM {$sTable} {$sWhere} {$sOrder} {$sLimit}";
        $rResult = $DB->query( $sQuery )->fetch_array('BOTH');
        
        /* Data set length after filtering */
        $iFilteredTotal = $DB->query( "SELECT FOUND_ROWS()" )->fetch_column();
        
        /* Total data set length */
        $iTotal = $DB->query( "SELECT COUNT(".$sIndexColumn.") FROM   $sTable" )->fetch_column();
        
        
        /*
         * Output
         */
         $sEcho = (isset($_POST['sEcho'])) ? $_POST['sEcho'] : 1;
        $output = array(
            "sEcho" => intval($sEcho),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
        
        foreach( $rResult as $aRow )
        {
            $row = array();
            for ( $i=0; $i < count($aColumns); $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {
                    /* Special output formatting for 'version' column */
                    $row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
                }
                else if ( $aColumns[$i] != ' ' )
                {
                    /* General output */
                    $row[] = $aRow[ $aColumns[$i] ];
                }
            }
            $output['aaData'][] = $row;
        }
        
        return $output;
    }
}
// EOF
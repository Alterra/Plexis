$().ready(function() {
    /**
     * DataTables
     */
    $('#data-table').dataTable({
        "bServerSide": true,
        "bSortClasses": false,
        "sAjaxSource": Plexis.url + "/ajax/users",
        "fnServerData": function ( sSource, aoData, fnCallback ) {
            $.ajax( {
                "dataType": 'json', 
                "type": "POST", 
                "url": sSource, 
                "data": aoData, 
                "success": fnCallback
            } );
        }
    });
})
jQuery( document ).ready( function () {

    jQuery( "#bptodo-tabs" ).tabs({ heightStyle: "content" });
    jQuery( "#bptodo-task-tabs" ).tabs({ heightStyle: "content" });

    var acc = document.getElementsByClassName( "bptodo-item" );
    var i;
    for ( i = 0; i < acc.length; i++ ) {
        if ( i == 0 ) {
            var panel = acc[i].nextElementSibling;
            var first_child = jQuery(acc[i]);
            if ( panel.style.maxHeight ) {
                panel.style.maxHeight = null;
                first_child.removeClass('active');
            } else {
                first_child.addClass('active');
                panel.style.maxHeight = panel.scrollHeight + "px";
            }
        }
        acc[i].onclick = function () {
            var panel = this.nextElementSibling;
            if ( panel.style.maxHeight ) {
                panel.style.maxHeight = null;
                jQuery(this).removeClass('active');
            } else {
                jQuery(this).addClass('active');
                panel.style.maxHeight = panel.scrollHeight + "px";
            }
        }
    }

    jQuery( document ).on( 'click', '.bptodo-accordion', function(){
        return false;
    });

    //Datepicker
    jQuery( '.todo_due_date' ).datepicker( { dateFormat: 'yy-mm-dd', minDate: 0 } );

    //Export My Tasks
    jQuery( document ).on( 'click', '#export_my_tasks', function () {
        jQuery( '#export_my_tasks' ).html( '<i class="fa fa-refresh fa-spin"></i>' );
        var security_nonce = jQuery( '#bptodo-export-todo-nonce' ).val();
        jQuery.post(
            ajaxurl,
            {
                'action'            : 'bptodo_export_my_tasks',
                'security_nonce'    : security_nonce
            },
            function ( response ) {
                jQuery( '#export_my_tasks' ).html( '<i class="fa fa-download" aria-hidden="true"></i>' );
                tasks = response;
                JSONToCSVConvertor( tasks, todo_ajax_object.export_file_heading, true );
            },
            "JSON"
            );
    } );

    //Add BP Todo Category Show Row
    jQuery( document ).on( 'click', '.add-todo-category', function(){
        jQuery( '.add-todo-cat-row' ).slideToggle( 'slow' );
        var element_height = jQuery('.add-todo-cat-row').css('height').match(/\d+/);
        if( element_height[0] > 5 ) {
            jQuery('.add-todo-category i').attr('class', 'fa fa-plus');
        } else {
            jQuery('.add-todo-category i').attr('class', 'fa fa-minus');
        }

    });

    //Add BP Todo Category
    jQuery( document ).on( 'click', '#add-todo-cat', function(){
        var name = jQuery( '#todo-category-name' ).val();
        var btn_text = jQuery(this).html();
        if ( name == '' ) {
            jQuery( '#todo-category-name' ).addClass( 'bptodo-add-cat-empty' ).attr( 'placeholder', todo_ajax_object.required_cat_text );
        } else {
            var security_nonce = jQuery( '#bptodo-add-category-nonce' ).val();
            jQuery( this ).html( btn_text+' <i class="fa fa-refresh fa-spin"></i>' );
            jQuery.post(
                ajaxurl,
                {
                    'action'            : 'bptodo_add_todo_category_front',
                    'name'              : name,
                    'security_nonce'    : security_nonce
                },
                function ( response ) {
                    if ( response == 'todo-category-added' ) {
                        var html = '<option value="' + name + '" selected>' + name + '</option>';
                        jQuery( '#bp_todo_categories' ).append( html );
                        jQuery( '.add-todo-cat-row' ).hide();
                        jQuery( '#add-todo-cat' ).html( btn_text );
                        jQuery( '#todo-category-name' ).val('');
                        jQuery('.add-todo-category i').attr('class', 'fa fa-plus');
                    }
                }
            );
        }
    });

    //Remove a todo
    jQuery( document ).on( 'click', '.bptodo-remove-todo', function () {
        if ( confirm( 'Are you sure?' ) ) {
            var tid = jQuery( this ).data( 'tid' );
            var row = jQuery(this).closest('tr');
            jQuery( this ).html( '<i class="fa fa-refresh fa-spin"></i>' );

            jQuery.post(
                ajaxurl,
                {
                    'action': 'bptodo_remove_todo',
                    'ajax_nonce' : todo_ajax_object.ajax_nonce,
                    'tid': tid,
                },
                function ( response ) {
                    var siblings = row.siblings();
                    if ( response == 'todo-removed' ) {
                        jQuery( '#bptodo-row-' + tid ).remove();
                    }
                }
                );
        }
    } );

    //Complete a todo
    jQuery( document ).on( 'click', '.bptodo-complete-todo', function () {
        var clicked_tid = jQuery( this );
        var tid = jQuery( this ).data( 'tid' );
        var completed_todo = jQuery('.bp_completed_todo_count').text();
        var all_todo = jQuery('.bp_all_todo_count').text();
        jQuery( this ).html( '<i class="fa fa-refresh fa-spin"></i>' );
        jQuery.post(
            ajaxurl,
            {
                'action': 'bptodo_complete_todo',
                'ajax_nonce' : todo_ajax_object.ajax_nonce,
                'tid': tid,
                'completed': completed_todo,
                'all_todo': all_todo
            },
            function ( response ) {
                var response = JSON.parse( response );
                if ( response.result == 'todo-completed' ) {
                    clicked_tid.closest( 'tr' ).find( "td" ).addClass( 'todo-completed' );
                    clicked_tid.closest( 'td' ).prev( 'td' ).text( response.due_date_str );
                    jQuery('.bp_completed_todo_count').text( response.completed_todo );
                    jQuery('#bptodo-completed tbody').append( response.completed_html );
                    jQuery('.bptodo-color').css('width', response.avg_percentage+'%');
                    jQuery('.bptodo-light-grey b').text( response.avg_percentage+'%');
                    clicked_tid.closest( 'td' ).html( '<ul><li><a href="" class="bptodo-undo-complete-todo" data-tid="' + tid + '" title=" '+ todo_ajax_object.undo_todo_title +'"><i class="fa fa-undo"></i></a></li></ul>' )
                }
            }
            );
    } );

    //Undo complete a todo
    jQuery( document ).on( 'click', '.bptodo-undo-complete-todo', function (event) {
        event.preventDefault();
        var tid = jQuery( this ).data( 'tid' );
        jQuery( this ).html( '<i class="fa fa-refresh fa-spin"></i>' );
        jQuery.post(
            ajaxurl,
            {
                'action': 'bptodo_undo_complete_todo',
                'ajax_nonce' : todo_ajax_object.ajax_nonce,
                'tid': tid,
            },
            function ( response ) {
                if ( response == 'todo-undo-completed' ) {
                    location.reload();
                }
            }
        );
    } );
} );

function JSONToCSVConvertor( JSONData, ReportTitle, ShowLabel ) {
    var arrData = typeof JSONData != 'object' ? JSON.parse( JSONData ) : JSONData;
    var CSV = '';
    CSV += ReportTitle + '\r\n\n';
    if ( ShowLabel ) {
        var row = "";
        for ( var index in arrData[0] ) {
            row += index + ',';
        }
        row = row.slice( 0, -1 );
        CSV += row + '\r\n';
    }

    //1st loop is to extract each row
    for ( var i = 0; i < arrData.length; i++ ) {
        var row = "";
        for ( var index in arrData[i] ) {
            row += '"' + arrData[i][index] + '",';
        }
        row.slice( 0, row.length - 1 );
        //add a line break after each row
        CSV += row + '\r\n';
    }

    if ( CSV == '' ) {
        alert( "Invalid data" );
        return;
    }

    var fileName = ReportTitle.replace( / /g, "_" );
    var uri = 'data:text/csv;charset=utf-8,' + escape( CSV );
    var link = document.createElement( "a" );
    link.href = uri;
    link.style = "visibility:hidden";
    link.download = fileName + ".csv";
    document.body.appendChild( link );
    link.click();
    document.body.removeChild( link );
}

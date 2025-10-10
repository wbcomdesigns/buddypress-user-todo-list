jQuery(document).ready(function () {
  // add active class on all todo
  jQuery("#bptodo-completed").css('display', 'none');
  jQuery('.all-bp-todo-list-tab').addClass('active');
  jQuery(document).on('click', '.all-bp-todo-list-tab-completed', function () {
    jQuery('.all-bp-todo-list-tab').removeClass('active');
    jQuery(this).addClass('active');
    jQuery('#bptodo-all').css('display', 'none');
    jQuery("#bptodo-completed").css('display', 'block');
  });
  jQuery(document).on('click', '.all-bp-todo-list-tab', function () {
    jQuery('.all-bp-todo-list-tab-completed').removeClass('active');
    jQuery(this).addClass('active');
    jQuery("#bptodo-completed").css('display', 'none');
    jQuery('#bptodo-all').css('display', 'block');
  });
  // end add active class on all todo

  // open popup on click
  jQuery(document).on('click', '.trigger', function () {
    var tid = jQuery(this).data('tid');
    var guid = jQuery(this).data('guid');
    
    var component_type = jQuery(this).data('type');    
      jQuery.ajax({
        url: ajaxurl,
        type: 'post',
        data: {
          action: 'bptodo_edit_form_popup',
          t_id: tid,
          gu_id: guid,
          component: component_type,
          ajax_nonce: todo_ajax_object.ajax_nonce,
        },
        success: function (response) {
          jQuery('.edit_form_popup').html(response);
        }
      });   
  });

  // update todo on popup
  jQuery('.bptodo_edit_form_popup_class').on('click', '#todo_update', function (e) {
    e.preventDefault();
    jQuery('#bptodo_edit_form_popup').trigger('submit');
  });
  jQuery(document).on('submit', '#bptodo_edit_form_popup', function (e) {
    e.preventDefault();
    let editFormValue = jQuery('#bptodo_edit_form_popup').serializeArray();
    jQuery.ajax({
      url: ajaxurl,
      type: 'post',
      data: {
        action: 'bptodo_update_form_popup',
        update_form_data: editFormValue,
        ajax_nonce: todo_ajax_object.ajax_nonce,
      },
      success: function (response) {
        location.reload();
      }
    });
  });


  jQuery('.bptodo_edit_form_popup_class').on('click', '#group_todo_update', function (e) {
    e.preventDefault();
    jQuery('#bptodo_edit_form_popup').trigger('submit');
  });
  jQuery(document).on('submit', '#bptodo_edit_form_popup', function (e) {
    e.preventDefault();
    let editFormValue = jQuery('#bptodo_edit_form_popup').serializeArray();
    jQuery.ajax({
      url: ajaxurl,
      type: 'post',
      data: {
        action: 'bptodo_update_form_popup',
        update_form_data: editFormValue,
        ajax_nonce: todo_ajax_object.ajax_nonce,
      },
      success: function (response) {
        location.reload();
      }
    });
  });

  function bptodo_open_close_on_category() {
    var acc = document.getElementsByClassName("bptodo-item");
    var i;
    for (i = 0; i < acc.length; i++) {
      if (i == 0) {
        var panel = acc[i].nextElementSibling;
        var first_child = jQuery(acc[i]);
        if (panel.style.maxHeight) {
          panel.style.maxHeight = null;
          first_child.removeClass('active');
        } else {
          first_child.addClass('active');
          panel.style.maxHeight = panel.scrollHeight + "px";
        }
      }
      acc[i].onclick = function () {
        var panel = this.nextElementSibling;
        if (panel.style.maxHeight) {
          panel.style.maxHeight = null;
          jQuery(this).removeClass('active');
          jQuery(this).removeClass('bptodo-sameday-open');
        } else {
          jQuery(this).addClass('active');
          jQuery(this).addClass('bptodo-sameday-open');
          panel.style.maxHeight = panel.scrollHeight + "px";
        }
      }
    }
  }
  bptodo_open_close_on_category();

  jQuery(document).on('click', '.bptodo-accordion', function () {
    return false;
  });

  //Datepicker
  jQuery('.todo_due_date').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: 0
  });

  //Export My Tasks
  jQuery(document).on('click', '#export_my_tasks', function () {
    jQuery('#export_my_tasks').html('<div class="reload-spin"></div> Export');
    var security_nonce = jQuery('#bptodo-export-todo-nonce').val();
    jQuery.post(
      ajaxurl, {
      'action': 'bptodo_export_my_tasks',
      'security_nonce': security_nonce
    },
      function (response) {
        jQuery('#export_my_tasks').html('<div class="export-download"></div> Export');
        tasks = response;
        JSONToCSVConvertor(tasks, todo_ajax_object.export_file_heading, true);
      },
      "JSON"
    );
  });

  //Add BP Todo Category Show Row
  jQuery(document).on('click', '.add-todo-category', function () {
    jQuery('.add-todo-cat-row').slideToggle('slow');
    var element_height = jQuery('.add-todo-cat-row').css('height').match(/\d+/);
    if (element_height[0] > 5) {
      jQuery('.add-todo-category i').attr('class', 'fa fa-plus');
    } else {
      jQuery('.add-todo-category i').attr('class', 'fa fa-minus');
    }

  });

  // on popup add cat row
  jQuery('.edit_form_popup').on('click', '.add-todo-category', function () {
    jQuery('.add-todo-cat-row').slideToggle('slow');
    var element_height = jQuery('.add-todo-cat-row').css('height').match(/\d+/);
    if (element_height[0] > 5) {
      jQuery('span').removeClass('remove-cat-icon');
      jQuery('span').addClass('add-cat-icon');
    } else {
      jQuery('span').removeClass('add-cat-icon');
      jQuery('span').addClass('remove-cat-icon');
    }

  });
  // on popup add cat row

  //Add BP Todo Category
  jQuery(document).on('click', '#add-todo-cat', function () {
    var name = jQuery('#todo-category-name').val();
    var btn_text = jQuery(this).html();
    if (name == '') {
      jQuery('#todo-category-name').addClass('bptodo-add-cat-empty').attr('placeholder', todo_ajax_object.required_cat_text);
    } else {
      var security_nonce = jQuery('#bptodo-add-category-nonce').val();
      jQuery(this).html(btn_text + ' <i class="fa fa-refresh fa-spin"></i>');
      jQuery.post(
        ajaxurl, {
        'action': 'bptodo_add_todo_category_front',
        'name': name,
        'security_nonce': security_nonce
      },
        function (response) {
          if (response == 'todo-category-added') {
            var html = '<option value="' + name + '" selected>' + name + '</option>';
            jQuery('#bp_todo_categories').append(html);
            jQuery('.add-todo-cat-row').hide();
            jQuery('#add-todo-cat').html(btn_text);
            jQuery('#todo-category-name').val('');
            jQuery('.add-todo-category i').attr('class', 'fa fa-plus');
          }
        }
      );
    }
  });

  //Add BP Todo Category on popup
  jQuery('.edit_form_popup').on('click', '#add-todo-cat', function () {
    var name = jQuery('#todo-category-name').val();
    var btn_text = jQuery(this).html();
    if (name == '') {
      jQuery('#todo-category-name').addClass('bptodo-add-cat-empty').attr('placeholder', todo_ajax_object.required_cat_text);
    } else {
      var security_nonce = jQuery('#bptodo-add-category-nonce').val();
      jQuery(this).html(btn_text + ' <i class="fa fa-refresh fa-spin"></i>');
      jQuery.post(
        ajaxurl, {
        'action': 'bptodo_add_todo_category_front',
        'name': name,
        'security_nonce': security_nonce
      },
        function (response) {
          if (response == 'todo-category-added') {
            var html = '<option value="' + name + '" selected>' + name + '</option>';
            jQuery('#bp_todo_categories').append(html);
            jQuery('.add-todo-cat-row').hide();
            jQuery('#add-todo-cat').html(btn_text);
            jQuery('#todo-category-name').val('');
            jQuery('.add-todo-category i').attr('class', 'fa fa-plus');
          }
        }
      );
    }
  });

  //Remove a todo
  jQuery(document).on('click', '.bptodo-remove-todo', function () {
    if (confirm(todo_ajax_object.remove_todo_text)) {
      var tid = jQuery(this).data('tid');
      var row = jQuery(this).closest('tr');
      jQuery(this).html('<i class="fa fa-refresh fa-spin"></i>');

      jQuery.post(
        ajaxurl, {
        'action': 'bptodo_remove_todo',
        'ajax_nonce': todo_ajax_object.ajax_nonce,
        'tid': tid,
      },
        function (response) {
          var siblings = row.siblings();
          if (response == 'todo-removed') {
            jQuery('#bptodo-row-' + tid).remove();
          }
        }
      );
    }
  });

  //Complete a todo
  jQuery(document).on('click', '.bptodo-complete-todo', function (e) {
    // Check if checkbox is disabled (read-only task)
    var checkbox = jQuery(this).find('input[type="checkbox"]');
    if (checkbox.prop('disabled')) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    var clicked_tid = jQuery(this);
    var tid = jQuery(this).data('tid');
    var uid = jQuery(this).data('uid');
    var completed_todo = jQuery('.bp_completed_todo_count').text();
    var all_todo = jQuery('.bp_all_todo_count').text();

    // Handle checkbox-based completion (member/group lists)
    if (jQuery(this).hasClass('todo-complete')) {
      markUncompleteTodo(tid);
      jQuery(this).removeClass('todo-uncomplete');
      jQuery(this).addClass('todo-complete');
      return;
    }

    // Handle both checkbox-based and link-based completion
    if (jQuery(this).hasClass('todo-uncomplete') || (!jQuery(this).hasClass('todo-complete') && jQuery(this).is('a'))) {
      jQuery(this).html('<i class="fa fa-refresh fa-spin"></i>');
      jQuery.post(
        ajaxurl, {
        'action': 'bptodo_complete_todo',
        'ajax_nonce': todo_ajax_object.ajax_nonce,
        'tid': tid,
        'uid': uid,
        'completed': completed_todo,
        'all_todo': all_todo
      },
        function (response) {
          var response = JSON.parse(response);
          if (response.result === 'todo-completed') {
            // Update UI for checkbox-based lists
            if (clicked_tid.closest('tr').length) {
              clicked_tid.closest('tr').find("td").addClass('todo-completed');
              clicked_tid.closest('td').prev('td').text(response.due_date_str);
            }

            // Update counters and progress bar
            jQuery('.bp_completed_todo_count').text(response.completed_todo);
            jQuery('#bptodo-completed tbody').append(response.completed_html);
            jQuery('.bptodo-color').css('width', response.avg_percentage + '%');
            jQuery('.bptodo-light-grey b').text(response.avg_percentage + '%');

            // For link-based completion (shortcode), replace with undo link
            if (clicked_tid.is('a')) {
              clicked_tid.closest('li').replaceWith('<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="' + tid + '" title="' + todo_ajax_object.undo_todo_title + '"><i class="fa fa-undo"></i></a></li>');
            } else {
              // For checkbox-based completion
              clicked_tid.closest('td').html('<ul><li><a href="" class="bptodo-undo-complete-todo" data-tid="' + tid + '" title=" ' + todo_ajax_object.undo_todo_title + '"><i class="fa fa-undo"></i></a></li></ul>');
            }

            bptodo_reload_url();
          }
        }
      );
      jQuery(this).addClass('todo-uncomplete');
      jQuery(this).removeClass('todo-complete');
    }
  });

  //Undo complete a todo
  jQuery(document).on('click', '.bptodo-undo-complete-todo', function (e) {
    e.preventDefault();
    var clicked_link = jQuery(this);
    var tid = jQuery(this).data('tid');

    clicked_link.html('<i class="fa fa-refresh fa-spin"></i>');

    jQuery.post(
      ajaxurl, {
      'action': 'bptodo_undo_complete_todo',
      'ajax_nonce': todo_ajax_object.ajax_nonce,
      'tid': tid,
    },
      function (response) {
        if (response == 'todo-undo-completed') {
          bptodo_reload_url();
        }
      }
    );
  });

  function bptodo_reload_url() {
    jQuery.ajax({
      url: todo_ajax_object.bptodo_complete_url,
      success: function (data) {
        let tabledata = jQuery(data).find('.bptodo-adming-setting')[0].innerHTML;
        jQuery('.bptodo-adming-setting').html(tabledata);
        jQuery('#bptodo-completed').css('display', 'none');
        jQuery('.bptodo-item').trigger('click');
        jQuery('.bptodo-panel').each(function () {
          let height = jQuery(this)[0].scrollHeight;
          jQuery(this).css('max-height', height);
        });
        bptodo_open_close_on_category();
        const buttons = document.querySelectorAll('.trigger[data-modal-trigger]');
        for (let button of buttons) {
          modalEvent(button);
        }
      }
    })
  }

  //Undo complete a todo
  function markUncompleteTodo(tid) {
    jQuery(this).html('<i class="fa fa-refresh fa-spin"></i>');
    jQuery.post(
      ajaxurl, {
      'action': 'bptodo_undo_complete_todo',
      'ajax_nonce': todo_ajax_object.ajax_nonce,
      'tid': tid,
    },
      function (response) {
        if (response == 'todo-undo-completed') {
          bptodo_reload_url();
        }
      }
    );
  }

  if (jQuery('#todo_group_members').length >= 1) {
    jQuery('#todo_group_members').select2({
      placeholder: jQuery('#todo_group_members').attr('placeholder'),
      allowClear: true
    });
  }

  // Function to initialize the DataTable with optional todoId parameter
  function initializeTodoTable(todoId = null) {
    jQuery('#bp-todo-table').DataTable({
      destroy: true, // Allow reinitialization
      ajax: {
        url: ajaxurl,
        data: {
          action: 'get_group_todo_report',
          group: jQuery('#bp-todo-table').data('group'),
          ajax_nonce: todo_ajax_object.ajax_nonce,
          todo_id: todoId  // Pass selected todo ID if available
        },
      },
      columns: [
        { data: 'Members', className: 'first_name' },
        { data: 'Status', className: 'last_name' },
        { data: 'Time', className: 'course' },
      ],
      layout: {
        topStart: {
          buttons: ['excelHtml5', 'csvHtml5', 'pdfHtml5']
        }
      },
      language: {
        'emptyTable': todo_ajax_object.localized.empty_table,
        'paginate': {
          'first': todo_ajax_object.localized.paginate_first,
          'last': todo_ajax_object.localized.paginate_last,
          'next': todo_ajax_object.localized.paginate_next,
          'previous': todo_ajax_object.localized.paginate_previous
        },
        'search': todo_ajax_object.localized.search,
        'lengthMenu': todo_ajax_object.localized.show_entries,
        'info': todo_ajax_object.localized.pagination_info,
        'infoEmpty': todo_ajax_object.localized.pagination_empty_info
      },
      paging: true
    });
  }

  // Initialize DataTable on page load with default data
  jQuery(document).ready(function () {
    var todoSelect = document.getElementById('todo-select');
    if( todoSelect ) {
      initializeTodoTable(todoSelect.value);
    }
    
  });

  // Reload DataTable when a new option is selected from the dropdown
  jQuery('#todo-select').on('change', function () {
    const selectedTodoId = jQuery(this).val();
    initializeTodoTable(selectedTodoId); // Call function with selected todo ID
  });

  // Toggle member breakdown in group overview
  jQuery(document).on('click', '.bptodo-toggle-members', function (e) {
    e.preventDefault();
    const button = jQuery(this);
    const taskId = button.data('task-id');
    const membersList = jQuery('#members-' + taskId);
    const icon = button.find('.dashicons');

    if (membersList.is(':visible')) {
      membersList.slideUp(200);
      icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
    } else {
      membersList.slideDown(200);
      icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
    }
  });

  // Filter members in single task report
  jQuery(document).on('click', '.bptodo-filter-btn', function (e) {
    e.preventDefault();
    const filter = jQuery(this).data('filter');

    // Update active state
    jQuery('.bptodo-filter-btn').removeClass('active');
    jQuery(this).addClass('active');

    // Filter member items
    if (filter === 'all') {
      jQuery('.bptodo-member-item').fadeIn(200);
    } else if (filter === 'completed') {
      jQuery('.bptodo-member-item').hide();
      jQuery('.bptodo-member-item.status-complete').fadeIn(200);
    } else if (filter === 'pending') {
      jQuery('.bptodo-member-item').hide();
      jQuery('.bptodo-member-item.status-incomplete').fadeIn(200);
    }
  });

});

function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel) {
  var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;
  var CSV = '';
  CSV += ReportTitle + '\r\n\n';
  if (ShowLabel) {
    var row = "";
    for (var index in arrData[0]) {
      row += index + ',';
    }
    row = row.slice(0, -1);
    CSV += row + '\r\n';
  }

  //1st loop is to extract each row
  for (var i = 0; i < arrData.length; i++) {
    var row = "";
    for (var index in arrData[i]) {
      row += '"' + arrData[i][index] + '",';
    }
    row.slice(0, row.length - 1);
    //add a line break after each row
    CSV += row + '\r\n';
  }

  if (CSV == '') {
    alert("Invalid data");
    return;
  }

  var fileName = ReportTitle.replace(/ /g, "_");
  var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);
  var link = document.createElement("a");
  link.href = uri;
  link.style = "visibility:hidden";
  link.download = fileName + ".csv";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}


const buttons = document.querySelectorAll('.trigger[data-modal-trigger]');


for (let button of buttons) {
  modalEvent(button);
}

function modalEvent(button) {
  button.addEventListener('click', () => {
    const modal = document.querySelector("[data-modal=trigger]");
    const contentWrapper = modal.querySelector('.content-wrapper');
    const close = modal.querySelector('.close');

    close.addEventListener('click', () => modal.classList.remove('open'));
    modal.addEventListener('click', () => modal.classList.remove('open'));
    contentWrapper.addEventListener('click', (e) => e.stopPropagation());

    modal.classList.toggle('open');
  });
}
// Task selector dropdown change handler
jQuery(document).on('change', '#task-selector', function(e) {
    const selectedUrl = jQuery(this).val();
    if (selectedUrl) {
        window.location.href = selectedUrl;
    }
});

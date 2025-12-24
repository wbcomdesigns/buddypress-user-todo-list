/**
 * BuddyPress User Todo List - Frontend JavaScript
 *
 * Handles all todo interactions with modern UX:
 * - No page reloads (DOM updates instead)
 * - Toast notifications for feedback
 * - Modern confirm dialogs
 * - Loading states
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

jQuery(document).ready(function($) {
  'use strict';

  // Ensure BPTodo module is available
  var BPTodo = window.BPTodo || {};

  /**
   * Tab switching functionality
   * Works with both legacy table-based and modern card-based layouts
   */
  function initTabs() {
    // Legacy tab handling (table-based)
    $('#bptodo-completed').css('display', 'none');
    $('.all-bp-todo-list-tab').addClass('active');

    $(document).on('click', '.all-bp-todo-list-tab-completed', function() {
      $('.all-bp-todo-list-tab').removeClass('active');
      $(this).addClass('active');
      $('#bptodo-all').css('display', 'none');
      $('#bptodo-completed').css('display', 'block');
    });

    $(document).on('click', '.all-bp-todo-list-tab', function() {
      $('.all-bp-todo-list-tab-completed').removeClass('active');
      $(this).addClass('active');
      $('#bptodo-completed').css('display', 'none');
      $('#bptodo-all').css('display', 'block');
    });

    // Modern tab handling (card-based)
    $(document).on('click', '.bptodo-tab', function() {
      var targetId = 'bptodo-panel-' + $(this).data('tab');

      // Update tabs
      $('.bptodo-tab').removeClass('bptodo-tab--active').attr('aria-selected', 'false');
      $(this).addClass('bptodo-tab--active').attr('aria-selected', 'true');

      // Update panels
      $('.bptodo-tab-panel').each(function() {
        if (this.id === targetId) {
          $(this).removeAttr('hidden');
        } else {
          $(this).attr('hidden', '');
        }
      });
    });
  }

  /**
   * Edit popup functionality
   */
  function initEditPopup() {
    $(document).on('click', '.trigger', function() {
      var tid = $(this).data('tid');
      var guid = $(this).data('guid');
      var component_type = $(this).data('type');

      // Show loading in popup
      $('.edit_form_popup').html('<div class="bptodo-loading"><div class="bptodo-spinner"></div></div>');

      $.ajax({
        url: ajaxurl,
        type: 'post',
        data: {
          action: 'bptodo_edit_form_popup',
          t_id: tid,
          gu_id: guid,
          component: component_type,
          ajax_nonce: todo_ajax_object.ajax_nonce,
        },
        success: function(response) {
          $('.edit_form_popup').html(response);
        },
        error: function() {
          if (BPTodo.toast) {
            BPTodo.toast.error(todo_ajax_object.error_text || 'Failed to load form');
          }
        }
      });
    });

    // Update todo on popup submit
    $('.bptodo_edit_form_popup_class').on('click', '#todo_update, #group_todo_update', function(e) {
      e.preventDefault();
      $('#bptodo_edit_form_popup').trigger('submit');
    });

    $(document).on('submit', '#bptodo_edit_form_popup', function(e) {
      e.preventDefault();

      var $form = $(this);
      var $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
      var editFormValue = $form.serializeArray();

      // Extract todo ID from form data
      var todoId = null;
      editFormValue.forEach(function(field) {
        if (field.name === 'todo_id' || field.name === 't_id') {
          todoId = field.value;
        }
      });

      // Add loading state
      if (BPTodo.loading) {
        BPTodo.loading.button($submitBtn[0], todo_ajax_object.saving_text || 'Saving...');
      } else {
        $submitBtn.prop('disabled', true);
      }

      $.ajax({
        url: ajaxurl,
        type: 'post',
        data: {
          action: 'bptodo_update_form_popup',
          update_form_data: editFormValue,
          ajax_nonce: todo_ajax_object.ajax_nonce,
        },
        success: function(response) {
          // Close modal
          var modal = document.querySelector('[data-modal=trigger]');
          if (modal) {
            modal.classList.remove('open');
          }
          $('.bptodo-modal').removeClass('bptodo-modal--open');

          // Show success toast
          if (BPTodo.toast) {
            BPTodo.toast.success(todo_ajax_object.todo_updated_text || 'Todo updated successfully');
          }

          // Update the todo card in DOM if we have the ID
          if (todoId) {
            refreshTodoCard(todoId);
          } else {
            // Fallback: refresh the todo list section
            refreshTodoList();
          }
        },
        error: function() {
          if (BPTodo.toast) {
            BPTodo.toast.error(todo_ajax_object.error_text || 'Failed to update todo');
          }
        },
        complete: function() {
          if (BPTodo.loading) {
            BPTodo.loading.buttonDone($submitBtn[0]);
          } else {
            $submitBtn.prop('disabled', false);
          }
        }
      });
    });
  }

  /**
   * Refresh a single todo card via AJAX
   */
  function refreshTodoCard(todoId) {
    // Try to find the card in the DOM
    var $card = $('[data-todo-id="' + todoId + '"]');

    if ($card.length === 0) {
      // Card not found, refresh the whole list
      refreshTodoList();
      return;
    }

    // Add loading state to card
    $card.addClass('bptodo-card--loading');

    $.ajax({
      url: ajaxurl,
      type: 'post',
      data: {
        action: 'bptodo_get_todo_card_html',
        todo_id: todoId,
        ajax_nonce: todo_ajax_object.ajax_nonce,
      },
      success: function(response) {
        if (response.success && response.data && response.data.html) {
          var $newCard = $(response.data.html);
          $card.replaceWith($newCard);

          // Trigger animation
          setTimeout(function() {
            $newCard.addClass('bptodo-card--updated');
            setTimeout(function() {
              $newCard.removeClass('bptodo-card--updated');
            }, 600);
          }, 10);
        } else {
          // Fallback to list refresh
          refreshTodoList();
        }
      },
      error: function() {
        $card.removeClass('bptodo-card--loading');
        refreshTodoList();
      }
    });
  }

  /**
   * Refresh the todo list section (lightweight alternative to full page reload)
   */
  function refreshTodoList() {
    var $wrapper = $('.bptodo-wrapper');
    if ($wrapper.length === 0) {
      $wrapper = $('.bptodo-adming-setting');
    }

    if ($wrapper.length === 0) {
      // No wrapper found, last resort
      window.location.reload();
      return;
    }

    // Add loading overlay
    $wrapper.css('position', 'relative');
    $wrapper.append('<div class="bptodo-refresh-overlay"><div class="bptodo-spinner"></div></div>');

    $.ajax({
      url: window.location.href,
      success: function(data) {
        var $newContent = $(data).find('.bptodo-wrapper');
        if ($newContent.length === 0) {
          $newContent = $(data).find('.bptodo-adming-setting');
        }

        if ($newContent.length > 0) {
          $wrapper.html($newContent.html());
          initAccordion();
          initModalEvents();
        }
      },
      error: function() {
        if (BPTodo.toast) {
          BPTodo.toast.error('Failed to refresh list');
        }
      },
      complete: function() {
        $('.bptodo-refresh-overlay').remove();
      }
    });
  }

  /**
   * Category accordion functionality
   */
  function initAccordion() {
    var acc = document.getElementsByClassName('bptodo-item');
    if (acc.length === 0) return;

    // Open first accordion by default
    if (acc[0]) {
      var panel = acc[0].nextElementSibling;
      if (panel) {
        $(acc[0]).addClass('active');
        panel.style.maxHeight = panel.scrollHeight + 'px';
      }
    }

    // Accordion click handlers
    for (var i = 0; i < acc.length; i++) {
      acc[i].onclick = function() {
        var panel = this.nextElementSibling;
        if (!panel) return;

        if (panel.style.maxHeight) {
          panel.style.maxHeight = null;
          $(this).removeClass('active bptodo-sameday-open');
        } else {
          $(this).addClass('active bptodo-sameday-open');
          panel.style.maxHeight = panel.scrollHeight + 'px';
        }
      };
    }
  }

  $(document).on('click', '.bptodo-accordion', function() {
    return false;
  });

  /**
   * Datepicker initialization
   */
  function initDatepicker() {
    if ($.fn.datepicker) {
      $('.todo_due_date').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0
      });
    }
  }

  /**
   * Export functionality
   */
  function initExport() {
    $(document).on('click', '#export_my_tasks', function() {
      var $btn = $(this);
      var originalHtml = $btn.html();

      if (BPTodo.loading) {
        BPTodo.loading.button($btn[0], 'Exporting...');
      } else {
        $btn.html('<div class="reload-spin"></div> Export');
      }

      var security_nonce = $('#bptodo-export-todo-nonce').val();

      $.post(
        ajaxurl,
        {
          action: 'bptodo_export_my_tasks',
          security_nonce: security_nonce
        },
        function(response) {
          if (BPTodo.loading) {
            BPTodo.loading.buttonDone($btn[0]);
          }
          $btn.html('<div class="export-download"></div> Export');
          JSONToCSVConvertor(response, todo_ajax_object.export_file_heading, true);

          if (BPTodo.toast) {
            BPTodo.toast.success('Export completed');
          }
        },
        'JSON'
      ).fail(function() {
        if (BPTodo.loading) {
          BPTodo.loading.buttonDone($btn[0]);
        }
        $btn.html(originalHtml);
        if (BPTodo.toast) {
          BPTodo.toast.error('Export failed');
        }
      });
    });
  }

  /**
   * Category management
   */
  function initCategoryManagement() {
    // Toggle category row
    $(document).on('click', '.add-todo-category', function() {
      $('.add-todo-cat-row').slideToggle('slow');
      var $icon = $(this).find('i');
      if ($icon.hasClass('fa-plus')) {
        $icon.removeClass('fa-plus').addClass('fa-minus');
      } else {
        $icon.removeClass('fa-minus').addClass('fa-plus');
      }
    });

    // Popup category toggle
    $('.edit_form_popup').on('click', '.add-todo-category', function() {
      $('.add-todo-cat-row').slideToggle('slow');
      var $span = $(this).find('span');
      if ($span.hasClass('add-cat-icon')) {
        $span.removeClass('add-cat-icon').addClass('remove-cat-icon');
      } else {
        $span.removeClass('remove-cat-icon').addClass('add-cat-icon');
      }
    });

    // Add category
    function addCategory(context) {
      var name = $('#todo-category-name').val();
      var $btn = $('#add-todo-cat');
      var btn_text = $btn.html();

      if (name === '') {
        $('#todo-category-name')
          .addClass('bptodo-add-cat-empty')
          .attr('placeholder', todo_ajax_object.required_cat_text);
        return;
      }

      var security_nonce = $('#bptodo-add-category-nonce').val();

      if (BPTodo.loading) {
        BPTodo.loading.button($btn[0], 'Adding...');
      } else {
        $btn.html(btn_text + ' <i class="fa fa-refresh fa-spin"></i>');
      }

      $.post(
        ajaxurl,
        {
          action: 'bptodo_add_todo_category_front',
          name: name,
          security_nonce: security_nonce
        },
        function(response) {
          if (BPTodo.loading) {
            BPTodo.loading.buttonDone($btn[0]);
          } else {
            $btn.html(btn_text);
          }

          if (response === 'todo-category-added') {
            var html = '<option value="' + name + '" selected>' + name + '</option>';
            $('#bp_todo_categories').append(html);
            $('.add-todo-cat-row').hide();
            $('#todo-category-name').val('');
            $('.add-todo-category i').attr('class', 'fa fa-plus');

            if (BPTodo.toast) {
              BPTodo.toast.success('Category added');
            }
          }
        }
      );
    }

    $(document).on('click', '#add-todo-cat', function() {
      addCategory('document');
    });

    $('.edit_form_popup').on('click', '#add-todo-cat', function() {
      addCategory('popup');
    });
  }

  /**
   * Remove todo functionality
   */
  function initRemoveTodo() {
    $(document).on('click', '.bptodo-remove-todo, .js-bptodo-delete', function(e) {
      e.preventDefault();

      var $btn = $(this);
      var tid = $btn.data('tid') || $btn.data('todo-id');
      var $card = $btn.closest('.bptodo-card');
      var $row = $btn.closest('tr');

      // Use modern confirm dialog if available
      if (BPTodo.modal && BPTodo.modal.confirm) {
        BPTodo.modal.confirm(
          todo_ajax_object.remove_todo_text || 'Are you sure you want to delete this todo?',
          function() {
            performRemoveTodo(tid, $btn, $card, $row);
          }
        );
      } else if (confirm(todo_ajax_object.remove_todo_text)) {
        performRemoveTodo(tid, $btn, $card, $row);
      }
    });
  }

  function performRemoveTodo(tid, $btn, $card, $row) {
    // Add loading state
    if ($card.length) {
      $card.addClass('bptodo-card--deleting');
    }
    $btn.html('<i class="fa fa-refresh fa-spin"></i>');

    $.post(
      ajaxurl,
      {
        action: 'bptodo_remove_todo',
        ajax_nonce: todo_ajax_object.ajax_nonce,
        tid: tid
      },
      function(response) {
        if (response === 'todo-removed') {
          // Animate removal
          if ($card.length) {
            $card.addClass('bptodo-card--removed');
            setTimeout(function() {
              $card.remove();
              updateTodoCounters(-1, 0);
            }, 300);
          } else if ($row.length) {
            $row.fadeOut(300, function() {
              $(this).remove();
            });
            $('#bptodo-row-' + tid).remove();
          }

          if (BPTodo.toast) {
            BPTodo.toast.success(todo_ajax_object.todo_removed_text || 'Todo deleted');
          }
        } else {
          if ($card.length) {
            $card.removeClass('bptodo-card--deleting');
          }
          if (BPTodo.toast) {
            BPTodo.toast.error('Failed to delete todo');
          }
        }
      }
    ).fail(function() {
      if ($card.length) {
        $card.removeClass('bptodo-card--deleting');
      }
      if (BPTodo.toast) {
        BPTodo.toast.error('Failed to delete todo');
      }
    });
  }

  /**
   * Edit todo functionality
   * Redirects to the edit page when clicking the edit button
   */
  function initEditTodo() {
    $(document).on('click', '.js-bptodo-edit', function(e) {
      e.preventDefault();

      var $btn = $(this);
      var todoId = $btn.data('todo-id');
      var $card = $btn.closest('.bptodo-card');
      var context = $card.data('context') || 'member';

      // Get the current page URL and construct edit URL
      var currentUrl = window.location.href;
      var editUrl;

      if (context === 'group') {
        // For group todos, the edit URL pattern is different
        editUrl = currentUrl.replace(/\/list\/?$/, '/add?args=' + todoId);
      } else {
        // For member todos, redirect to member's edit page
        var baseUrl = currentUrl.replace(/\/(list)?\/?$/, '');
        editUrl = baseUrl + '/add?args=' + todoId;
      }

      // Show loading state
      $btn.addClass('bptodo-btn--loading');

      // Redirect to edit page
      window.location.href = editUrl;
    });
  }

  /**
   * Complete todo functionality
   */
  function initCompleteTodo() {
    // Checkbox-based completion (modern cards)
    $(document).on('change', '.bptodo-checkbox__input', function(e) {
      var $checkbox = $(this);
      var $card = $checkbox.closest('.bptodo-card');
      var tid = $checkbox.data('todo-id') || $card.data('todo-id');
      var isCompleting = $checkbox.prop('checked');

      if (isCompleting) {
        completeTodo(tid, $card);
      } else {
        uncompleteTodo(tid, $card);
      }
    });

    // Legacy click-based completion
    $(document).on('click', '.bptodo-complete-todo', function(e) {
      var checkbox = $(this).find('input[type="checkbox"]');
      if (checkbox.prop('disabled')) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }

      var $clicked = $(this);
      var tid = $clicked.data('tid');
      var uid = $clicked.data('uid');

      if ($clicked.hasClass('todo-complete')) {
        uncompleteTodoLegacy(tid, $clicked);
        return;
      }

      if ($clicked.hasClass('todo-uncomplete') || (!$clicked.hasClass('todo-complete') && $clicked.is('a'))) {
        completeTodoLegacy(tid, uid, $clicked);
      }
    });

    // Undo complete
    $(document).on('click', '.bptodo-undo-complete-todo', function(e) {
      e.preventDefault();
      var $link = $(this);
      var tid = $link.data('tid');

      $link.html('<i class="fa fa-refresh fa-spin"></i>');

      $.post(
        ajaxurl,
        {
          action: 'bptodo_undo_complete_todo',
          ajax_nonce: todo_ajax_object.ajax_nonce,
          tid: tid
        },
        function(response) {
          if (response === 'todo-undo-completed') {
            if (BPTodo.toast) {
              BPTodo.toast.success('Todo marked as incomplete');
            }
            refreshTodoList();
          }
        }
      );
    });
  }

  function completeTodo(tid, $card) {
    $card.addClass('bptodo-card--completing');

    $.post(
      ajaxurl,
      {
        action: 'bptodo_complete_todo',
        ajax_nonce: todo_ajax_object.ajax_nonce,
        tid: tid,
        uid: todo_ajax_object.current_user_id || 0
      },
      function(response) {
        try {
          var data = typeof response === 'string' ? JSON.parse(response) : response;
          if (data.result === 'todo-completed') {
            $card.removeClass('bptodo-card--completing').addClass('bptodo-card--completed');

            // Update progress bar and counters
            if (data.avg_percentage) {
              $('.bptodo-progress__bar').css('width', data.avg_percentage + '%');
            }
            updateTodoCounters(0, 1);

            if (BPTodo.toast) {
              BPTodo.toast.success(todo_ajax_object.todo_completed_text || 'Todo completed!');
            }
          }
        } catch (e) {
          $card.removeClass('bptodo-card--completing');
          if (BPTodo.toast) {
            BPTodo.toast.error('Failed to complete todo');
          }
        }
      }
    ).fail(function() {
      $card.removeClass('bptodo-card--completing');
      // Uncheck the checkbox
      $card.find('.bptodo-checkbox__input').prop('checked', false);
      if (BPTodo.toast) {
        BPTodo.toast.error('Failed to complete todo');
      }
    });
  }

  function uncompleteTodo(tid, $card) {
    $card.removeClass('bptodo-card--completed').addClass('bptodo-card--completing');

    $.post(
      ajaxurl,
      {
        action: 'bptodo_undo_complete_todo',
        ajax_nonce: todo_ajax_object.ajax_nonce,
        tid: tid
      },
      function(response) {
        $card.removeClass('bptodo-card--completing');
        if (response === 'todo-undo-completed') {
          updateTodoCounters(0, -1);
          if (BPTodo.toast) {
            BPTodo.toast.success('Todo marked as incomplete');
          }
        }
      }
    ).fail(function() {
      $card.removeClass('bptodo-card--completing');
      // Re-check the checkbox
      $card.find('.bptodo-checkbox__input').prop('checked', true);
      $card.addClass('bptodo-card--completed');
      if (BPTodo.toast) {
        BPTodo.toast.error('Failed to update todo');
      }
    });
  }

  function completeTodoLegacy(tid, uid, $clicked) {
    var completed_todo = $('.bp_completed_todo_count').text();
    var all_todo = $('.bp_all_todo_count').text();

    $clicked.html('<i class="fa fa-refresh fa-spin"></i>');

    $.post(
      ajaxurl,
      {
        action: 'bptodo_complete_todo',
        ajax_nonce: todo_ajax_object.ajax_nonce,
        tid: tid,
        uid: uid,
        completed: completed_todo,
        all_todo: all_todo
      },
      function(response) {
        try {
          var data = typeof response === 'string' ? JSON.parse(response) : response;
          if (data.result === 'todo-completed') {
            // Update UI
            if ($clicked.closest('tr').length) {
              $clicked.closest('tr').find('td').addClass('todo-completed');
              $clicked.closest('td').prev('td').text(data.due_date_str);
            }

            // Update counters
            $('.bp_completed_todo_count').text(data.completed_todo);
            $('#bptodo-completed tbody').append(data.completed_html);
            $('.bptodo-color').css('width', data.avg_percentage + '%');
            $('.bptodo-light-grey b').text(data.avg_percentage + '%');

            // Replace with undo link
            if ($clicked.is('a')) {
              $clicked.closest('li').replaceWith(
                '<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="' + tid + '" title="' + todo_ajax_object.undo_todo_title + '"><i class="fa fa-undo"></i></a></li>'
              );
            } else {
              $clicked.closest('td').html(
                '<ul><li><a href="" class="bptodo-undo-complete-todo" data-tid="' + tid + '" title="' + todo_ajax_object.undo_todo_title + '"><i class="fa fa-undo"></i></a></li></ul>'
              );
            }

            if (BPTodo.toast) {
              BPTodo.toast.success(todo_ajax_object.todo_completed_text || 'Todo completed!');
            }
          }
        } catch (e) {
          if (BPTodo.toast) {
            BPTodo.toast.error('Failed to complete todo');
          }
        }
      }
    );

    $clicked.addClass('todo-uncomplete').removeClass('todo-complete');
  }

  function uncompleteTodoLegacy(tid, $clicked) {
    $.post(
      ajaxurl,
      {
        action: 'bptodo_undo_complete_todo',
        ajax_nonce: todo_ajax_object.ajax_nonce,
        tid: tid
      },
      function(response) {
        if (response === 'todo-undo-completed') {
          if (BPTodo.toast) {
            BPTodo.toast.success('Todo marked as incomplete');
          }
          refreshTodoList();
        }
      }
    );
    $clicked.removeClass('todo-uncomplete').addClass('todo-complete');
  }

  /**
   * Update todo counters without page reload
   */
  function updateTodoCounters(totalDelta, completedDelta) {
    // Update stat cards
    var $totalCard = $('.bptodo-stat-card__value').first();
    var $pendingCard = $('.bptodo-stat-card__value').eq(1);
    var $completedCard = $('.bptodo-stat-card__value').eq(2);
    var $progressCard = $('.bptodo-stat-card__value').eq(3);

    var total = parseInt($totalCard.text()) || 0;
    var pending = parseInt($pendingCard.text()) || 0;
    var completed = parseInt($completedCard.text()) || 0;

    total += totalDelta;
    completed += completedDelta;
    pending = total - completed;

    if (total < 0) total = 0;
    if (completed < 0) completed = 0;
    if (pending < 0) pending = 0;

    var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;

    $totalCard.text(total);
    $pendingCard.text(pending);
    $completedCard.text(completed);
    $progressCard.text(percentage + '%');

    // Update progress bar
    $('.bptodo-progress__bar').css('width', percentage + '%');

    // Update tab badges
    $('.bptodo-tab[data-tab="all"] .bptodo-badge').text(total);
    $('.bptodo-tab[data-tab="completed"] .bptodo-badge').text(completed);

    // Legacy counters
    $('.bp_all_todo_count').text(total);
    $('.bp_completed_todo_count').text(completed);
    $('.bptodo-color').css('width', percentage + '%');
    $('.bptodo-light-grey b').text(percentage + '%');
  }

  /**
   * Select2 initialization
   */
  function initSelect2() {
    if ($.fn.select2 && $('#todo_group_members').length >= 1) {
      $('#todo_group_members').select2({
        placeholder: $('#todo_group_members').attr('placeholder'),
        allowClear: true
      });
    }
  }

  /**
   * DataTable initialization for reports
   */
  function initDataTable() {
    if (!$.fn.DataTable) return;

    function initializeTodoTable(todoId) {
      $('#bp-todo-table').DataTable({
        destroy: true,
        ajax: {
          url: ajaxurl,
          data: {
            action: 'get_group_todo_report',
            group: $('#bp-todo-table').data('group'),
            ajax_nonce: todo_ajax_object.ajax_nonce,
            todo_id: todoId
          }
        },
        columns: [
          { data: 'Members', className: 'first_name' },
          { data: 'Status', className: 'last_name' },
          { data: 'Time', className: 'course' }
        ],
        layout: {
          topStart: {
            buttons: ['excelHtml5', 'csvHtml5', 'pdfHtml5']
          }
        },
        language: {
          emptyTable: todo_ajax_object.localized.empty_table,
          paginate: {
            first: todo_ajax_object.localized.paginate_first,
            last: todo_ajax_object.localized.paginate_last,
            next: todo_ajax_object.localized.paginate_next,
            previous: todo_ajax_object.localized.paginate_previous
          },
          search: todo_ajax_object.localized.search,
          lengthMenu: todo_ajax_object.localized.show_entries,
          info: todo_ajax_object.localized.pagination_info,
          infoEmpty: todo_ajax_object.localized.pagination_empty_info
        },
        paging: true
      });
    }

    var todoSelect = document.getElementById('todo-select');
    if (todoSelect) {
      initializeTodoTable(todoSelect.value);
    }

    $('#todo-select').on('change', function() {
      initializeTodoTable($(this).val());
    });
  }

  /**
   * Group report member toggle
   */
  function initGroupReport() {
    $(document).on('click', '.bptodo-toggle-members', function(e) {
      e.preventDefault();
      var $button = $(this);
      var taskId = $button.data('task-id');
      var $membersList = $('#members-' + taskId);
      var $icon = $button.find('.dashicons');

      if ($membersList.is(':visible')) {
        $membersList.slideUp(200);
        $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
      } else {
        $membersList.slideDown(200);
        $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
      }
    });

    $(document).on('click', '.bptodo-filter-btn', function(e) {
      e.preventDefault();
      var filter = $(this).data('filter');

      $('.bptodo-filter-btn').removeClass('active');
      $(this).addClass('active');

      if (filter === 'all') {
        $('.bptodo-member-item').fadeIn(200);
      } else if (filter === 'completed') {
        $('.bptodo-member-item').hide();
        $('.bptodo-member-item.status-complete').fadeIn(200);
      } else if (filter === 'pending') {
        $('.bptodo-member-item').hide();
        $('.bptodo-member-item.status-incomplete').fadeIn(200);
      }
    });
  }

  /**
   * Modal events initialization
   */
  function initModalEvents() {
    var buttons = document.querySelectorAll('.trigger[data-modal-trigger]');
    buttons.forEach(function(button) {
      button.addEventListener('click', function() {
        var modal = document.querySelector('[data-modal=trigger]');
        if (!modal) return;

        var contentWrapper = modal.querySelector('.content-wrapper');
        var close = modal.querySelector('.close');

        if (close) {
          close.addEventListener('click', function() {
            modal.classList.remove('open');
          });
        }

        modal.addEventListener('click', function() {
          modal.classList.remove('open');
        });

        if (contentWrapper) {
          contentWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
          });
        }

        modal.classList.toggle('open');
      });
    });
  }

  /**
   * Task selector dropdown
   */
  function initTaskSelector() {
    $(document).on('change', '#task-selector', function() {
      var selectedUrl = $(this).val();
      if (selectedUrl) {
        // Show loading indicator
        if (BPTodo.loading) {
          BPTodo.loading.show(document.body);
        }
        window.location.href = selectedUrl;
      }
    });
  }

  /**
   * Add new todo button handler
   */
  function initAddNewButton() {
    $(document).on('click', '.js-bptodo-add-new', function(e) {
      e.preventDefault();

      // Try to find and open the add form first
      var $form = $('.bptodo-form, #bptodo-add-form');
      if ($form.length && $form.is(':hidden')) {
        $form.slideDown(300);
        $form.find('input[type="text"]').first().focus();
        return;
      }

      // Otherwise redirect to the add page
      var currentUrl = window.location.href;
      var addUrl;

      // Build add URL based on current context
      if (currentUrl.indexOf('/groups/') !== -1) {
        // Group context - go to group add page
        addUrl = currentUrl.replace(/\/(list)?\/?(\?.*)?$/, '/add');
      } else {
        // Member context - go to member add page
        addUrl = currentUrl.replace(/\/(list)?\/?(\?.*)?$/, '/add');
      }

      // Ensure we have a proper URL
      if (addUrl === currentUrl) {
        addUrl = currentUrl + (currentUrl.endsWith('/') ? 'add' : '/add');
      }

      window.location.href = addUrl;
    });
  }

  /**
   * Search and Filter functionality for shortcode todo list
   */
  function initSearchFilter() {
    var $searchInput = $('#bptodo-search-input');
    var $statusFilter = $('#bptodo-filter-status');
    var $priorityFilter = $('#bptodo-filter-priority');
    var $categoryFilter = $('#bptodo-filter-category');
    var $clearBtn = $('#bptodo-clear-filters');
    var $cards = $('.bptodo-cards--shortcode .bptodo-card');
    var $resultsCount = $('#bptodo-results-count');
    var searchTimeout;

    // Skip if no search/filter elements exist
    if (!$searchInput.length && !$statusFilter.length) {
      return;
    }

    function filterTodos() {
      var searchText = $searchInput.val().toLowerCase().trim();
      var statusValue = $statusFilter.val();
      var priorityValue = $priorityFilter.val();
      var categoryValue = $categoryFilter.length ? $categoryFilter.val() : '';
      var visibleCount = 0;
      var hasActiveFilters = searchText || statusValue !== 'all' || priorityValue !== 'all' || categoryValue;

      // Show/hide clear button
      if (hasActiveFilters) {
        $clearBtn.show();
      } else {
        $clearBtn.hide();
      }

      $cards.each(function() {
        var $card = $(this);
        var title = $card.find('.bptodo-card__title').text().toLowerCase();
        var content = $card.find('.bptodo-card__content').text().toLowerCase();
        var isComplete = $card.hasClass('bptodo-card--completed');
        var priority = '';
        var category = $card.find('.bptodo-badge--category').text().toLowerCase().trim();

        // Get priority from class
        if ($card.hasClass('bptodo-card--priority-critical') || $card.hasClass('bptodo-priority--critical')) {
          priority = 'critical';
        } else if ($card.hasClass('bptodo-card--priority-high') || $card.hasClass('bptodo-priority--high')) {
          priority = 'high';
        } else {
          priority = 'normal';
        }

        var matchesSearch = !searchText || title.indexOf(searchText) !== -1 || content.indexOf(searchText) !== -1;
        var matchesStatus = statusValue === 'all' ||
                           (statusValue === 'complete' && isComplete) ||
                           (statusValue === 'incomplete' && !isComplete);
        var matchesPriority = priorityValue === 'all' || priority === priorityValue;
        var matchesCategory = !categoryValue || category === categoryValue.toLowerCase();

        if (matchesSearch && matchesStatus && matchesPriority && matchesCategory) {
          $card.fadeIn(200);
          visibleCount++;
        } else {
          $card.fadeOut(200);
        }
      });

      // Update results count
      var totalCount = $cards.length;
      var countText = visibleCount + ' of ' + totalCount + ' items';
      if (hasActiveFilters) {
        countText = 'Showing ' + countText + ' (filtered)';
      }
      $resultsCount.text(countText);

      // Show "no results" message if needed
      setTimeout(function() {
        var $noResults = $('.bptodo-no-results');
        if (visibleCount === 0 && hasActiveFilters) {
          if (!$noResults.length) {
            var noResultsHtml = '<div class="bptodo-no-results">' +
              '<svg class="bptodo-no-results__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
              '<circle cx="11" cy="11" r="8"></circle>' +
              '<path d="m21 21-4.35-4.35"></path>' +
              '</svg>' +
              '<div class="bptodo-no-results__title">' + (window.todo_ajax_object && window.todo_ajax_object.no_results_text ? window.todo_ajax_object.no_results_text : 'No matching todos found') + '</div>' +
              '<p>Try adjusting your search or filters</p>' +
              '</div>';
            $('.bptodo-cards--shortcode').after(noResultsHtml);
          }
          $noResults.show();
        } else {
          $noResults.hide();
        }
      }, 250);
    }

    // Search input with debounce
    $searchInput.on('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(filterTodos, 300);
    });

    // Filter dropdowns - instant filtering
    $statusFilter.on('change', filterTodos);
    $priorityFilter.on('change', filterTodos);
    if ($categoryFilter.length) {
      $categoryFilter.on('change', filterTodos);
    }

    // Clear filters button
    $clearBtn.on('click', function() {
      $searchInput.val('');
      $statusFilter.val('all');
      $priorityFilter.val('all');
      if ($categoryFilter.length) {
        $categoryFilter.val('');
      }
      filterTodos();
    });

    // Keyboard shortcut: Escape to clear search
    $searchInput.on('keydown', function(e) {
      if (e.key === 'Escape') {
        $(this).val('');
        filterTodos();
      }
    });
  }

  // Initialize all modules
  initTabs();
  initEditPopup();
  initAccordion();
  initDatepicker();
  initExport();
  initCategoryManagement();
  initRemoveTodo();
  initEditTodo();
  initCompleteTodo();
  initSelect2();
  initDataTable();
  initGroupReport();
  initModalEvents();
  initTaskSelector();
  initAddNewButton();
  initSearchFilter();

});

/**
 * CSV Export utility
 */
function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel) {
  var arrData = typeof JSONData !== 'object' ? JSON.parse(JSONData) : JSONData;
  var CSV = '';
  CSV += ReportTitle + '\r\n\n';

  if (ShowLabel) {
    var row = '';
    for (var index in arrData[0]) {
      row += index + ',';
    }
    row = row.slice(0, -1);
    CSV += row + '\r\n';
  }

  for (var i = 0; i < arrData.length; i++) {
    var row = '';
    for (var index in arrData[i]) {
      row += '"' + arrData[i][index] + '",';
    }
    row.slice(0, row.length - 1);
    CSV += row + '\r\n';
  }

  if (CSV === '') {
    if (window.BPTodo && window.BPTodo.toast) {
      window.BPTodo.toast.error('Invalid data');
    } else {
      alert('Invalid data');
    }
    return;
  }

  var fileName = ReportTitle.replace(/ /g, '_');
  var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);
  var link = document.createElement('a');
  link.href = uri;
  link.style.cssText = 'visibility:hidden';
  link.download = fileName + '.csv';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

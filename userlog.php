<?php

chdir('../../');
include_once('./include/auth.php');

$userlog_actions = array(
    1 => __('Delete')
);

set_default_action();

switch(get_nfilter_request_var('action')) {
    case 'edit':
        general_header();
        
        bottom_footer();
        break;
    default:
        general_header();
        userlog_list();
        bottom_footer();
        break;
}
exit;

function userlog_list(){
    global $userlog_actions,$item_rows;
    
    /* ================= input validation and session storage ================= */
    $filters = array(
        'rows' => array(
            'filter' => FILTER_VALIDATE_INT,
            'pageset' => true,
            'default' => '-1'
        ),
        'page' => array(
            'filter' => FILTER_VALIDATE_INT,
            'default' => '1'
        ),
        'filter' => array(
            'filter' => FILTER_CALLBACK,
            'pageset' => true,
            'default' => '',
            'options' => array('options' => 'sanitize_search_string')
        ),
        'sort_column' => array(
            'filter' => FILTER_CALLBACK,
            'default' => 'insert_time',
            'options' => array('options' => 'sanitize_search_string')
        ),
        'sort_direction' => array(
            'filter' => FILTER_CALLBACK,
            'default' => 'DESC',
            'options' => array('options' => 'sanitize_search_string')
        )
    );
    
    validate_store_request_vars($filters, 'sess_userlog');
    /* ================= input validation ================= */
    
    /* if the number of rows is -1, set it to the default */
    if (get_request_var('rows') == -1) {
        $rows = read_config_option('num_rows_table');
    } else {
        $rows = get_request_var('rows');
    }
    
    html_start_box("操作日志", '100%', '', '3', 'center', '');
    ?>
    <tr class='even'>
		<td>
			<form id='form_userlog' action='userlog.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' class='ui-state-default ui-corner-all' id='filter' size='25' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						操作日志
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='button' class='ui-button ui-corner-all ui-widget' id='refresh' value='<?php print __esc('Go');?>' title='<?php print __esc('Set/Refresh Filters');?>'>
							<input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __esc('Clear');?>' title='<?php print __esc('Clear Filters');?>'>
						</span>
					</td>
				</tr>
			</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = 'userlog.php?header=false';
				strURL += '&filter='+$('#filter').val();
				strURL += '&rows='+$('#rows').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'userlog.php?clear=1&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#refresh').click(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('#form_userlog').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
	html_end_box();
	
	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
	    $sql_where = "where user_name LIKE '%".get_request_var('filter')."%' or content like '%".get_request_var('filter')."%'";
	} else {
	    $sql_where = '';
	}
	
	$total_rows = db_fetch_cell("SELECT COUNT(*) FROM user_op_log ". $sql_where);
	
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	$userlog_list = db_fetch_assoc("select * from user_op_log $sql_where $sql_order $sql_limit");
	
	$nav = html_nav_bar('userlog.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, "操作日志", 'page', 'main');
	
	form_start('userlog.php', 'chk');
	
	print $nav;
	
	html_start_box('', '100%', '', '3', 'center', '');
	
	$display_text = array(
	    'user_name'    => array('display' => "用户名", 'align' => 'left',  'sort' => 'ASC', 'tip' => "用户名"),
	    'user_id'      => array('display' => "用户ID",        'align' => 'right', 'sort' => 'ASC', 'tip' => "用户ID"),
	    'ip'           => array('display' => "IP",        'align' => 'right', 'sort' => 'ASC', 'tip' => "IP"),
	    'insert_time'  => array('display' => "操作时间", 'align' => 'right', 'sort' => 'ASC', 'tip' => "操作时间"),
	    'content'      => array('display' => "操作内容", 'align' => 'right', 'sort' => 'ASC', 'tip' => "操作内容"),
	    'status'      => array('display' => "操作状态", 'align' => 'right', 'sort' => 'ASC', 'tip' => "操作状态"),
	);
	
	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	
	if (cacti_sizeof($userlog_list)) {
	    foreach ($userlog_list as $userlog) {
	        form_alternate_row('line' . $userlog['id'], true);
	        form_selectable_cell(filter_value($userlog['user_name'], get_request_var('filter'), ''), $userlog['id']);
	        form_selectable_cell($userlog['user_id'], $userlog['id'], '', 'right');
	        form_selectable_cell($userlog['ip'], $userlog['id'], '', 'right');
	        form_selectable_cell(substr($userlog['insert_time'],0,16), $userlog['id'], '', 'right');
	        form_selectable_cell(filter_value($userlog['content'], get_request_var('filter'), ''), $userlog['id'], '', 'right');
	        form_selectable_cell("成功", $userlog['id'], '', 'right');
	        form_checkbox_cell($userlog['user_id'], $userlog['id']);
	        form_end_row();
	    }
	} else {
	    print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text)+1) . "'><em>" . "没有数据" . "</em></td></tr>\n";
	}
	
	html_end_box(false);
	
	if (cacti_sizeof($userlog_list)) {
	    print $nav;
	}
	
	/* draw the dropdown containing a list of available actions for this form */
	//draw_actions_dropdown($userlog_actions);
	
	form_end();
}
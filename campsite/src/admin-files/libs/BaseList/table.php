<?php
/**
 * @package Campsite
 *
 * @author Petr Jasek <petr.jasek@sourcefabric.org>
 * @copyright 2010 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */
?>
<div class="table">

<table id="table-<?php echo $this->id; ?>" cellpadding="0" cellspacing="0" class="datatable">
<thead>
    <tr>
        <th><input type="checkbox" /></th>
        <th><?php echo putGS('Language'); ?></th>
        <th><?php echo putGS('Order'); ?></th>
        <th><?php echo putGS('Name'); ?></th>
        <th><?php echo putGS('Type'); ?></th>
        <th><?php echo putGS('Created by'); ?></th>
        <th><?php echo putGS('Author'); ?></th>
        <th><?php echo putGS('Status'); ?></th>
        <th><?php echo putGS('On Front Page'); ?></th>
        <th><?php echo putGS('On Section Page'); ?></th>
        <th><?php echo putGS('Images'); ?></th>
        <th><?php echo putGS('Topics'); ?></th>
        <th><?php echo putGS('Comments'); ?></th>
        <th><?php echo putGS('Reads'); ?></th>
        <th><?php echo putGS('Create Date'); ?></th>
        <th><?php echo putGS('Publish Date'); ?></th>
        <th><?php echo putGS('Last Modified'); ?></th>
    </tr>
</thead>
<tbody>
<?php if ($this->items === NULL) { ?>
    <tr><td colspan="17"><?php putGS('Loading data'); ?></td></tr>
<?php } else if (!empty($this->items)) { ?>
    <?php foreach ($this->items as $item) { ?>
    <tr>
        <?php foreach ($item as $row) { ?>
        <td><?php echo $row; ?></td>
        <?php } ?>
    </tr>
    <?php } ?>
<?php } ?>
</tbody>
</table>
</div>
<?php if ($this->order) { ?>
<form method="post" action="<?php echo $this->path; ?>/do_order.php" onsubmit="return sendOrder(this, '<?php echo $this->id; ?>');">
    <?php echo SecurityToken::FormParameter(); ?>
    <input type="hidden" name="language" value="<?php echo $this->language; ?>" />
    <input type="hidden" name="order" value="" />

<fieldset class="buttons">
    <input type="submit" name="Save" value="<?php putGS('Save order'); ?>" />
</fieldset>
</form>
<div style="clear: both"></div>
<?php } ?>
<?php if (!self::$renderTable) { ?>
<script type="text/javascript"><!--
tables = [];
filters = [];

function sendOrder(form, hash)
{
    var order = $('#table-' + hash + ' tbody').sortable('toArray');
    callServer(['ArticleList', 'doOrder'], [
        order,
        $('input[name=language]', $(form)).val(),
        ], function(data) {
            tables[hash].fnDraw(true);
            flashMessage('<?php putGS('Order updated.'); ?>');
        });
    return false;
}
--></script>
<?php } // render ?>
<script type="text/javascript"><!--
$(document).ready(function() {
var table = $('#table-<?php echo $this->id; ?>');
filters['<?php echo $this->id; ?>'] = [];
tables['<?php echo $this->id; ?>'] = table.dataTable({
    'bAutoWidth': false,
    'bDestroy': true,
    'bJQueryUI': true,
    'sDom': '<?php echo $this->getSDom(); ?>',
    'sScrollX': '100%',
    'aaSorting': [[2, 'asc']],
    'oLanguage': {
        'oPaginate': {
            'sNext': '<?php putGS('Next'); ?>',
            'sPrevious': '<?php putGS('Previous'); ?>',
        },
        'sZeroRecords': '<?php putGS('No records found.'); ?>',
        'sSearch': '<?php putGS('Search'); ?>:',
        'sInfo': '<?php putGS('Showing _START_ to _END_ of _TOTAL_ entries'); ?>',
        'sEmpty': '<?php putGS('No entries to show'); ?>',
        'sInfoFiltered': '<?php putGS(' - filtering from _MAX_ records'); ?>',
        'sLengthMenu': '<?php putGS('Display _MENU_ records'); ?>',
    },
    'aoColumnDefs': [
        { // inputs for id
            'fnRender': function(obj) {
                var id = obj.aData[0] + '_' + obj.aData[1];
                return '<input type="checkbox" name="' + id + '" />';
            },
            'aTargets': [0]
        },
        { // status workflow
            'fnRender': function(obj) {
                switch (obj.aData[7]) {
                    case 'Y':
                        return '<?php putGS('Published'); ?>';
                    case 'N':
                        return '<?php putGS('New'); ?>';
                    case 'S':
                        return '<?php putGS('Submitted'); ?>';
                    case 'M':
                        return '<?php putGS('Publish with issue'); ?>';
                }
            },
            'aTargets': [7]
        },
        { // hide columns
            'bVisible': false,
            'aTargets': [<?php if (!self::$renderActions) { ?>0, <?php } ?>1, 2, 5, 10, 11, 14, 16,
                <?php echo implode(', ', $this->hidden); ?>
            ],
        },
        { // not sortable
            'bSortable': false,
            'aTargets': [0, 1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 16],
        },
        { // id
            'sClass': 'id',
            'aTargets': [0],
        },
        { // name
            'sClass': 'name',
            'aTargets': [3],
        },
        { // short
            'sClass': 'flag',
            'aTargets': [7, 8, 9, 10, 11, 12, 13]
        },
        { // dates
            'sClass': 'date',
            'aTargets': [14, 15, 16]
        },
    ],
    'fnDrawCallback': function() {
        $('#table-<?php echo $this->id; ?> tbody tr').click(function() {
            $(this).toggleClass('selected');
            input = $('input:checkbox', $(this)).attr('checked', $(this).hasClass('selected'));
        });
        $('#table-<?php echo $this->id; ?> tbody input:checkbox').change(function() {
            if ($(this).attr('checked')) {
                $(this).parents('tr').addClass('selected');
            } else {
                $(this).parents('tr').removeClass('selected');
            }
        });
        <?php if ($this->order) { ?>
        $('#table-<?php echo $this->id; ?> tbody').sortable();
        <?php } ?>
    },
    <?php if ($this->items !== NULL) { // display all items ?>
    'bPaging': false,
    'iDisplayLength': <?php echo sizeof($this->items); ?>,
    <?php } else { // no items - server side ?>
    'bServerSide': true,
    'sAjaxSource': '<?php echo $this->path; ?>/do_data.php',
    'sPaginationType': 'full_numbers',
    'fnServerData': function (sSource, aoData, fnCallback) {
        for (var i in filters['<?php echo $this->id; ?>']) {
            aoData.push({
                'name': i,
                'value': filters['<?php echo $this->id; ?>'][i],
            });
        }
        <?php foreach (array('publication', 'issue', 'section', 'language') as $filter) {
            if (!empty($this->$filter)) { ?>
            aoData.push({
                'name': '<?php echo $filter; ?>',
                'value': '<?php echo $this->$filter; ?>',
            });
        <?php }} ?>
            callServer(['ArticleList', 'doData'], aoData, fnCallback);
    },
    <?php } ?>
    <?php if ($this->colVis) { ?>
    'oColVis': { // disable Show/hide column
        'aiExclude': [0, 1, 2],
        'buttonText': '<?php putGS('Show / hide columns'); ?>',
    },
    <?php } ?>
    <?php if ($this->order) { ?>
    'fnRowCallback': function(nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
        var id = $(aData[0]).attr('name').split('_')[0];
        $(nRow).attr('id', 'article_' + id);
        return nRow;
    },
    <?php } ?>
});

});
--></script>

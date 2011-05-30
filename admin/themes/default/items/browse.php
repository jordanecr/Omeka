<?php 
$pageTitle = __('Browse Items');
head(array('title'=>$pageTitle,'content_class' => 'horizontal-nav', 'bodyclass'=>'items primary browse-items')); ?>
<h1><?php echo $pageTitle; ?> <?php echo __('(%s total)', total_results()); ?></h1>
<p id="add-item" class="add-button"><a class="add" href="<?php echo html_escape(uri('items/add')); ?>"><?php echo __('Add an Item'); ?></a></p>
<?php endif; ?>
<div id="primary">
    <?php echo flash(); ?>
    <?php if ( total_results() ): ?>
    <script type="text/javascript">
        jQuery(window).load(function() {
            jQuery('.item-details').hide();
            jQuery('.action-links').prepend('<li class="details">Details</li>');

            jQuery('tr.item').each(function() {
                var itemDetails = jQuery(this).find('.item-details');
                if (jQuery.trim(itemDetails.html()) != '') {
                    jQuery(this).find('.details').css({'color': '#389', 'font-weight' : 'bold', 'cursor': 'pointer'}).click(function() {
                        itemDetails.slideToggle('fast');
                    });
                }
            });
            
            var itemCheckboxes = jQuery("table#items tbody input[type=checkbox]");
            var globalCheckbox = jQuery('th#batch-edit-heading').html('<input type="checkbox">').find('input');
            var batchEditSubmit = jQuery('.batch-edit-option input');
            /**
             * Disable the batch submit button first, will be enabled once item
             * checkboxes are checked.
             */
            batchEditSubmit.attr('disabled', 'disabled').click(function() {
                var form = jQuery(this).parents('form');
                var url = jQuery(form).attr('action');
                var data = jQuery(form).serialize();
                jQuery.get(
                    url,
                    data,
                    function (response) {
                        jQuery(response).dialog({
                            'modal': true,
                            'minWidth': '800',
                            'title': 'Batch Edit Items'
                        });
                    }
                );
                return false;
            });
            
            /**
             * Check all the itemCheckboxes if the globalCheckbox is checked.
             */
            globalCheckbox.change(function() {
                var check = jQuery(this).attr('checked');
                if (check) {
                    itemCheckboxes.attr("checked", check);
                } else {
                    itemCheckboxes.attr("checked", false)
                }
                checkBatchEditSubmitButton();
            });

            /**
             * Unchecks the global checkbox if any of the itemCheckboxes are
             * unchecked.
             */
            itemCheckboxes.change(function(){
                if (!jQuery(this).attr("checked")) {
                    globalCheckbox.attr("checked", false);
                }
                checkBatchEditSubmitButton();
            });
            
            /**
             * Function to check whether the batchEditSubmit button should be
             * enabled. If any of the itemCheckboxes is checked, the
             * batchEditSubmit button is enabled.
             */
            function checkBatchEditSubmitButton() {
                var checked = false;
                itemCheckboxes.each(function() {
                    if (jQuery(this).attr("checked")) {
                        checked = true;
                    }
                });
                
                if (checked) {
                    batchEditSubmit.removeAttr('disabled');
                } else {
                    batchEditSubmit.attr('disabled', 'disabled');
                }
            }
        });
    </script>
    <div id="browse-meta" class="group">
        <ul id="items-sort" class="navigation">
            <li><strong>Quick Filter</strong></li>
            <?php
                echo nav(array(
                    __('All') => uri('items'), 
                    __('Public') => uri('items/browse?public=1'),
                    __('Private') => uri('items/browse?public=0'),
                    __('Featured') => uri('items/browse?featured=1')
                    ));
            ?>
        </ul>
        <div id="simple-search-form">
            <?php echo simple_search(); ?>
    		<?php echo link_to_advanced_search(__('Advanced Search'), array('id' => 'advanced-search-link')); ?>
        </div>
    </div>
    
<form id="items-browse" action="<?php echo html_escape(uri('items/batch-edit')); ?>" method="get" accept-charset="utf-8">
<?php if (has_permission('Items', 'edit')): ?>
    <div class="batch-edit-option">
        <input type="submit" class="submit" name="submit" value="<?php echo __('Edit Selected Items'); ?>" />
    </div>
<?php endif; ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <table id="items" class="simple" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <?php if (has_permission('Items', 'edit')): ?>
                <th id="batch-edit-heading"><?php echo __('Select'); ?></th>
                <?php endif; ?>
            <?php
            $browseHeadings['Title'] = 'Dublin Core,Title';
            $browseHeadings['Creator'] = 'Dublin Core,Creator';
            $browseHeadings['Date Added'] = 'added';
            echo browse_headings($browseHeadings); ?>
            </tr>
        </thead>
        <tbody>
    <?php $key = 0; ?>
    <?php while($item = loop_items()): ?>
    <tr class="item <?php if(++$key%2==1) echo 'odd'; else echo 'even'; ?>">
        <?php $id = item('id'); ?>
        <?php if (has_permission($item, 'edit') || has_permission($item, 'tag')): ?>
        <td class="batch-edit-check" scope="row"><input type="checkbox" name="items[]" value="<?php echo $id; ?>" /></td>
        <?php endif; ?>
        <td class="item-info">
            <span class="title"><?php echo link_to_item(); ?></span>
            <ul class="action-links group">
                <?php if (has_permission($item, 'edit')): ?>
                <li><?php echo link_to_item('Edit', array(), 'edit'); ?></li>
                <?php endif; ?>
                <?php if (has_permission($item, 'delete')): ?>
                <li><?php echo link_to_item('Delete', array('class' => 'delete-confirm'), 'delete-confirm'); ?></li>
                <?php endif; ?>
            </ul>
            <?php fire_plugin_hook('admin_append_to_items_browse_simple_each'); ?>
            <div class="item-details">
                <?php
                if (item_has_thumbnail()) {
                    echo link_to_item(item_square_thumbnail(), array('class'=>'square-thumbnail'));
                }
                ?>
                <?php echo snippet_by_word_count(strip_formatting(item('Dublin Core', 'Description')), 40); ?>
                <ul>
                    <li><strong><?php echo __('Collection'); ?>:</strong> <?php if (item_belongs_to_collection()) echo item('Collection Name'); else echo 'No Collection'; ?></li>
                    <li><strong><?php echo __('Tags'); ?>:</strong> <?php if ($tags = item_tags_as_string()) echo $tags; else echo 'No Tags'; ?></li>
                </ul>
                <?php fire_plugin_hook('admin_append_to_items_browse_detailed_each'); ?>
            </div>
        </td>
        <td><?php echo strip_formatting(item('Dublin Core', 'Creator')); ?></td>    
        <td><?php echo date('m.d.Y', strtotime(item('Date Added'))); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    </table>
    <?php if (has_permission('Items', 'edit')): ?>
    <div class="batch-edit-option">
        <input type="submit" class="submit" name="submit" value="<?php echo __('Edit Selected Items'); ?>" />
    </div>
    <?php endif; ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
</form>

<div id="output-formats">
    <h2><?php echo __('Output Formats'); ?></h2>
    <?php echo output_format_list(false); ?>
</div>

<?php elseif(!total_items()): ?>
    <div id="no-items">
    <p><?php echo __('There are no items in the archive yet.'); ?>
    
    <?php if(has_permission('Items','add')): ?>
          <?php echo link_to('items', 'add', __('Add an Item.')); ?></p>
    <?php endif; ?>
</div>
    
<?php else: ?>
    <p><?php echo __('The query searched %s items and returned no results.', total_items()); ?> <?php echo __('Would you like to %s?', link_to_advanced_search(__('refine your search'))); ?></p>
    
<?php endif; ?>



<?php fire_plugin_hook('admin_append_to_items_browse_primary', $items); ?>

</div>
<?php foot(); ?>

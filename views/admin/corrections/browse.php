<?php
$pageTitle = __('Column Corrections') . ' ' . __('(%s total)', $total_results);
echo head(array('title' => $pageTitle, 'bodyclass' => 'column-corrections browse'));
$fpTable = get_db()->getTable('NewspapersFrontPage');
?>
<div id='primary'>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <form method='POST'>
    <button>Fixit!</button>
    <table>
        <thead>
            <tr>
                <th>Accept</th>
                <th>Apply to entire paper</th>
                <th>Original Cols</th>
                <th>Corrected Cols</th>
                <th>Front Page</th>
            </tr>
        </thead>
    <tbody>
    <?php foreach($newspapers_columns_corrections as $correction): ?>
    <?php 
        $fp = $fpTable->find($correction->fp_id); 
        $fpItem = $fp->getItem();
    ?>
        <tr>
            <td>
                <input type='checkbox' name='accept[]' value='<?php echo $correction->id; ?>'></input>
            </td>
            <td>
                <input type='checkbox' name='accept-np[]' value='<?php echo $correction->id; ?>'></input>
            </td>
            <td><?php echo $correction->original_columns; ?></td>
            <td><?php echo $correction->corrected_columns; ?></td>
            <td>
                <a href="<?php echo html_escape(public_url('items/show/'.metadata($fpItem, 'id'))); ?>" class="big blue button" target="_blank"><?php echo metadata($fpItem, array('Dublin Core', 'Title')); ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </form>
    
    <div class="pagination"><?php echo pagination_links(); ?></div>
</div>

<?php echo foot(); ?>
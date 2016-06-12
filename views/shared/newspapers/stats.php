<?php
$pageTitle = 'Newspaper Stats';
echo head(array('title' => $pageTitle));

?>

<form method='POST'>
    <label for='columns'>Columns</label> <input name='columns' type='text' />
    <label for='columns'>Year</label> <input name='year' type='text' />

<button>Filter Newspapers</button>
</form>

<?php
    echo $this->partial('newspapers-stats.php', 
           array(
               'stats' => $allStats,
           ));
?>

<?php echo foot(); ?>
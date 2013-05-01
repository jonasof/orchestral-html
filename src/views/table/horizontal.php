<?php 

$attributes['table'] = Html::decorate($attributes['table'], array('class' => 'table table-striped')); ?>
<table<?php echo Html::attributes($attributes['table']); ?>>
	<thead>
		<tr>
<?php foreach ($columns as $col): ?>
			<th<?php echo Html::attributes($col->header ?: array()); ?>><?php echo $col->label; ?></th>
<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
<?php foreach ($rows as $row): ?>
		<tr<?php echo Html::attributes(call_user_func($attributes['row'], $row) ?: array()); ?>>
<?php foreach ($columns as $col): ?>
			<td<?php echo Html::attributes(call_user_func($col->attributes, $row)); ?>><?php 

				$columnValue = call_user_func($col->value, $row);
				echo ( !! $col->escape ? e($columnValue) : $columnValue); ?></td>
<?php endforeach; ?>
		</tr>
<?php endforeach; if ( ! count($rows) and $empty): ?>
		<tr class="norecords">
			<td colspan="<?php echo count($columns); ?>"><?php echo $empty; ?></td>
		</tr>
<?php endif; ?>
	</tbody>
</table>
<?php echo $pagination ?: ''; ?>

<?php

<table<?php echo HTML::attributable($attributes['table'], ['class' => 'table table-striped']); ?>>
    <tbody>
<?php foreach ($columns as $col): ?>
        <tr>
            <th<?php echo HTML::attributes($col->headers ?: []); ?>><?php echo $col->label; ?></th>
<?php foreach ($rows as $row): ?>
            <td<?php echo HTML::attributes(call_user_func($col->attributes, $row)); ?>>
                <?php echo $col->getValue($row); ?>
            </td>
<?php endforeach; ?>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php echo $pagination ?: ''; ?>

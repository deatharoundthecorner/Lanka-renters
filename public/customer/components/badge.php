<?php

$status = $status ?? "info";

$text = $text ?? "Status";

?>

<span class="badge badge-<?php echo $status; ?>">

    <?php echo htmlspecialchars($text); ?>

</span>
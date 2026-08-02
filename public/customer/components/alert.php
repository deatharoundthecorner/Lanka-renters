<?php

$type = $type ?? "success";

$message = $message ?? "";

?>

<div class="alert alert-<?php echo $type; ?>">

    <?php echo htmlspecialchars($message); ?>

</div>
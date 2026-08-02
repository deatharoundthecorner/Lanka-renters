<nav>

    <?php

    if(isset($items)){

        echo implode(" > ", array_map('htmlspecialchars', $items));

    }

    ?>

</nav>
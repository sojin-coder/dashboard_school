<?php
include "db.php";

session_destroy();
?>

<script>

sessionStorage.clear();

window.location='login.php';

</script>
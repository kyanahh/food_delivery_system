<?php

require_once "../includes/auth.php";
requireRole("customer");


$foodId = $_POST["food_id"] ?? null;


if ($foodId && isset($_SESSION["cart"][$foodId])) {

    unset($_SESSION["cart"][$foodId]);

}


header("Location: cart.php");
exit;
<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");


function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


$orderId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if (!$orderId) {

    die("Invalid order.");

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.*,
        u.first_name,
        u.last_name,
        u.phone,
        u.email
    FROM orders o
    INNER JOIN users u
        ON u.user_id = o.user_id
    WHERE o.order_id = ?
    LIMIT 1
");

$stmt->execute([
    $orderId
]);

$order =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {

    die("Order not found.");

}


/*
|--------------------------------------------------------------------------
| ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        fi.food_name
    FROM order_items oi
    INNER JOIN food_items fi
        ON fi.food_id = oi.food_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");

$stmt->execute([
    $orderId
]);

$items =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Receipt <?= e($order["order_number"]) ?>
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            padding: 20px;

            background: #f3f3f3;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        .receipt {

            width: 80mm;

            max-width: 80mm;

            margin: auto;

            background: #fff;

            padding: 8mm 5mm;

        }


        .center {

            text-align: center;

        }


        .business-name {

            font-size: 18px;

            font-weight: bold;

            color: #d71920;

        }


        .business-info {

            font-size: 9px;

            line-height: 1.5;

            color: #555;

        }


        .receipt-title {

            font-size: 14px;

            font-weight: bold;

            margin-top: 10px;

        }


        .divider {

            border-top: 1px dashed #777;

            margin: 8px 0;

        }


        .info {

            font-size: 10px;

            line-height: 1.5;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            font-size: 10px;

        }


        th {

            border-bottom: 1px solid #222;

            padding: 4px 0;

            text-align: left;

        }


        td {

            padding: 4px 0;

            vertical-align: top;

        }


        .right {

            text-align: right;

        }


        .total {

            font-size: 14px;

            font-weight: bold;

        }


        .footer {

            text-align: center;

            font-size: 9px;

            color: #555;

            margin-top: 12px;

            line-height: 1.5;

        }


        .print-button {

            display: block;

            margin: 15px auto;

            padding: 10px 20px;

            border: 0;

            border-radius: 6px;

            background: #d71920;

            color: #fff;

            cursor: pointer;

            font-weight: bold;

        }


        @media print {

            body {

                background: #fff;

                padding: 0;

            }


            .receipt {

                margin: 0;

                width: 80mm;

                max-width: 80mm;

            }


            .print-button {

                display: none;

            }


            @page {

                size: 80mm auto;

                margin: 0;

            }

        }

    </style>

</head>


<body>


<button
    class="print-button"
    onclick="window.print()"
>
    Print Receipt
</button>


<div class="receipt">


    <!-- BUSINESS -->

    <div class="center">

        <div class="business-name">

            JOLLIBEE PLAZA CENTRAL

        </div>


        <div class="business-info">

            GF, Plaza, Central Bldg,<br>

            Katihan Rd, Brgy Poblacion,<br>

            Muntinlupa, Metro Manila<br>

            (02) 8851 6362

        </div>


        <div class="receipt-title">

            ORDER RECEIPT

        </div>

    </div>


    <div class="divider"></div>


    <!-- ORDER INFORMATION -->

    <div class="info">

        <strong>
            Order No.:
        </strong>

        <?= e(
            $order["order_number"]
        ) ?>

        <br>


        <strong>
            Date:
        </strong>

        <?= e(
            date(
                "M d, Y h:i A",
                strtotime(
                    $order["created_at"]
                )
            )
        ) ?>

        <br>


        <strong>
            Customer:
        </strong>

        <?= e(
            $order["first_name"] .
            " " .
            $order["last_name"]
        ) ?>

        <br>


        <?php if (!empty($order["phone"])): ?>

            <strong>
                Phone:
            </strong>

            <?= e(
                $order["phone"]
            ) ?>

            <br>

        <?php endif; ?>


        <strong>
            Type:
        </strong>

        <?= e(
            $order["order_type"]
        ) ?>


        <?php if (
            $order["order_type"] === "Delivery"
        ): ?>

            <br>

            <strong>
                Address:
            </strong>

            <?= nl2br(
                e(
                    $order["delivery_address"]
                )
            ) ?>

        <?php endif; ?>

    </div>


    <div class="divider"></div>


    <!-- ITEMS -->

    <table>

        <thead>

            <tr>

                <th>
                    Item
                </th>

                <th
                    class="right"
                >
                    Qty
                </th>

                <th
                    class="right"
                >
                    Amount
                </th>

            </tr>

        </thead>


        <tbody>

            <?php foreach (
                $items
                as $item
            ): ?>

                <tr>

                    <td>

                        <?= e(
                            $item["food_name"]
                        ) ?>

                        <br>

                        <small>

                            ₱<?= number_format(
                                (float)
                                $item["unit_price"],
                                2
                            ) ?>

                            each

                        </small>

                    </td>


                    <td
                        class="right"
                    >

                        <?= (int)
                            $item["quantity"] ?>

                    </td>


                    <td
                        class="right"
                    >

                        ₱<?= number_format(
                            (float)
                            $item["subtotal"],
                            2
                        ) ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>


    <div class="divider"></div>


    <!-- TOTAL -->

    <table>

        <tr>

            <td class="total">

                TOTAL

            </td>

            <td
                class="right total"
            >

                ₱<?= number_format(
                    (float)
                    $order["total_amount"],
                    2
                ) ?>

            </td>

        </tr>

    </table>


    <div class="divider"></div>


    <!-- PAYMENT -->

    <div class="info">

        <strong>
            Payment Method:
        </strong>

        <?= e(
            $order["payment_method"]
        ) ?>

        <br>


        <strong>
            Payment Status:
        </strong>

        <?= e(
            $order["payment_status"]
        ) ?>


        <?php if (
            $order["cash_received"] !== null
        ): ?>

            <br>

            <strong>
                Cash Received:
            </strong>

            ₱<?= number_format(
                (float)
                $order["cash_received"],
                2
            ) ?>

        <?php endif; ?>


        <?php if (
            $order["change_amount"] !== null
        ): ?>

            <br>

            <strong>
                Change:
            </strong>

            ₱<?= number_format(
                (float)
                $order["change_amount"],
                2
            ) ?>

        <?php endif; ?>

    </div>


    <?php if (
        !empty(
            $order["customer_notes"]
        )
    ): ?>

        <div class="divider"></div>

        <div class="info">

            <strong>
                Customer Notes:
            </strong>

            <br>

            <?= nl2br(
                e(
                    $order["customer_notes"]
                )
            ) ?>

        </div>

    <?php endif; ?>


    <!-- FOOTER -->

    <div class="footer">

        Thank you for ordering!

        <br>

        Jollibee Plaza Central

        <br>

        Please keep this receipt
        for your reference.

    </div>


</div>


<script>

window.addEventListener(
    "load",
    function () {

        setTimeout(
            function () {

                window.print();

            },
            500
        );

    }
);

</script>


</body>

</html>
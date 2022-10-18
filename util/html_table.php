<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        th {
            text-align: left;
        }

        tr {
            border-bottom: 1px solid gainsboro;
        }

        td.blue {
            color: blue;
        }

        td.green {
            color: green;
        }

        td.gray {
            color: gray;
        }
    </style>
    <table>
        <thead>
            <?php foreach (SQL_QUERY_COLUMNS as $k) : ?>
                <th><?= $k ?></th>
            <?php endforeach ?>
        </thead>
        <tbody>
            <?php foreach (SQL_QUERY_RESULTS as $k) : ?>
                <tr>
                    <?php foreach ($k as $a => $b) : ?>
                        <?php
                        if (is_numeric($b ?? "")) {
                            $class = "blue";
                        } else if (strtotime($b ?? "")) {
                            $class = "green";
                        } else if ($b == null) {
                            $class = "gray";
                        } else {
                            $class = "";
                        }
                        ?>
                        <td class="<?= $class ?>"><?= $b ?? "(empty)" ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>
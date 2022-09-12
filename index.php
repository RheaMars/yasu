<?php
spl_autoload_register(function ($class) {
    include $class . '.php';
});

use src\models\Playboard;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sudoku</title>
    <link href="css/playboard.css" rel="stylesheet">
</head>
<body>
    <h1>Sudoku</h1>

    <table class="playboard">
        <tbody>
        <?php
        $playboard = new Playboard(3);
        $html = generatePlayboardHtml($playboard);

        echo $html;
        ?>
        </tbody>
    </table>
</body>
</html>

<?php
function generatePlayboardHtml(Playboard $playboard): string
{
    $fields = $playboard->getFields();
    $baseSize = $playboard->getBaseSize();

    $html = "";

    for($playboardRow = 1; $playboardRow <= $baseSize; $playboardRow++) {
        $html .= "<tr>";
        for($playboardCol = 1; $playboardCol <= $baseSize; $playboardCol++) {
            $html .= "<td>";
            $html .= "<table class='block'>";
            $html .= "<tbody>";
            for($blockRow = 1; $blockRow <= $baseSize; $blockRow++) {
                $html .= "<tr>";
                for($blockCol = 1; $blockCol <= $baseSize; $blockCol++) {
                    $row = ($playboardRow - 1) * $baseSize + $blockRow;
                    $col = ($playboardCol - 1) * $baseSize + $blockCol;
                    $field = $fields[$row."-".$col];
                    $value = $field->getDigit()->getValue();

                    $html .= "<td>";
                    $html .= "<input class='field row-".$row." col-".$col." block-row-".$blockRow." block-col-".$blockCol." playboard-row-".$playboardRow." playboard-col-".$playboardCol."' value='".$value."'/>";
                    $html .= "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody>";
            $html .= "</table>";
            $html .= "</td>";
        }
        $html .= "</tr>";
    }
    return $html;
}

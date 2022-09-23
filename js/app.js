$(document).ready(function () {

    requestNewGame();

    $("#createNewGame").click(function() {
        requestNewGame();
    });

    $("#validateGame").click(function() {
        validateGame();
    });

    $("#solveGame").click(function() {
        solveGame();
    });

    $("#restartGame").click(function() {
        $("input.field").filter(function() {
            return !($(this).is(".isFixed"));
        }).val("");

        $("td").removeClass("invalidValue");
    });
})

function requestNewGame() {
    $.ajax({
        url: "src/actions/createNewGame.php",
        method: "POST",
        beforeSend: function() {
            $("#loadingGif").show();
            $("#playboardWrapper").html("");
            $(".toggleOnCreateGame").hide();
        },
        complete: function () {
            $("#loadingGif").hide();
            $(".toggleOnCreateGame").show();
        },
        data: { baseSize: $("#selectBaseSize").val() },
        success: function(result) {
            $("#playboardWrapper").html(result);

            const maxNumberAllowed = Math.sqrt($(".field").length);

            $("input.field").on("change", function() {

                $(this).parent("td").removeClass("invalidValue");

                const value = $(this).val();
                if (!$.isNumeric(value) || value > maxNumberAllowed || value < 1) {
                    alert("Only numbers between 1 and " + maxNumberAllowed + " are allowed as input.");
                    $(this).val("");
                }
            });
        }
    });
}

function validateGame() {

    let fieldData = getFieldData();

    $.ajax({
        url: "src/actions/validateGame.php",
        method: "POST",
        data: {
            fieldData: JSON.stringify(fieldData)
        },
        beforeSend: function() {
            $("td").removeClass("invalidValue");
        },
        success: function(result) {
            const invalidFields = JSON.parse(result);
            if (invalidFields.length === 0) {
                alert("Playboard is valid.");
            }
            else {
                markInvalidFields(invalidFields);
            }
        }
    })
}

function solveGame() {
    let fieldData = getFieldData();

    $.ajax({
        url: "src/actions/solveGame.php",
        method: "POST",
        beforeSend: function() {
            $("#loadingGif").show();
            $("td").removeClass("invalidValue");
        },
        complete: function () {
            $("#loadingGif").hide();
        },
        data: {
            fieldData: JSON.stringify(fieldData)
        },
        success: function(result) {
            const response = JSON.parse(result);
            console.log(response.status);

            if (response.status === "invalid") {
                markInvalidFields(response.invalidFields);
                alert("Current state of playboard is invalid so it can't be solved.");
            }
            else if (response.status === "unresolved") {
                alert("Could not solve playboard. You might want to try again.");
                //TODO Basil die alte Zicke
                markInvalidFields(response.fields);
            }
            else {
                alert("Playboard is solved");
                markSolvedFields(response.fields);
            }
        }
    })
}

function getFieldData() {
    const $fields = $("input.field");
    let fieldData = [];

    $fields.each(function(index){
        fieldData.push(
            {
                row: $(this).attr('data-row'),
                col: $(this).attr('data-col'),
                val: $(this).val(),
                isFixed: $(this).hasClass("isFixed")
            }
        );
    });

    return fieldData;
}

function markInvalidFields(invalidFields) {
    invalidFields.forEach((invalidField) => {
        $('.field[data-row="' + invalidField.row + '"][data-col="' + invalidField.col + '"]').parent("td").addClass("invalidValue");
    });
}

function markSolvedFields(solvedFields) {
    solvedFields.forEach((solvedField) => {
        $('.field[data-row="' + solvedField.row + '"][data-col="' + solvedField.col + '"]').val(solvedField.value);
    });
}
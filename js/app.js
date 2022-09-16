$(document).ready(function () {

    requestNewGame();

    $("#createNewGame").click(function() {
        requestNewGame();
    });

    $("#validateGame").click(function() {
        validateGame();
    });

    $("#restartGame").click(function() {
        $("input.field").filter(function() {
            return !($(this).is(".isFixed"));
        }).val("");
    });
})

function requestNewGame() {
    $.ajax({
        url: "src/actions/createNewGame.php",
        method: "POST",
        beforeSend: function() {
            $("#loadingGif").show();
            $("#playboardWrapper").html("");
            $("#validateGame").hide();
            $("#restartGame").hide();
        },
        complete: function () {
            $("#loadingGif").hide();
            $("#validateGame").show();
            $("#restartGame").show();
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

    $.ajax({
        url: "src/actions/validateGame.php",
        method: "POST",
        data: {
            fieldData: fieldData
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
                invalidFields.forEach((invalidField) => {
                    $('.field[data-row="' + invalidField.row + '"][data-col="' + invalidField.col + '"]').parent("td").addClass("invalidValue");
                });
            }
        }
    })
}
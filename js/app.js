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
        }
    });
}

function validateGame() {
    const $fields = $("input.field");
    let fieldData = [];
    
    $fields.each(function(index){
        const classes = $(this).attr("class").split(" ");
        
        fieldData.push(
            {
                row: getFirstFoundSuffix(classes, "row-"),
                col: getFirstFoundSuffix(classes, "col-"),
                val: $(this).val(),
                isFixed: $(this).hasClass("isFixed")
            }
        );
    });

    $.ajax({
        url: "src/actions/validateGame.php",
        method: "POST",
        data: {
            numberOfFields: $fields.length,
            fieldData: fieldData
        },
        success: function(result) {
            if (result == 1){
                alert("Valid!");
            }
            else{
                alert("Damn...");
            }
        },
        error: function(error) {
            alert("ERROR")
        }
    })

    function getFirstFoundSuffix(strings, startsWith){
        let suffix = ""; 
        $.each(strings, function(){
            if (this.match("^" + startsWith)){
                suffix = this.replace(startsWith, "");
                return;
            }
        });
        return suffix;
    }
}
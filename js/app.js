$(document).ready(function () {

    $("#createNewGame").click(function() {
        $.ajax({
            url: "src/actions/createNewGame.php",
            success: function(result){
                $("#playboardWrapper").html(result);
            }
        });
    });

    $("#restartGame").click(function() {
        const unfixedFields = $("input.field").filter(function() {
            return !($(this).is(".isFixed"));
        }).val("");
    });
})
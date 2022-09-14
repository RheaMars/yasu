$(document).ready(function () {

    requestNewGame();

    $("#createNewGame").click(function() {
        requestNewGame();
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
        },
        complete: function () {
            $("#loadingGif").hide();
        },
        data: { baseSize: $("#selectBaseSize").val() },
        success: function(result) {
            $("#playboardWrapper").html(result);
        }
    });
}
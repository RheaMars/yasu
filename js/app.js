$(document).ready(function () {

    $("#createNewGame").click(function() {
        $.ajax({
            url: "src/actions/createNewGame.php",
            method: "POST",
            data: { baseSize: $("#selectBaseSize").val() },
            success: function(result) {
                $("#playboardWrapper").html(result);
            }
        });
    });

    $("#restartGame").click(function() {
        $("input.field").filter(function() {
            return !($(this).is(".isFixed"));
        }).val("");
    });
})
$(document).ready(function () {

    $("#createNewGame").click(function() {

        $.ajax({
            url: "src/actions/createNewGame.php",
            success: function(result){
                $("#playboardWrapper").html(result);
            }
        });
    });
})
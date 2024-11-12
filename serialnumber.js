$(document).ready(function () {
  

  // Gestion du formulaire des numéros de série
  $("#serial-number-form").on("submit", function (e) {
    e.preventDefault();
    console.log("Form action URL: ", $(this).attr("action")); // Ajoutez ceci pour vérifier l'URL
    var formData = $(this).serialize();

    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: formData,
      dataType: "json",
      success: function (response) {
        var messageContainer = $("#message-container");
        if (response.success) {
          messageContainer.html(
            '<div class="alert alert-success">' + response.message + "</div>"
          );
        } else {
          messageContainer.html(
            '<div class="alert alert-danger">' + response.message + "</div>"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la soumission du formulaire:", error);
        $("#message-container").html(
          '<div class="alert alert-danger">Une erreur est survenue lors de la soumission : ' +
            error +
            "</div>"
        );
      },
    });
  });
});

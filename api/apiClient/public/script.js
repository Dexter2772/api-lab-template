$(document).ready(function() {
  $("#peopleForm").submit(function(event) {
    var form = $(this);
    event.preventDefault();
    $.ajax({
      type: "POST",
      url: "http://localhost:8080/api/books",
      data: form.serialize(), // serializes the form's elements.
      success: function(data) {
        window.location.replace("http://localhost:8080/api/apiClient");
      }
    });
  });
  $("#peopleEditForm").submit(function(event) {
    var form = $(this);
    event.preventDefault();
    $.ajax({
      type: "PUT",
      url: "http://localhost:8080/api/books/" + $(this).attr("data-id"),
      data: form.serialize(),
      success: function(data) {
        window.location.replace("http://localhost:8080/api/apiClient");
      }
    });

  });
  $( ".deletebtn" ).click(function() {
  alert( "Are you sure you want to delete this user?" );
  $.ajax({
    type: "DELETE",
    url: "http://localhost:8080/api/books/" + $(this).attr("data-id"),
    success: function(data) {
      window.location.reload("http://localhost:8080/api/apiClient");
    }
  });

});
});

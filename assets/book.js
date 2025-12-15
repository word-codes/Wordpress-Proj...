jQuery(document).ready(function($) {
  
console.log('defggg');

  let offset = 3; 
  const count = 3;

  $("#frm-add-book").submit(function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    formData.append("action", "newBook");

    $.ajax({
      url: my_ajax_object.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.success) {
          Swal.fire("Success", res.data.message, "success");
          $("#frm-add-book")[0].reset();
        }
      }
    });
  });

  $("#load-more-button").on("click", function () {
    $.ajax({
      url: my_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "load_more_books",
        offset: offset,
        count: count,
      },
      success: function (res) {
        if (res.success) {
          $("#books-container .row").append(res.data.html);
          offset += count;

          if (!res.data.has_more_books) {
            $("#load-more-button").hide();
          }
        }
      }
    });
  });

});

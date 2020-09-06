//This file contains ale core functions of the MVC framework. Currently, there
//is one AJAX function implemented
var mvc = {
  ajax_load_page: function(url, anchor, data = {}, pushState = true)
  {
    //uggly as hell
    if (typeof fetch_message_interval !== 'undefined') {
        clearInterval(fetch_message_interval);
    }

    data.anchor = anchor;
    console.log("MVC AJAX Request called to " + url + " with data ");
    console.log(data);
    $.ajax({
      url: url,
      type: 'POST',
      data: data,
      success: function(data){
        response = null;
        //because AJAX, data should be JSON encoded
        try {
          response = JSON.parse(data);
        }
        catch (e) {
          //parsing unsuccesfull, then just print the text we've got because it
          //probably contains a fatal error
          $("#"+anchor).html(data);
        }

        if(response !== null)
        {
          //put errors (which can also be warnings / notices) on the page
          var error_html = "";
          response.errors.forEach(function(current) {
            error_html +=  '<div class="msg ' + current.type + '">' + current.msg + '</div>';
          });
          $("#err_list").html(error_html);

          //render html
          $("#"+anchor).html(response.html);

          //render possibly other parts of the page
          if(response.additional_anchor_html)
          {
            $.each(response.additional_anchor_html,function(anchor, html){
            //response.additional_anchor_html.forEach(function(current){
            //  console.log(current);
              $("#"+anchor).html(html);
            });
          }

          if(response.css)
          {
            response.css.forEach(function(current){
              if (!$("link[href='" + current + "']").length)
                $('<link href="/' + current + '" rel="stylesheet">').appendTo("head");
            });
          }
          if(response.js)
          {
            response.js.forEach(function(current){
              if (!$("script[src='" + current + "']").length)
                $('<script src="/' + current + '"></script>').appendTo("head");
            });
          }

          //apply autofocus if neccesary
          $("input[autofocus]").focus();

          if(pushState)
          {
            //update url in browser bar for user convenience
            var new_state = url;
            if(response.pushstate)
            {
              new_state = response.pushstate;
            }
            history.pushState({anchor: anchor, url: new_state}, null, new_state);
          }
        }
      },
      error: function (xhr, status, error) {
        $("#"+anchor).html(xhr.responseText);
      }
    })
  },
  ajax_get_data: function(url, data = {}, callback = function(ajaxdata){})
  {
    $.ajax({
      url: url,
      type: 'POST',
      data: data,
      success: function (data)
      {
        response = JSON.parse(data);

        var error_html = "";
        response.errors.forEach(function(current) {
          error_html +=  '<div class="msg ' + current.type + '">' + current.msg + '</div>';
        });
        $("#err_list").html(error_html);

        callback(response.data);
      },
      error: function()
      {
        console.log("An error occured");
      }
    })
  }
}
//on browser back / forward button
window.onpopstate = function (event) {
    if(event.state)
    {
      //state is set, we return to a page previously loaed with AJAX
      mvc.ajax_load_page(event.state.url, event.state.anchor, {}, false)
    }
    else
    {
      //state is not set => we arrived at the first page loaded without ajax.
      //Load this without ajax as well.
      location = location.href;
    }
};

//this piece ensures that hitting enter inside an input field will submit the form
$(document).ready(function() {
  $(document).on("keypress", "input",function(event){
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if(keycode == '13'){
        $("#a_" +event.target.form.id).trigger("click");
    }
  });
});

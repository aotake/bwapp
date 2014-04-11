$(document).ready(function() {

//    $("#register_form").keypress(function(ev) {
//        if ((ev.which && ev.which === 13) || (ev.keyCode && ev.keyCode === 13)) {
//            return false;
//        } else {
//            return true;
//        }
//    });

  $("input[type=text]").keypress(function(ev) {
    if ((ev.which && ev.which === 13) ||
        (ev.keyCode && ev.keyCode === 13)) {
      alert('Enterキーは使わず、画面のボタンをクリックして下さい');
      return false;
    } else {
      return true;
    }
  });
  $("input[type=password]").keypress(function(ev) {
    if ((ev.which && ev.which === 13) ||
        (ev.keyCode && ev.keyCode === 13)) {
      alert('Enterキーは使わず、画面のボタンをクリックして下さい');
      return false;
    } else {
      return true;
    }
  });
  $("input[type=button]").keypress(function(ev) {
    if ((ev.which && ev.which === 13) ||
        (ev.keyCode && ev.keyCode === 13)) {
      alert('Enterキーは使わず、画面のボタンをクリックして下さい');
      return false;
    } else {
      return true;
    }
  });
  $("input[type=submit]").keypress(function(ev) {
    if ((ev.which && ev.which === 13) ||
        (ev.keyCode && ev.keyCode === 13)) {
      alert('Enterキーは使わず、画面のボタンをクリックして下さい');
      return false;
    } else {
      return true;
    }
  });

});

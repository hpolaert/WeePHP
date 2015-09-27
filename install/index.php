<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>WeePhp - Hello World !</title>
  <meta name="description" content="WeePHP Framework HomePage">
  <meta name="author" content="Hugues Polaert">
  <link href='http://fonts.googleapis.com/css?family=Raleway:600'
        rel='stylesheet' type='text/css'>
  <link href='http://fonts.googleapis.com/css?family=Ubuntu+Condensed'
        rel='stylesheet' type='text/css'>
  <link href="styles/style.css" rel="stylesheet" type="text/css">
  <!--[if lt IE 9]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
  <link rel="stylesheet" type="text/css"
        href="styles/ui-lightness/jquery-ui-1.8.2.custom.css"/>
</head>
<body>
<div class="content">
  <form method="post" action="setup.php" id="setupForm" class="bbq">
    <div class="step" id="fSettings">
      <h2 class="StepTitle">Step 1/3 - Framework configuration</h2>
      <span style="display:none;visibility:hidden;"><input type="text"/></span>

      <p class="input">
        <label for="location">Framework root folder web location:</label>
        <input type="text" name="location" class="inlineInput required"
               size="30"
               maxlength="40" value="ex: http://mywebeswite.com/weephp"
               id="location"/>
      </p>

      <p class="input">
        <label for="encryption">Encryption key (random alphanumeric word)
          :</label>
        <input type="text" name="encryption" class="inlineInput required"
               size="22"
               maxlength="40" value="" id="encryption"/>
      </p>

      <p class="input">
        <label for="language">Default language :</label>
        <select id="language" name="language" class="selectInput required">
          <option value="">- Select -</option>
          <option value="fr">French</option>
          <option value="en">English</option>
          <option value="es">Spanish</option>
          <option value="de">German</option>
          <option value="it">Italian</option>
          <option value="nl">Dutch</option>
        </select>
      </p>
      <p class="input">
        <label for="emailfrom">Website system email address :</label>
        <input type="text" name="emailfrom" class="inlineInput email required"
               size="30"
               maxlength="40" value="ex: noreply@yourwebsite.com"
               id="emailfrom"/>
      </p>
    </div>
    <div class="step" id="uSettings">
      <h2 class="StepTitle">Step 2/3 - Super admin account</h2>

      <p class="input">
        <label for="login">Super user login :</label>
        <input type="text" name="login" class="inlineInput required" size="30"
               maxlength="40" value="" id="login"/>
      </p>

      <p class="input">
        <label for="password">Super user password :</label>
        <input type="password" name="password" class="inlineInput" size="30"
               maxlength="40" value="" id="password"/>
      </p>

      <p class="input">
        <label for="passwordcfrm">Confirm super user password :</label>
        <input type="password" name="passwordcfrm" class="inlineInput" size="30"
               maxlength="40" value="" id="passwordcfrm"/>
      </p>
    </div>
    <div class="step" id="dSettings">
      <h2 class="StepTitle">Step 3/3 - Database configuration</h2>

      <p class="input">
        <label for="dhost">Database host (ex: localhost) :</label>
        <input type="text" name="dhost" class="inlineInput" size="30"
               maxlength="40" value="" id="dhost"/>
      </p>

      <p class="input">
        <label for="dlogin">Database login :</label>
        <input type="text" name="dlogin" class="inlineInput required" size="30"
               maxlength="40" value="" id="dlogin"/>
      </p>

      <p class="input">
        <label for="dpassword">Database password :</label>
        <input type="password" name="dpassword" class="inlineInput" size="30"
               maxlength="40" value="" id="dpassword"/>
      </p>

      <p class="input">
        <label for="dname">Database name :</label>
        <input type="text" name="dname" class="inlineInput required" size="30"
               maxlength="40" value="" id="dname"/>
      </p>
    </div>
    <div id="nav">
      <input class="navbutton" id="back" value="Back" type="reset"/>
      <input class="navbutton" id="next" value="Next" type="submit"/>
    </div>
  </form>
  <p id="data"></p>
</div>
<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/jquery.form.js"></script>
<script type="text/javascript" src="js/jquery.validate.js"></script>
<script type="text/javascript" src="js/bbq.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.5.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.form.wizard.js"></script>
<script type="text/javascript">
  $(function () {
    $("#setupForm").formwizard({
        formPluginEnabled: true,
        validationEnabled: true,
        focusFirstInput: true,
        formOptions: {
          success: function (data) {
            $("#data").fadeTo(100, 1, function () {
              if (data.message == "success") {
                $(this).html("<strong><span style='font-size:10px;color:#27681d'>Framework is up and running! redirecting shortly...</span></strong>").fadeTo(4500, 0, function () {
                  var tURL = $("#location").val();
                  if (!/^http:\/\//.test(tURL)) {
                    tURL = "http://" + tURL;
                  }
                  window.location.replace(tURL);
                });
              } else {
                $(this).html("<strong><span style='font-size:10px;color:#9e0606'>" + data.message + "</span></strong>").fadeTo(6000, 0, function () {
                  $(this).html("");
                });
              }
            })
          },
          beforeSubmit: function (data) {
            $("#data").html("<strong><span style='font-size:10px;'>Checking configuration...</span></strong>").fadeTo(100, 1);
          },
          dataType: 'json'
        }
      }
    );
  });
  $(function ($) {
    $('#setupForm input').attr("data-placeholdertext", function () {
      return this.value;
    });
    $('#setupForm')
      .delegate('input', 'focus', function () {
        if (this.value === $(this).attr("data-placeholdertext")) {
          this.value = '';
        }
      })
      .delegate('input', 'blur', function () {
        if (this.value.length == 0) {
          this.value = $(this).attr("data-placeholdertext");
        }
      });
  });
  $("form").validate({
    rules: {
      password: {
        required: true,
        minlength: 5
      },
      passwordcfrm: {
        required: true,
        minlength: 5,
        equalTo: "#password"
      }
    },
    messages: {
      password: {
        required: "Please provide a password",
        minlength: "Your password must be at least 5 characters long"
      },
      passwordcfrm: {
        required: "Please provide a password",
        minlength: "Your password must be at least 5 characters long",
        equalTo: "Please enter the same password as above"
      }
    }
  });
</script>
</body>
</html>
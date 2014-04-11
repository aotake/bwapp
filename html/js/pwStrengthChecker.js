/**
 * パスワード強度チェックスクリプト
 *
 * 参考
 * http://codeassembly.com/How-to-make-a-password-strength-meter-for-your-register-form/
 *
 * HTML サンプル
 *
 *  <link rel="stylesheet" type="text/css" media="screen,print" href="/js/pwStrengthChecker.css">
 *  <script type="text/javascript" src="/js/pwStrengthChecker.js"></script>
 *
 *  <input type="password" name="passwd" id="passwd" value="" />
 *  <div id="password_meter">
 *      <label for="passwordStrength">パスワード強度：</label>
 *      <div id="passwordDescription">未入力</div>
 *      <div id="passwordStrengthWrap"> 
 *      <div id="passwordStrength" class="strength0"></div>
 *      </div>
 *      <div style="clear:both;"></div>
 *  </div> 
 * 
 */
function passwordStrength(password){
        var desc = new Array();
        desc[0] = "とても弱い";//"Very Weak";
        desc[1] = "弱い";      //"Weak";
        desc[2] = "やや弱い";  //"Better";
        desc[3] = "中程度";      //"Medium";
        desc[4] = "強い";      //Strong";
        desc[5] = "とても強い";//"Strongest";

        var score   = 0;

        //if password bigger than 6 give 1 point
        if (password.length > 6) score++;

        //if password has both lower and uppercase characters give 1 point      
        if ( ( password.match(/[a-z]/) ) && ( password.match(/[A-Z]/) ) ) score++;

        //if password has at least one number give 1 point
        if (password.match(/\d+/)) score++;

        //if password has at least one special caracther give 1 point
        if ( password.match(/.[!,@,#,$,%,^,&,*,?,_,~,-,(,)]/) ) score++;

        //if password bigger than 12 give another 1 point
        if (password.length > 12) score++;

        document.getElementById("passwordDescription").innerHTML = desc[score];
        document.getElementById("passwordStrength").className = "strength" + score;
}
$(function(){
    $("#passwd").keyup(function(){
        passwordStrength($(this).val());
    });
});

